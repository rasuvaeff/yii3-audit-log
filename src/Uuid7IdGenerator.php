<?php

declare(strict_types=1);

namespace Rasuvaeff\Yii3AuditLog;

use Symfony\Component\Uid\Uuid;

/**
 * UUIDv7 rendered as 32 hex characters — same width as the default format, so
 * it drops into the existing `VARCHAR(32)` column of rasuvaeff/yii3-audit-log-db
 * with no migration, while ids become time-ordered.
 *
 * Dashes are stripped deliberately: the canonical 36-character form does not
 * fit that column, and the id was never dash-separated to begin with.
 * symfony/uid keeps UUIDv7 monotonic even within one millisecond, so string
 * comparison of two ids reflects their real order.
 *
 * Requires `symfony/uid` — a `suggest`, not a hard dependency: install it only
 * if you bind this generator.
 *
 * @api
 */
final readonly class Uuid7IdGenerator implements AuditEventIdGeneratorInterface
{
    #[\Override]
    public function generate(): string
    {
        /** @var non-empty-string */
        return str_replace('-', '', Uuid::v7()->toRfc4122());
    }
}
