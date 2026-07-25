<?php

declare(strict_types=1);

use Psr\Clock\ClockInterface;
use Rasuvaeff\Yii3AuditLog\AuditEventIdGeneratorInterface;
use Rasuvaeff\Yii3AuditLog\AuditLogger;
use Rasuvaeff\Yii3AuditLog\AuditWriter;
use Rasuvaeff\Yii3AuditLog\RandomHexIdGenerator;
use Rasuvaeff\Yii3AuditLog\SensitiveValueMasker;

/** @var array $params */

return [
    SensitiveValueMasker::class => [
        '__construct()' => [
            'sensitiveKeys' => $params['rasuvaeff/yii3-audit-log']['sensitiveKeys'],
        ],
    ],
    // the historical id format; an application swaps it by rebinding this
    // interface (application definitions win over vendor ones)
    AuditEventIdGeneratorInterface::class => RandomHexIdGenerator::class,
    AuditLogger::class => static fn (
        AuditWriter $writer,
        ClockInterface $clock,
        SensitiveValueMasker $masker,
        AuditEventIdGeneratorInterface $idGenerator,
    ): AuditLogger => new AuditLogger(
        writer: $writer,
        clock: $clock,
        masker: $masker,
        skipEmptyChangeSets: $params['rasuvaeff/yii3-audit-log']['skipEmptyChangeSets'],
        idGenerator: $idGenerator,
    ),
];
