<?php

declare(strict_types=1);

require __DIR__ . '/../vendor/autoload.php';

use DateTimeImmutable;
use Psr\Clock\ClockInterface;
use Rasuvaeff\Yii3AuditLog\AuditActor;
use Rasuvaeff\Yii3AuditLog\AuditChangeSet;
use Rasuvaeff\Yii3AuditLog\AuditLogger;
use Rasuvaeff\Yii3AuditLog\AuditSubject;
use Rasuvaeff\Yii3AuditLog\InMemoryAuditWriter;

$clock = new class implements ClockInterface {
    public function now(): DateTimeImmutable { return new DateTimeImmutable(); }
};

$writer = new InMemoryAuditWriter();
$logger = new AuditLogger(writer: $writer, clock: $clock);

$logger->logChange(
    actor: AuditActor::user(id: '1', name: 'Admin'),
    subject: AuditSubject::of(type: 'order', id: '42'),
    changes: AuditChangeSet::fromArrays(
        old: ['status' => 'new', 'total' => 0],
        new: ['status' => 'paid', 'total' => 99.95],
    ),
);

$logger->logCreate(
    actor: AuditActor::user(id: '1'),
    subject: AuditSubject::of(type: 'product', id: '10'),
    changes: AuditChangeSet::fromArrays(
        old: [],
        new: ['name' => 'Widget', 'price' => 29.99],
    ),
);

// Empty change set is skipped
$logger->logChange(
    actor: AuditActor::user(id: '1'),
    subject: AuditSubject::of(type: 'order', id: '42'),
    changes: AuditChangeSet::fromArrays(
        old: ['status' => 'paid'],
        new: ['status' => 'paid'],
    ),
);

echo "Events recorded: {$writer->count()}\n\n";

foreach ($writer->getEvents() as $event) {
    echo "[{$event->getAction()}] {$event->getSubject()->getType()}#{$event->getSubject()->getId()}";
    echo " by actor={$event->getActor()->getType()}:{$event->getActor()->getId()}\n";

    foreach ($event->getChangeSet()->getChanges() as $change) {
        echo "  {$change->getField()}: {$change->getOldValue()} → {$change->getNewValue()}\n";
    }
}
