# AGENTS.md — yii3-audit-log

Guidance for AI agents working on this package. Read before changing code.

## What this is

`rasuvaeff/yii3-audit-log` provides an audit trail for Yii3 applications:
records who changed what and when, with optional sensitive value masking.
Namespace: `Rasuvaeff\Yii3AuditLog`.

Public API:
- `AuditLogger` — main entry point; `log()`, `logCreate()`, `logChange()`, `logDelete()`
- `AuditEvent` — immutable event: id, actor, action, subject, changeSet, occurredAt, metadata
- `AuditActor` — who acted; `::user(id, name?)`, `::system()`
- `AuditSubject` — what was affected; `::of(type, id)`
- `AuditChangeSet` — diff of changes; `::fromArrays(old, new)`, `::empty()`
- `AuditChange` — single field change: field, oldValue, newValue
- `AuditMetadata` — optional request context: requestId, ip, userAgent
- `AuditWriter` — writer interface
- `NullAuditWriter` — no-op writer
- `InMemoryAuditWriter` — test writer
- `SensitiveValueMasker` — masks sensitive keys (`password`, `secret`, etc.)

DB writer lives in a separate adapter package.

Yii3 config-plugin wiring: `config/di.php` binds only `AuditLogger` and
`SensitiveValueMasker`. It must never bind `AuditWriter` or `ClockInterface`;
those are owned by exactly one backend package or the application.

## Golden rules

1. **Verification is mandatory.** Never claim "done" without a fresh green
   `composer build`. "Should work" does not count.
2. **No suppressions.** No `@psalm-suppress`, no baseline. Fix the root cause.
3. **Masker runs before writer.** Apply `SensitiveValueMasker` inside `AuditLogger::log()`
   before passing the event to the writer.
4. **DI one-source rule.** Core config-plugin wiring must not bind `AuditWriter`
   or `ClockInterface`; bind them in a backend package or app config.
5. **Preserve the public contract.** Update README + tests with any API change.

## Commands

No PHP/Composer on the host — run in Docker via the `composer:2` image.

```bash
docker run --rm -v "$PWD":/app -w /app composer:2 composer build
docker run --rm -v "$PWD":/app -w /app composer:2 composer cs:fix
docker run --rm -v "$PWD":/app -w /app composer:2 composer psalm
docker run --rm -v "$PWD":/app -w /app composer:2 composer test
docker run --rm -v "$PWD":/app -w /app composer:2 composer release-check
```

Or with Make:

```bash
make build
make cs-fix
make psalm
make test
make test-coverage
make mutation
make release-check
```

`composer.lock` is gitignored (library).
`make test-coverage` and `make mutation` bootstrap `pcov` inside the
`composer:2` container because the base image has no coverage driver.

## Invariants & gotchas

- `AuditChangeSet::fromArrays()` only includes fields where old !== new (strict).
- `AuditLogger` skips empty change sets by default (`skipEmptyChangeSets: true`).
- `SensitiveValueMasker` compares keys case-insensitively; masked value is `'***'`.
- Default sensitive keys: `password`, `secret`, `token`, `api_key`, `credit_card`.
- `AuditEvent` id is auto-generated (32-char hex) by `AuditLogger::log()`.
- `occurredAt` timestamp comes from injected `ClockInterface`.
- Code: `declare(strict_types=1)`, `final readonly class`, `#[\Override]`, explicit types.

- `examples/` is part of the public contract: keep scripts runnable and update
  `examples/README.md` when example usage changes.

## When you finish

- Update `README.md` (and `examples/` if usage changed); update `CHANGELOG.md`
  when releasing.
- Re-run `composer build`; if the change affects the public API or release
  process, also run `make release-check`. Paste the output.
