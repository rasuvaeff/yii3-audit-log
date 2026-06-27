<?php

declare(strict_types=1);

namespace Rasuvaeff\Yii3AuditLog\Tests;

use DateTimeImmutable;
use InvalidArgumentException;
use Rasuvaeff\Yii3AuditLog\AuditActor;
use Rasuvaeff\Yii3AuditLog\AuditChangeSet;
use Rasuvaeff\Yii3AuditLog\AuditEvent;
use Rasuvaeff\Yii3AuditLog\AuditMetadata;
use Rasuvaeff\Yii3AuditLog\AuditSubject;
use Testo\Assert;
use Testo\Codecov\Covers;
use Testo\Lifecycle\BeforeTest;
use Testo\Test;

#[Test]
#[Covers(AuditEvent::class)]
final class AuditEventTest
{
    private AuditActor $actor;
    private AuditSubject $subject;
    private AuditChangeSet $changeSet;
    private DateTimeImmutable $occurredAt;

    #[BeforeTest]
    public function setUp(): void
    {
        $this->actor = AuditActor::user(id: '42', name: 'Admin');
        $this->subject = AuditSubject::of(type: 'order', id: '100');
        $this->changeSet = AuditChangeSet::empty();
        $this->occurredAt = new DateTimeImmutable('2026-06-20 12:00:00');
    }

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

        Assert::same($event->getId(), 'event-1');
        Assert::same($event->getActor(), $this->actor);
        Assert::same($event->getAction(), 'update');
        Assert::same($event->getSubject(), $this->subject);
        Assert::same($event->getChangeSet(), $this->changeSet);
        Assert::same($event->getOccurredAt(), $this->occurredAt);
        Assert::same($event->getMetadata(), $metadata);
    }

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

        Assert::null($event->getMetadata());
    }

    public function throwsOnEmptyId(): void
    {
        try {
            new AuditEvent(
                id: '',
                actor: $this->actor,
                action: 'update',
                subject: $this->subject,
                changeSet: $this->changeSet,
                occurredAt: $this->occurredAt,
            );
            Assert::fail('Expected InvalidArgumentException');
        } catch (InvalidArgumentException $e) {
            Assert::string($e->getMessage())->contains('Event id must not be empty');
        }
    }

    public function throwsOnEmptyAction(): void
    {
        try {
            new AuditEvent(
                id: 'event-1',
                actor: $this->actor,
                action: '',
                subject: $this->subject,
                changeSet: $this->changeSet,
                occurredAt: $this->occurredAt,
            );
            Assert::fail('Expected InvalidArgumentException');
        } catch (InvalidArgumentException $e) {
            Assert::string($e->getMessage())->contains('Event action must not be empty');
        }
    }
}
