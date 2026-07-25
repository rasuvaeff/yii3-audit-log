# rasuvaeff/yii3-audit-log

[![Stable Version](https://poser.pugx.org/rasuvaeff/yii3-audit-log/v/stable)](https://packagist.org/packages/rasuvaeff/yii3-audit-log)
[![Total Downloads](https://poser.pugx.org/rasuvaeff/yii3-audit-log/downloads)](https://packagist.org/packages/rasuvaeff/yii3-audit-log)
[![Build](https://github.com/rasuvaeff/yii3-audit-log/actions/workflows/build.yml/badge.svg)](https://github.com/rasuvaeff/yii3-audit-log/actions)
[![Static analysis](https://github.com/rasuvaeff/yii3-audit-log/actions/workflows/static-analysis.yml/badge.svg)](https://github.com/rasuvaeff/yii3-audit-log/actions)
[![Psalm Level](https://shepherd.dev/github/rasuvaeff/yii3-audit-log/level.svg)](https://shepherd.dev/github/rasuvaeff/yii3-audit-log)
[![PHP](https://img.shields.io/packagist/dependency-v/rasuvaeff/yii3-audit-log/php)](https://packagist.org/packages/rasuvaeff/yii3-audit-log)
[![License](https://poser.pugx.org/rasuvaeff/yii3-audit-log/license)](https://packagist.org/packages/rasuvaeff/yii3-audit-log)
[English version](README.md)

Audit trail для Yii3-приложений: кто, что и когда изменил, с маскированием
чувствительных значений. Ядро без состояния — writer подключается отдельно
(DB-адаптер живёт в другом пакете).

> Используете AI-ассистента? В [llms.txt](llms.txt) — компактный API-справочник.
> Проекты с Composer-плагином [llm/skills](https://github.com/roxblnfk/skills) дополнительно получают agent-скилл этого пакета в `.agents/skills/` автоматически при установке.

## Требования

- PHP 8.3+
- `psr/clock` ^1.0
- `symfony/uid` ^7.0 || ^8.0 — опционально, только для `Uuid7IdGenerator`

## Установка

```bash
composer require rasuvaeff/yii3-audit-log
```

## config-plugin Yii3

Пакет поставляет `config/di.php` и `config/params.php` через config-plugin. Он
конфигурирует `AuditLogger`, `SensitiveValueMasker` и
`AuditEventIdGeneratorInterface` (на `RandomHexIdGenerator`), но намеренно НЕ биндит
`AuditWriter` и `Psr\Clock\ClockInterface`. Установите ровно один адаптер writer-а
или забиндите `AuditWriter` в конфиге приложения:

```php
use Psr\Clock\ClockInterface;
use Rasuvaeff\Yii3AuditLog\AuditWriter;

return [
    AuditWriter::class => MyAuditWriter::class,
    ClockInterface::class => MyClock::class,
];
```

Параметры по умолчанию:

```php
return [
    'rasuvaeff/yii3-audit-log' => [
        'sensitiveKeys' => ['password', 'secret', 'token', 'api_key', 'credit_card'],
        'skipEmptyChangeSets' => true,
    ],
];
```

## Использование

### Идентификаторы событий

Id события — это первичный ключ таблицы аудита, поэтому его формат это выбор,
а не деталь реализации:

| Генератор | id | Когда |
|---|---|---|
| `RandomHexIdGenerator` (по умолчанию) | 32 случайных hex-символа | что угодно; сохраняет исторический формат |
| `Uuid7IdGenerator` | UUIDv7 в виде 32 hex-символов | большие растущие таблицы аудита |

Случайный первичный ключ разбрасывает вставки InnoDB по страницам (таблица
аудита append-only и только растёт, поэтому фрагментация накапливается), а
упорядоченный по времени пишется в конец. Плюс он сортируется хронологически —
это дешёвый tie-breaker для событий внутри одной секунды, по `occurred_at`
их не упорядочить.

`Uuid7IdGenerator` намеренно убирает дефисы: 32 символа — та же ширина, что и
у формата по умолчанию, поэтому он ложится в существующую колонку
`VARCHAR(32)` пакета
[rasuvaeff/yii3-audit-log-db](https://github.com/rasuvaeff/yii3-audit-log-db)
без миграции. Ему нужен `symfony/uid` — он в `suggest` и ставится, только если
генератор используется:

```bash
composer require symfony/uid
```

```php
// config/common/di/audit-log.php
use Rasuvaeff\Yii3AuditLog\AuditEventIdGeneratorInterface;
use Rasuvaeff\Yii3AuditLog\Uuid7IdGenerator;

return [
    AuditEventIdGeneratorInterface::class => Uuid7IdGenerator::class,
];
```

Определения приложения побеждают пакетные, так что эта одна строка и есть весь
переключатель. Собственная реализация `AuditEventIdGeneratorInterface` (ULID,
принятая в проекте схема id, фиксированное значение в тестах) биндится так же.

Смена генератора не переписывает существующие строки: у старых событий
остаются случайные id, новые становятся упорядоченными. И те, и другие — 32
hex-символа, поэтому ниже по течению менять нечего.

### Базовое логирование

```php
use Rasuvaeff\Yii3AuditLog\AuditActor;
use Rasuvaeff\Yii3AuditLog\AuditChangeSet;
use Rasuvaeff\Yii3AuditLog\AuditLogger;
use Rasuvaeff\Yii3AuditLog\AuditSubject;
use Rasuvaeff\Yii3AuditLog\InMemoryAuditWriter;

$logger = new AuditLogger(writer: $writer, clock: $clock);

$logger->logChange(
    actor: AuditActor::user(id: $userId, name: 'John'),
    subject: AuditSubject::of(type: 'order', id: (string) $orderId),
    changes: AuditChangeSet::fromArrays(
        old: ['status' => 'new', 'total' => 0],
        new: ['status' => 'paid', 'total' => 99.95],
    ),
);
```

### Реализация writer-а

```php
use Rasuvaeff\Yii3AuditLog\AuditEvent;
use Rasuvaeff\Yii3AuditLog\AuditWriter;

final readonly class DbAuditWriter implements AuditWriter
{
    public function write(AuditEvent $event): void
    {
        // INSERT INTO audit_log ...
        // $event->getId(), $event->getActor(), $event->getAction(),
        // $event->getSubject(), $event->getChangeSet(), $event->getOccurredAt()
    }
}
```

### Маскирование чувствительных значений

```php
use Rasuvaeff\Yii3AuditLog\AuditLogger;
use Rasuvaeff\Yii3AuditLog\SensitiveValueMasker;

$logger = new AuditLogger(
    writer: $writer,
    clock: $clock,
    masker: new SensitiveValueMasker(), // masks password, secret, token, api_key, credit_card
);

// Custom sensitive keys:
$masker = new SensitiveValueMasker(sensitiveKeys: ['ssn', 'pin', 'password']);
```

### Системный actor

```php
$logger->logCreate(
    actor: AuditActor::system(),
    subject: AuditSubject::of(type: 'config', id: 'smtp'),
    changes: AuditChangeSet::fromArrays(old: [], new: ['host' => 'mail.example.com']),
);
```

### Метаданные запроса

```php
use Rasuvaeff\Yii3AuditLog\AuditMetadata;

$logger->logChange(
    actor: $actor,
    subject: $subject,
    changes: $changes,
    metadata: new AuditMetadata(
        requestId: $request->getHeaderLine('X-Request-Id'),
        ip: $request->getServerParams()['REMOTE_ADDR'] ?? null,
        userAgent: $request->getHeaderLine('User-Agent'),
    ),
);
```

## API-справочник

### AuditLogger

| Метод | Описание |
|---|---|
| `__construct(writer, clock, masker?, skipEmptyChangeSets?, idGenerator?)` | По умолчанию: skip empty sets = true, генератор id = `RandomHexIdGenerator` |
| `log(actor, action, subject, changes, metadata?)` | Универсальная запись |
| `logCreate(actor, subject, changes, metadata?)` | action = `'create'` |
| `logChange(actor, subject, changes, metadata?)` | action = `'update'` |
| `logDelete(actor, subject, changes, metadata?)` | action = `'delete'` |

### AuditEventIdGeneratorInterface

| Реализация | Что выдаёт |
|---|---|
| `RandomHexIdGenerator` (по умолчанию) | 32 hex-символа, 128 случайных бит |
| `Uuid7IdGenerator` | UUIDv7 в виде 32 hex-символов (нужен `symfony/uid`) |

### AuditActor

| Метод | Описание |
|---|---|
| `::user(id, name?)` | Actor-пользователь |
| `::system()` | Системный actor (id = null) |
| `getType()` | `'user'`, `'system'` или пользовательский |
| `getId()` | `?string` |
| `getName()` | `?string` |
| `isSystem()` | `bool` |

### AuditSubject

| Метод | Описание |
|---|---|
| `::of(type, id)` | Фабрика |
| `getType()` | Тип ресурса |
| `getId()` | Идентификатор ресурса |

### AuditChangeSet

| Метод | Описание |
|---|---|
| `::fromArrays(old, new)` | Вычисляет diff; включаются только изменившиеся поля |
| `::empty()` | Пустой change set |
| `getChanges()` | `list<AuditChange>` |
| `isEmpty()` | `bool` |
| `count()` | Количество изменений |

### AuditChange

| Метод | Описание |
|---|---|
| `getField()` | Имя поля |
| `getOldValue()` | `mixed` |
| `getNewValue()` | `mixed` |

### SensitiveValueMasker

| Метод | Описание |
|---|---|
| `__construct(sensitiveKeys?)` | По умолчанию: `password, secret, token, api_key, credit_card` |
| `mask(array)` | Возвращает массив с замаскированными чувствительными значениями (`***`) |
| `maskChangeSet(AuditChangeSet)` | Возвращает новый `AuditChangeSet` с замаскированными значениями |

## Безопасность

- Маскирование применяется внутри `AuditLogger` до того, как событие попадает в
  writer — секреты никогда не доходят до хранилища.
- Маскирование нечувствительно к регистру: `Password`, `PASSWORD`, `password`
  маскируются одинаково.
- DB-реализации writer-а обязаны использовать параметризованные запросы.

## Примеры

Полные примеры использования — см. [examples/](examples/).

## Разработка

```bash
make install
make build
make cs-fix
make test
make test-coverage
make mutation
make release-check
```

`make test-coverage` и `make mutation` поднимают `pcov` внутри контейнера
`composer:2`, потому что в базовом образе нет драйвера покрытия.

## Лицензия

BSD-3-Clause. См. [LICENSE.md](LICENSE.md).
