<?php

declare(strict_types=1);

namespace Rasuvaeff\Yii3AuditLog;

/**
 * @api
 */
interface AuditWriter
{
    public function write(AuditEvent $event): void;
}
