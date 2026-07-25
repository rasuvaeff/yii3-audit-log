<?php

declare(strict_types=1);

namespace Rasuvaeff\Yii3AuditLog;

/**
 * Produces the identifier of an audit event — the primary key of the audit
 * table. The shipped {@see RandomHexIdGenerator} keeps the historical format
 * (32 random hex characters); {@see Uuid7IdGenerator} makes ids monotonic,
 * which matters for an append-only table: a random primary key scatters
 * InnoDB inserts across pages, while a time-ordered one appends and also
 * gives a cheap tie-breaker for events within the same second.
 *
 * @api
 */
interface AuditEventIdGeneratorInterface
{
    /**
     * @return non-empty-string
     */
    public function generate(): string;
}
