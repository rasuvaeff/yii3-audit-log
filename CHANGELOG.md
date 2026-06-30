# Changelog

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

