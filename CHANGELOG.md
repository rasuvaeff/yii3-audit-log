# Changelog

## 1.2.0 — 2026-07-25

- The audit event id is now produced by `AuditEventIdGeneratorInterface`
  instead of being hard-coded. The default `RandomHexIdGenerator` keeps the
  historical format (32 random hex characters), so nothing changes unless the
  interface is rebound; `AuditLogger`'s new `idGenerator` argument is last and
  optional.
- Add `Uuid7IdGenerator`: UUIDv7 rendered as 32 hex characters (dashes
  stripped, so it fits `yii3-audit-log-db`'s existing `VARCHAR(32)` column with
  no migration). Time-ordered ids stop a random primary key from scattering
  InnoDB inserts across pages in an append-only, ever-growing table, and give
  a tie-breaker for events within the same second. Requires `symfony/uid`
  (a `suggest`, not a hard dependency).
- `config/di.php` binds `AuditEventIdGeneratorInterface` to
  `RandomHexIdGenerator`; an application switches generators with one line in
  its own DI config.

## 1.1.0 — 2026-07-25

- Ship an AI agent skill (`resources/skills/rasuvaeff-yii3-audit-log/SKILL.md` +
  `extra.skills` in composer.json): projects using the `llm/skills` Composer
  plugin get the skill synced into `.agents/skills/` automatically on install.

## 1.0.2 — 2026-06-30

- Add `/benchmarks` and `/Makefile` to `.gitattributes` export-ignore.

All notable changes to this project will be documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.1.0/),
and this project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

## 1.0.1 — 2026-06-27

- Migrate test suite from PHPUnit to Testo. Internal change, no public API impact.

## 1.0.0 — 2026-06-20

- `AuditLogger` — main entry point: `log()`, `logCreate()`, `logChange()`, `logDelete()`.
- `AuditEvent` immutable value object: id, actor, action, subject, changeSet, occurredAt, metadata.
- `AuditActor` — who acted: `::user(id, name?)`, `::system()`.
- `AuditSubject` — what was affected: `::of(type, id)`.
- `AuditChangeSet` — diff from `::fromArrays(old, new)`; only changed fields included.
- `AuditChange` — single field change with old and new value.
- `AuditMetadata` — optional request context: requestId, ip, userAgent.
- `SensitiveValueMasker` — masks sensitive keys (`password`, `secret`, `token`, etc.) to `***`.
- `NullAuditWriter` (no-op) and `InMemoryAuditWriter` (for tests).
- Yii3 config-plugin wiring for `AuditLogger` and `SensitiveValueMasker`.
- DB writer deferred to `yii3-audit-log-db`.

