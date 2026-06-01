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
use Rasuvaeff\Yii3AuditLog\SensitiveValueMasker;

$clock = new class implements ClockInterface {
    public function now(): DateTimeImmutable { return new DateTimeImmutable(); }
};

$writer = new InMemoryAuditWriter();
$logger = new AuditLogger(
    writer: $writer,
    clock: $clock,
    masker: new SensitiveValueMasker(),
);

$logger->logChange(
    actor: AuditActor::user(id: '1'),
    subject: AuditSubject::of(type: 'user', id: '7'),
    changes: AuditChangeSet::fromArrays(
        old: ['email' => 'old@example.com', 'password' => 'old-hash', 'token' => 'tok-old'],
        new: ['email' => 'new@example.com', 'password' => 'new-hash', 'token' => 'tok-new'],
    ),
);

$event = $writer->getEvents()[0];

echo "Changes after masking:\n";
foreach ($event->getChangeSet()->getChanges() as $change) {
    echo "  {$change->getField()}: {$change->getOldValue()} → {$change->getNewValue()}\n";
}
