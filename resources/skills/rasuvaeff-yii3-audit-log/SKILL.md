---
name: rasuvaeff-yii3-audit-log
description: >-
  Record an audit trail (who changed what and when) in Yii3 apps with
  rasuvaeff/yii3-audit-log — AuditLogger, AuditActor, AuditSubject,
  AuditChangeSet, SensitiveValueMasker, AuditWriter. Use when writing,
  reviewing or debugging audit logging in a project that has this package
  installed, especially when secrets leak into audit records, events silently
  disappear, or DI wiring for AuditWriter/ClockInterface fails.
---

# rasuvaeff/yii3-audit-log

Stateless audit-trail core for Yii3: `AuditLogger` builds an immutable
`AuditEvent` (actor, action, subject, change set, timestamp, metadata) and
hands it to an `AuditWriter`. Namespace `Rasuvaeff\Yii3AuditLog\`.

## Safety rules — verify these on every change

1. **Masking is by field name and NOT recursive.** `SensitiveValueMasker`
   matches top-level keys only (case-insensitive; masked value is `'***'`).
   A secret nested inside an array value leaks into the audit record. Keep
   secrets in top-level fields, or extend the key list.

   ```php
   $masker->mask(['password' => 'x']);              // ['password' => '***']
   $masker->mask(['config' => ['password' => 'x']]); // leaks — not masked
   new SensitiveValueMasker(['password', 'secret', 'token', 'api_key', 'credit_card', 'ssn']);
   ```

2. **Default sensitive keys are only** `password`, `secret`, `token`,
   `api_key`, `credit_card`. Anything else (`refresh_token`? no — exact
   match, not substring) must be added explicitly via the constructor or the
   `sensitiveKeys` param of `rasuvaeff/yii3-audit-log` config.

3. **The masker runs inside `AuditLogger::log()` before the writer.** Never
   mask in a custom `AuditWriter` — by contract the event arriving at
   `write()` is already masked; double-masking or writer-side masking hides
   bugs and breaks the core guarantee.

4. **DI one-source rule.** The core config-plugin binds only `AuditLogger`
   and `SensitiveValueMasker`. `AuditWriter` and `Psr\Clock\ClockInterface`
   must be bound exactly once — by a backend package (e.g.
   `yii3-audit-log-db`) or by app config. Binding them in two places gives
   `yiisoft/config` "Duplicate key" at runtime.

5. **Production needs a persistent writer.** `NullAuditWriter` discards
   events, `InMemoryAuditWriter` is for tests only. An audit trail is not
   PSR-3 logging — do not route it to a rotating app log; use
   `rasuvaeff/yii3-audit-log-db` or your own durable writer.

6. **Empty change sets are silently skipped** (`skipEmptyChangeSets: true`
   by default), and `AuditChangeSet::fromArrays()` keeps only fields where
   `old !== new` (strict). "log() wrote nothing" for a no-op update is
   expected behavior, not a bug.

## Canonical usage

```php
use Rasuvaeff\Yii3AuditLog\{AuditActor, AuditChangeSet, AuditSubject};

$changes = AuditChangeSet::fromArrays(
    old: ['status' => 'new', 'total' => 0],
    new: ['status' => 'paid', 'total' => 99],
);

$logger->logChange(
    actor: AuditActor::user(id: '42', name: 'John'),
    subject: AuditSubject::of(type: 'order', id: '42'),
    changes: $changes,
);
// custom action:
$logger->log(actor: $actor, action: 'approve', subject: $subject, changes: $changes);
```

## Full API

The complete reference — `AuditLogger` constructor, `AuditEvent`/`AuditChange`/
`AuditMetadata` shapes, config-plugin params and app-level wiring — ships with
the package: read `vendor/rasuvaeff/yii3-audit-log/llms.txt` before guessing a
method or parameter name.
