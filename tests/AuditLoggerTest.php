<?php

declare(strict_types=1);

namespace Rasuvaeff\Yii3AuditLog\Tests;

use DateTimeImmutable;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Rasuvaeff\Yii3AuditLog\AuditActor;
use Rasuvaeff\Yii3AuditLog\AuditChange;
use Rasuvaeff\Yii3AuditLog\AuditChangeSet;
use Rasuvaeff\Yii3AuditLog\AuditLogger;
use Rasuvaeff\Yii3AuditLog\AuditMetadata;
use Rasuvaeff\Yii3AuditLog\AuditSubject;
use Rasuvaeff\Yii3AuditLog\InMemoryAuditWriter;
use Rasuvaeff\Yii3AuditLog\SensitiveValueMasker;

#[CoversClass(AuditLogger::class)]
final class AuditLoggerTest extends TestCase
{
    private InMemoryAuditWriter $writer;
    private AuditLogger $logger;
    private AuditActor $actor;
    private AuditSubject $subject;

    #[\Override]
    protected function setUp(): void
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

    #[Test]
    public function logWritesEvent(): void
    {
        $this->logger->log(
            actor: $this->actor,
            action: 'update',
            subject: $this->subject,
            changes: $this->changeSet(),
        );

        $this->assertSame(1, $this->writer->count());
        $event = $this->writer->getEvents()[0];
        $this->assertSame('update', $event->getAction());
        $this->assertSame('order', $event->getSubject()->getType());
        $this->assertSame('42', $event->getSubject()->getId());
        $this->assertSame('2026-06-01 12:00:00', $event->getOccurredAt()->format('Y-m-d H:i:s'));
    }

    #[Test]
    public function logChangeWritesUpdateAction(): void
    {
        $this->logger->logChange(
            actor: $this->actor,
            subject: $this->subject,
            changes: $this->changeSet(),
        );

        $this->assertSame('update', $this->writer->getEvents()[0]->getAction());
    }

    #[Test]
    public function logCreateWritesCreateAction(): void
    {
        $this->logger->logCreate(
            actor: $this->actor,
            subject: $this->subject,
            changes: $this->changeSet(),
        );

        $this->assertSame('create', $this->writer->getEvents()[0]->getAction());
    }

    #[Test]
    public function logDeleteWritesDeleteAction(): void
    {
        $this->logger->logDelete(
            actor: $this->actor,
            subject: $this->subject,
            changes: $this->changeSet(),
        );

        $this->assertSame('delete', $this->writer->getEvents()[0]->getAction());
    }

    #[Test]
    public function skipsEmptyChangeSetByDefault(): void
    {
        $this->logger->logChange(
            actor: $this->actor,
            subject: $this->subject,
            changes: AuditChangeSet::empty(),
        );

        $this->assertSame(0, $this->writer->count());
    }

    #[Test]
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

        $this->assertSame(1, $this->writer->count());
    }

    #[Test]
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
        $this->assertSame('***', $eventChanges[0]->getOldValue());
        $this->assertSame('***', $eventChanges[0]->getNewValue());
        $this->assertSame('new', $eventChanges[1]->getOldValue());
    }

    #[Test]
    public function maskedEmptyChangeSetIsSkipped(): void
    {
        // If masking produces same value on both sides for all changes,
        // fromArrays would return empty set. Here test that masker result
        // being empty is also skipped.
        $logger = new AuditLogger(
            writer: $this->writer,
            clock: new StubClock(new DateTimeImmutable()),
            masker: new SensitiveValueMasker(sensitiveKeys: ['status']),
        );

        // After masking status old=*** new=*** — not equal, still written.
        // But empty set itself is skipped.
        $logger->logChange(
            actor: $this->actor,
            subject: $this->subject,
            changes: AuditChangeSet::empty(),
        );

        $this->assertSame(0, $this->writer->count());
    }

    #[Test]
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
        $this->assertSame('req-1', $event->getMetadata()?->getRequestId());
        $this->assertSame('127.0.0.1', $event->getMetadata()?->getIp());
    }

    #[Test]
    public function eventHasUniqueId(): void
    {
        $this->logger->logChange(actor: $this->actor, subject: $this->subject, changes: $this->changeSet());
        $this->logger->logChange(actor: $this->actor, subject: $this->subject, changes: $this->changeSet());

        $ids = array_map(fn($e) => $e->getId(), $this->writer->getEvents());
        $this->assertMatchesRegularExpression('/^[a-f0-9]{32}$/', $ids[0]);
        $this->assertMatchesRegularExpression('/^[a-f0-9]{32}$/', $ids[1]);
        $this->assertNotSame($ids[0], $ids[1]);
    }

    #[Test]
    public function systemActorIsSupported(): void
    {
        $this->logger->logChange(
            actor: AuditActor::system(),
            subject: $this->subject,
            changes: $this->changeSet(),
        );

        $event = $this->writer->getEvents()[0];
        $this->assertTrue($event->getActor()->isSystem());
    }
}
