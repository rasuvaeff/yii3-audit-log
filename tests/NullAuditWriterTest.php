<?php

declare(strict_types=1);

namespace Rasuvaeff\Yii3AuditLog\Tests;

use DateTimeImmutable;
use Rasuvaeff\Yii3AuditLog\AuditActor;
use Rasuvaeff\Yii3AuditLog\AuditChangeSet;
use Rasuvaeff\Yii3AuditLog\AuditEvent;
use Rasuvaeff\Yii3AuditLog\AuditSubject;
use Rasuvaeff\Yii3AuditLog\NullAuditWriter;
use Testo\Assert;
use Testo\Codecov\Covers;
use Testo\Test;

#[Test]
#[Covers(NullAuditWriter::class)]
final class NullAuditWriterTest
{
    public function writeDoesNothing(): void
    {
        $writer = new NullAuditWriter();

        $event = new AuditEvent(
            id: 'evt-1',
            actor: AuditActor::system(),
            action: 'update',
            subject: AuditSubject::of(type: 'order', id: '1'),
            changeSet: AuditChangeSet::empty(),
            occurredAt: new DateTimeImmutable(),
        );

        $writer->write($event);

        Assert::true(true);
    }
}
