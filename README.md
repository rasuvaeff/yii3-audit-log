# rasuvaeff/yii3-audit-log

[![Stable Version](https://poser.pugx.org/rasuvaeff/yii3-audit-log/v/stable)](https://packagist.org/packages/rasuvaeff/yii3-audit-log)
[![Total Downloads](https://poser.pugx.org/rasuvaeff/yii3-audit-log/downloads)](https://packagist.org/packages/rasuvaeff/yii3-audit-log)
[![Build](https://github.com/rasuvaeff/yii3-audit-log/actions/workflows/build.yml/badge.svg)](https://github.com/rasuvaeff/yii3-audit-log/actions)
[![Static analysis](https://github.com/rasuvaeff/yii3-audit-log/actions/workflows/static-analysis.yml/badge.svg)](https://github.com/rasuvaeff/yii3-audit-log/actions)
[![Psalm Level](https://shepherd.dev/github/rasuvaeff/yii3-audit-log/level.svg)](https://shepherd.dev/github/rasuvaeff/yii3-audit-log)
[![PHP](https://img.shields.io/packagist/dependency-v/rasuvaeff/yii3-audit-log/php)](https://packagist.org/packages/rasuvaeff/yii3-audit-log)
[![License](https://poser.pugx.org/rasuvaeff/yii3-audit-log/license)](https://packagist.org/packages/rasuvaeff/yii3-audit-log)

Audit trail for Yii3 applications: who changed what and when, with sensitive
value masking. Stateless core — bring your own writer (DB adapter lives in a
separate package).

> Using an AI coding assistant? [llms.txt](llms.txt) has a compact API reference you can use.

## Requirements

- PHP 8.3+
- `psr/clock` ^1.0

## Installation

```bash
composer require rasuvaeff/yii3-audit-log
```

## Yii3 config-plugin

The package ships `config/di.php` and `config/params.php` via config-plugin.
It wires `AuditLogger` and `SensitiveValueMasker`, but intentionally does not
bind `AuditWriter` or `Psr\Clock\ClockInterface`. Install exactly one writer
adapter or bind `AuditWriter` in your application config:

```php
use Psr\Clock\ClockInterface;
use Rasuvaeff\Yii3AuditLog\AuditWriter;

return [
    AuditWriter::class => MyAuditWriter::class,
    ClockInterface::class => MyClock::class,
];
```

Default params:

```php
return [
    'rasuvaeff/yii3-audit-log' => [
        'sensitiveKeys' => ['password', 'secret', 'token', 'api_key', 'credit_card'],
        'skipEmptyChangeSets' => true,
    ],
];
```

## Usage

### Basic logging

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

### Implementing a writer

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

### Sensitive value masking

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

### System actor

```php
$logger->logCreate(
    actor: AuditActor::system(),
    subject: AuditSubject::of(type: 'config', id: 'smtp'),
    changes: AuditChangeSet::fromArrays(old: [], new: ['host' => 'mail.example.com']),
);
```

### Request metadata

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

## API reference

### AuditLogger

| Method | Description |
|---|---|
| `__construct(writer, clock, masker?, skipEmptyChangeSets?)` | Default: skip empty sets = true |
| `log(actor, action, subject, changes, metadata?)` | Generic log |
| `logCreate(actor, subject, changes, metadata?)` | action = `'create'` |
| `logChange(actor, subject, changes, metadata?)` | action = `'update'` |
| `logDelete(actor, subject, changes, metadata?)` | action = `'delete'` |

### AuditActor

| Method | Description |
|---|---|
| `::user(id, name?)` | User actor |
| `::system()` | System actor (id = null) |
| `getType()` | `'user'`, `'system'`, or custom |
| `getId()` | `?string` |
| `getName()` | `?string` |
| `isSystem()` | `bool` |

### AuditSubject

| Method | Description |
|---|---|
| `::of(type, id)` | Factory |
| `getType()` | Resource type |
| `getId()` | Resource ID |

### AuditChangeSet

| Method | Description |
|---|---|
| `::fromArrays(old, new)` | Computes diff; only changed fields included |
| `::empty()` | Empty change set |
| `getChanges()` | `list<AuditChange>` |
| `isEmpty()` | `bool` |
| `count()` | Number of changes |

### AuditChange

| Method | Description |
|---|---|
| `getField()` | Field name |
| `getOldValue()` | `mixed` |
| `getNewValue()` | `mixed` |

### SensitiveValueMasker

| Method | Description |
|---|---|
| `__construct(sensitiveKeys?)` | Default: `password, secret, token, api_key, credit_card` |
| `mask(array)` | Returns array with sensitive values replaced by `***` |
| `maskChangeSet(AuditChangeSet)` | Returns new `AuditChangeSet` with masked values |

## Security

- Masker is applied inside `AuditLogger` before the event reaches the writer — secrets never reach storage.
- Masking is case-insensitive: `Password`, `PASSWORD`, `password` all masked.
- DB writer implementations must use parameterized queries.

## Examples

See [examples/](examples/) for complete usage examples.

## Development

```bash
make install
make build
make cs-fix
make test
make test-coverage
make mutation
make release-check
```

`make test-coverage` and `make mutation` bootstrap `pcov` inside the
`composer:2` container because the base image has no coverage driver.

## License

BSD-3-Clause. See [LICENSE.md](LICENSE.md).
