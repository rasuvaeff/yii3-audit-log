<?php

declare(strict_types=1);

namespace Rasuvaeff\Yii3AuditLog\Benchmarks;

use DateTimeImmutable;
use Rasuvaeff\Yii3AuditLog\AuditActor;
use Rasuvaeff\Yii3AuditLog\AuditChangeSet;
use Rasuvaeff\Yii3AuditLog\AuditEvent;
use Rasuvaeff\Yii3AuditLog\AuditSubject;
use Testo\Bench;

final class AuditLogBench
{
    #[Bench(
        callables: [
            'with-changes' => [self::class, 'buildWithChanges'],
        ],
        calls: 1_000,
        iterations: 10,
    )]
    public static function buildMinimal(): AuditEvent
    {
        return new AuditEvent(
            id: 'evt-001',
            actor: new AuditActor(type: 'user', id: '42', name: 'Alice'),
            action: 'update',
            subject: new AuditSubject(type: 'order', id: '99'),
            changeSet: AuditChangeSet::empty(),
            occurredAt: new DateTimeImmutable('2024-01-01T12:00:00Z'),
        );
    }

    public static function buildWithChanges(): AuditEvent
    {
        $changeSet = AuditChangeSet::fromArrays(
            old: ['status' => 'pending', 'amount' => 100, 'note' => null, 'tag' => 'new', 'priority' => 1],
            new: ['status' => 'shipped', 'amount' => 100, 'note' => 'express', 'tag' => 'new', 'priority' => 2],
        );

        return new AuditEvent(
            id: 'evt-001',
            actor: new AuditActor(type: 'user', id: '42', name: 'Alice'),
            action: 'update',
            subject: new AuditSubject(type: 'order', id: '99'),
            changeSet: $changeSet,
            occurredAt: new DateTimeImmutable('2024-01-01T12:00:00Z'),
        );
    }
}
