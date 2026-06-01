<?php

declare(strict_types=1);

use Psr\Clock\ClockInterface;
use Rasuvaeff\Yii3AuditLog\AuditLogger;
use Rasuvaeff\Yii3AuditLog\AuditWriter;
use Rasuvaeff\Yii3AuditLog\SensitiveValueMasker;

/** @var array $params */

return [
    SensitiveValueMasker::class => [
        '__construct()' => [
            'sensitiveKeys' => $params['rasuvaeff/yii3-audit-log']['sensitiveKeys'],
        ],
    ],
    AuditLogger::class => static fn (
        AuditWriter $writer,
        ClockInterface $clock,
        SensitiveValueMasker $masker,
    ): AuditLogger => new AuditLogger(
        writer: $writer,
        clock: $clock,
        masker: $masker,
        skipEmptyChangeSets: $params['rasuvaeff/yii3-audit-log']['skipEmptyChangeSets'],
    ),
];
