<?php

declare(strict_types=1);

namespace Rasuvaeff\Yii3AuditLog\Tests;

use DateTimeImmutable;
use Rasuvaeff\Yii3AuditLog\AuditActor;
use Rasuvaeff\Yii3AuditLog\AuditChange;
use Rasuvaeff\Yii3AuditLog\AuditChangeSet;
use Rasuvaeff\Yii3AuditLog\AuditLogger;
use Rasuvaeff\Yii3AuditLog\AuditMetadata;
use Rasuvaeff\Yii3AuditLog\AuditSubject;
use Rasuvaeff\Yii3AuditLog\InMemoryAuditWriter;
use Rasuvaeff\Yii3AuditLog\SensitiveValueMasker;
use Testo\Assert;
use Testo\Codecov\Covers;
use Testo\Lifecycle\BeforeTest;
use Testo\Test;

#[Test]
#[Covers(AuditLogger::class)]
final class AuditLoggerTest
{
    private InMemoryAuditWriter $writer;
    private AuditLogger $logger;
    private AuditActor $actor;
    private AuditSubject $subject;

    #[BeforeTest]
    public function setUp(): void
    {
        $this->writer = new InMemoryAuditWriter();
        $this->logger = new AuditLogger(
            writer: $this->writer,
            clock: new StubClock(new DateTimeImmutable('2026-06-01 12:00:00')),
        );
        $this->actor = AuditActor::user(id: '1', name: 'Admin');
        $this->subject = AuditSubject::of(type: 'order', id: '42');
    }

    private function changeSet(): AuditChangeSet
    {
        return new AuditChangeSet([
            new AuditChange(field: 'status', oldValue: 'new', newValue: 'paid'),
        ]);
    }

    public function logWritesEvent(): void
    {
        $this->logger->log(
            actor: $this->actor,
            action: 'update',
            subject: $this->subject,
            changes: $this->changeSet(),
        );

        Assert::same($this->writer->count(), 1);
        $event = $this->writer->getEvents()[0];
        Assert::same($event->getAction(), 'update');
        Assert::same($event->getSubject()->getType(), 'order');
        Assert::same($event->getSubject()->getId(), '42');
        Assert::same($event->getOccurredAt()->format('Y-m-d H:i:s'), '2026-06-01 12:00:00');
    }

    public function logChangeWritesUpdateAction(): void
    {
        $this->logger->logChange(
            actor: $this->actor,
            subject: $this->subject,
            changes: $this->changeSet(),
        );

        Assert::same($this->writer->getEvents()[0]->getAction(), 'update');
    }

    public function logCreateWritesCreateAction(): void
    {
        $this->logger->logCreate(
            actor: $this->actor,
            subject: $this->subject,
            changes: $this->changeSet(),
        );

        Assert::same($this->writer->getEvents()[0]->getAction(), 'create');
    }

    public function logDeleteWritesDeleteAction(): void
    {
        $this->logger->logDelete(
            actor: $this->actor,
            subject: $this->subject,
            changes: $this->changeSet(),
        );

        Assert::same($this->writer->getEvents()[0]->getAction(), 'delete');
    }

    public function skipsEmptyChangeSetByDefault(): void
    {
        $this->logger->logChange(
            actor: $this->actor,
            subject: $this->subject,
            changes: AuditChangeSet::empty(),
        );

        Assert::same($this->writer->count(), 0);
    }

    public function writesEmptyChangeSetWhenSkipDisabled(): void
    {
        $logger = new AuditLogger(
            writer: $this->writer,
            clock: new StubClock(new DateTimeImmutable()),
            skipEmptyChangeSets: false,
        );

        $logger->logChange(
            actor: $this->actor,
            subject: $this->subject,
            changes: AuditChangeSet::empty(),
        );

        Assert::same($this->writer->count(), 1);
    }

    public function maskerIsAppliedBeforeWriting(): void
    {
        $logger = new AuditLogger(
            writer: $this->writer,
            clock: new StubClock(new DateTimeImmutable()),
            masker: new SensitiveValueMasker(),
        );

        $changes = new AuditChangeSet([
            new AuditChange(field: 'password', oldValue: 'old-pass', newValue: 'new-pass'),
            new AuditChange(field: 'status', oldValue: 'new', newValue: 'paid'),
        ]);

        $logger->logChange(actor: $this->actor, subject: $this->subject, changes: $changes);

        $event = $this->writer->getEvents()[0];
        $eventChanges = $event->getChangeSet()->getChanges();
        Assert::same($eventChanges[0]->getOldValue(), '***');
        Assert::same($eventChanges[0]->getNewValue(), '***');
        Assert::same($eventChanges[1]->getOldValue(), 'new');
    }

    public function maskedEmptyChangeSetIsSkipped(): void
    {
        $logger = new AuditLogger(
            writer: $this->writer,
            clock: new StubClock(new DateTimeImmutable()),
            masker: new SensitiveValueMasker(sensitiveKeys: ['status']),
        );

        $logger->logChange(
            actor: $this->actor,
            subject: $this->subject,
            changes: AuditChangeSet::empty(),
        );

        Assert::same($this->writer->count(), 0);
    }

    public function metadataIsPassedToEvent(): void
    {
        $meta = new AuditMetadata(requestId: 'req-1', ip: '127.0.0.1');

        $this->logger->logChange(
            actor: $this->actor,
            subject: $this->subject,
            changes: $this->changeSet(),
            metadata: $meta,
        );

        $event = $this->writer->getEvents()[0];
        Assert::same($event->getMetadata()?->getRequestId(), 'req-1');
        Assert::same($event->getMetadata()?->getIp(), '127.0.0.1');
    }

    public function eventHasUniqueId(): void
    {
        $this->logger->logChange(actor: $this->actor, subject: $this->subject, changes: $this->changeSet());
        $this->logger->logChange(actor: $this->actor, subject: $this->subject, changes: $this->changeSet());

        $ids = array_map(fn($e) => $e->getId(), $this->writer->getEvents());
        Assert::true(preg_match('/^[a-f0-9]{32}$/', $ids[0]) === 1);
        Assert::true(preg_match('/^[a-f0-9]{32}$/', $ids[1]) === 1);
        Assert::notSame($ids[0], $ids[1]);
    }

    public function systemActorIsSupported(): void
    {
        $this->logger->logChange(
            actor: AuditActor::system(),
            subject: $this->subject,
            changes: $this->changeSet(),
        );

        $event = $this->writer->getEvents()[0];
        Assert::true($event->getActor()->isSystem());
    }
}
