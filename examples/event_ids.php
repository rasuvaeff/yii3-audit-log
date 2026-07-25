<?php

declare(strict_types=1);

require __DIR__ . '/../vendor/autoload.php';

use Psr\Clock\ClockInterface;
use Rasuvaeff\Yii3AuditLog\AuditActor;
use Rasuvaeff\Yii3AuditLog\AuditChangeSet;
use Rasuvaeff\Yii3AuditLog\AuditEventIdGeneratorInterface;
use Rasuvaeff\Yii3AuditLog\AuditLogger;
use Rasuvaeff\Yii3AuditLog\AuditSubject;
use Rasuvaeff\Yii3AuditLog\InMemoryAuditWriter;
use Rasuvaeff\Yii3AuditLog\RandomHexIdGenerator;
use Rasuvaeff\Yii3AuditLog\Uuid7IdGenerator;

$clock = new class implements ClockInterface {
    public function now(): DateTimeImmutable
    {
        return new DateTimeImmutable();
    }
};

/**
 * In an application this is one line of DI config:
 * AuditEventIdGeneratorInterface::class => Uuid7IdGenerator::class
 */
$run = static function (ClockInterface $clock, AuditEventIdGeneratorInterface $generator): array {
    $writer = new InMemoryAuditWriter();
    $logger = new AuditLogger(writer: $writer, clock: $clock, idGenerator: $generator);

    for ($i = 1; $i <= 3; ++$i) {
        $logger->logChange(
            actor: AuditActor::user(id: '1', name: 'Admin'),
            subject: AuditSubject::of(type: 'order', id: (string) $i),
            changes: AuditChangeSet::fromArrays(old: ['status' => 'new'], new: ['status' => 'paid']),
        );
    }

    return array_map(static fn ($event): string => $event->getId(), $writer->getEvents());
};

foreach (['RandomHexIdGenerator (default)' => new RandomHexIdGenerator(), 'Uuid7IdGenerator' => new Uuid7IdGenerator()] as $label => $generator) {
    $ids = $run($clock, $generator);
    $sorted = $ids;
    sort($sorted);

    echo $label . ":\n";

    foreach ($ids as $id) {
        echo '  ' . $id . "\n";
    }

    // the point of UUIDv7: insertion order and sort order are the same, so the
    // primary key appends instead of scattering across InnoDB pages
    echo '  chronologically sortable: ' . ($ids === $sorted ? 'yes' : 'no') . "\n";
}
