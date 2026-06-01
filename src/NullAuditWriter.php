<?php

declare(strict_types=1);

namespace Rasuvaeff\Yii3AuditLog;

/**
 * @api
 */
final readonly class NullAuditWriter implements AuditWriter
{
    #[\Override]
    public function write(AuditEvent $event): void {}
}
