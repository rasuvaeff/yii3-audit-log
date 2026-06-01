<?php

declare(strict_types=1);

namespace Rasuvaeff\Yii3AuditLog\Tests;

use DateTimeImmutable;
use InvalidArgumentException;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Rasuvaeff\Yii3AuditLog\AuditActor;
use Rasuvaeff\Yii3AuditLog\AuditChangeSet;
use Rasuvaeff\Yii3AuditLog\AuditEvent;
use Rasuvaeff\Yii3AuditLog\AuditMetadata;
use Rasuvaeff\Yii3AuditLog\AuditSubject;

#[CoversClass(AuditEvent::class)]
final class AuditEventTest extends TestCase
{
    private AuditActor $actor;
    private AuditSubject $subject;
    private AuditChangeSet $changeSet;
    private DateTimeImmutable $occurredAt;

    #[\Override]
    protected function setUp(): void
    {
        $this->actor = AuditActor::user(id: '42', name: 'Admin');
        $this->subject = AuditSubject::of(type: 'order', id: '100');
        $this->changeSet = AuditChangeSet::empty();
        $this->occurredAt = new DateTimeImmutable('2026-06-20 12:00:00');
    }

    #[Test]
    public function holdsValues(): void
    {
        $metadata = new AuditMetadata(requestId: 'req-1');

        $event = new AuditEvent(
            id: 'event-1',
            actor: $this->actor,
            action: 'update',
            subject: $this->subject,
            changeSet: $this->changeSet,
            occurredAt: $this->occurredAt,
            metadata: $metadata,
        );

        $this->assertSame('event-1', $event->getId());
        $this->assertSame($this->actor, $event->getActor());
        $this->assertSame('update', $event->getAction());
        $this->assertSame($this->subject, $event->getSubject());
        $this->assertSame($this->changeSet, $event->getChangeSet());
        $this->assertSame($this->occurredAt, $event->getOccurredAt());
        $this->assertSame($metadata, $event->getMetadata());
    }

    #[Test]
    public function metadataDefaultsToNull(): void
    {
        $event = new AuditEvent(
            id: 'event-1',
            actor: $this->actor,
            action: 'update',
            subject: $this->subject,
            changeSet: $this->changeSet,
            occurredAt: $this->occurredAt,
        );

        $this->assertNull($event->getMetadata());
    }

    #[Test]
    public function throwsOnEmptyId(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Event id must not be empty');

        new AuditEvent(
            id: '',
            actor: $this->actor,
            action: 'update',
            subject: $this->subject,
            changeSet: $this->changeSet,
            occurredAt: $this->occurredAt,
        );
    }

    #[Test]
    public function throwsOnEmptyAction(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Event action must not be empty');

        new AuditEvent(
            id: 'event-1',
            actor: $this->actor,
            action: '',
            subject: $this->subject,
            changeSet: $this->changeSet,
            occurredAt: $this->occurredAt,
        );
    }
}
