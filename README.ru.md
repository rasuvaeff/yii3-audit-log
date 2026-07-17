# rasuvaeff/yii3-audit-log
[![Stable Version](https://poser.pugx.org/rasuvaeff/yii3-audit-log/v/stable)](https://packagist.org/packages/rasuvaeff/yii3-audit-log)
[![Total Downloads](https://poser.pugx.org/rasuvaeff/yii3-audit-log/downloads)](https://packagist.org/packages/rasuvaeff/yii3-audit-log)
[![Build](https://github.com/rasuvaeff/yii3-audit-log/actions/workflows/build.yml/badge.svg)](https://github.com/rasuvaeff/yii3-audit-log/actions)
[![Static analysis](https://github.com/rasuvaeff/yii3-audit-log/actions/workflows/static-analysis.yml/badge.svg)](https://github.com/rasuvaeff/yii3-audit-log/actions)
[![Psalm Level](https://shepherd.dev/github/rasuvaeff/yii3-audit-log/level.svg)](https://shepherd.dev/github/rasuvaeff/yii3-audit-log)
[![PHP](https://img.shields.io/packagist/dependency-v/rasuvaeff/yii3-audit-log/php)](https://packagist.org/packages/rasuvaeff/yii3-audit-log)
[![License](https://poser.pugx.org/rasuvaeff/yii3-audit-log/license)](https://packagist.org/packages/rasuvaeff/yii3-audit-log)
Журнал аудита приложений Yii3: кто, что и когда изменил, с деликатной маскировкой значений
. Ядро без сохранения состояния — используйте свой собственный модуль записи (адаптер БД находится в отдельном пакете
).

 > Используете помощника по программированию с искусственным интеллектом? [llms.txt](llms.txt) содержит компактную ссылку на API, которую вы можете использовать. @@ЛИНИЯ@@
## Требования
- PHP 8.3+
 - `psr/lock` ^1.0

## Установка
```bash
composer require rasuvaeff/yii3-audit-log
```
## Конфигурационный плагин Yii3
Пакет поставляется с `config/di.php` и `config/params.php` через config-plugin.
 Он подключает `AuditLogger` и `SensitiveValueMasker`, но намеренно
 не связывает `AuditWriter` или `Psr\Clock\ClockInterface`. Установите ровно один адаптер записи
 или привяжите AuditWriter в конфигурации вашего приложения:

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
### Базовое ведение журнала
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
### Реализация писателя
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
### Чувствительная маскировка значений
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
### Системный актер
```php
$logger->logCreate(
    actor: AuditActor::system(),
    subject: AuditSubject::of(type: 'config', id: 'smtp'),
    changes: AuditChangeSet::fromArrays(old: [], new: ['host' => 'mail.example.com']),
);
```
### Запросить метаданные
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
## Справочник по API
### Аудитлогер
| Метод | Описание |
 |---|---|
 | `__construct(писатель, часы, маскер?,skipEmptyChangeSets?)` | По умолчанию: пропускать пустые наборы = true |
 | `log(субъект, действие, предмет, изменения, метаданные?)` | Общий журнал |
 | `logCreate(субъект, субъект, изменения, метаданные?)` | действие = `'создать'` |
 | `logChange(субъект, субъект, изменения, метаданные?)` | действие = `'обновить'` |
 | `logDelete(субъект, субъект, изменения, метаданные?)` | действие = `'удалить'` | @@ЛИНИЯ@@
### АудитАктер
| Метод | Описание |
 |---|---|
 | `::user(id, name?)` | Пользователь-актер |
 | `::system()` | Системный актер (id = null) |
 | `getType()` | `'пользователь'`, `'система'` или пользовательский |
 | `getId()` | `?строка` |
 | `getName()` | `?строка` |
 | `isSystem()` | `бул` | @@ЛИНИЯ@@
### АудитТема
| Метод | Описание |
 |---|---|
 | `::of(type, id)` | Фабрика |
 | `getType()` | Тип ресурса |
 | `getId()` | Идентификатор ресурса | @@ЛИНИЯ@@
### АудитЧанжеСет
| Метод | Описание |
 |---|---|
 | `::fromArrays(старый, новый)` | Вычисляет разницу; включены только измененные поля |
 | `::empty()` | Пустой набор мелочи |
 | `getChanges()` | `list<AuditChange>` |
 | `isEmpty()` | `бул` |
 | `счет()` | Количество изменений | @@ЛИНИЯ@@
### АудитИзменения
| Метод | Описание |
 |---|---|
 | `getField()` | Имя поля |
 | `getOldValue()` | `смешанный` |
 | `getNewValue()` | `смешанный` | @@ЛИНИЯ@@
### SensitiveValueMasker
| Метод | Описание |
 |---|---|
 | `__construct(sensitiveKeys?)` | По умолчанию: `пароль, секрет, токен, api_key, кредитная_карта` |
 | `маска(массив)` | Возвращает массив, в котором чувствительные значения заменены на `***` |
 | `maskChangeSet(AuditChangeSet)` | Возвращает новый AuditChangeSet с замаскированными значениями | @@ЛИНИЯ@@
## Безопасность
— Маскер применяется внутри AuditLogger до того, как событие достигает записывающего устройства — секреты никогда не достигают хранилища.
 - Маскирование не чувствительно к регистру: `Пароль`, `ПАРОЛЬ`, `пароль` все замаскированы.
 - Реализации средства записи БД должны использовать параметризованные запросы. @@ЛИНИЯ@@
## Примеры
См. [examples/](examples/) для полных примеров использования. @@ЛИНИЯ@@
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
`make test-coverage` и `makemutation` загружают `pcov` внутри контейнера
 `composer:2`, поскольку базовый образ не имеет драйвера покрытия. @@ЛИНИЯ@@
## Лицензия
BSD-3-пункт. См. [LICENSE.md](LICENSE.md).
