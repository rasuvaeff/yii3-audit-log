<?php

declare(strict_types=1);

namespace Rasuvaeff\Yii3AuditLog\Tests;

use DateTimeImmutable;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Rasuvaeff\Yii3AuditLog\AuditActor;
use Rasuvaeff\Yii3AuditLog\AuditChangeSet;
use Rasuvaeff\Yii3AuditLog\AuditEvent;
use Rasuvaeff\Yii3AuditLog\AuditSubject;
use Rasuvaeff\Yii3AuditLog\NullAuditWriter;

#[CoversClass(NullAuditWriter::class)]
final class NullAuditWriterTest extends TestCase
{
    #[Test]
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

        // Must not throw
        $writer->write($event);

        $this->assertTrue(true);
    }
}
