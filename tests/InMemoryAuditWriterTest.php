<?php

declare(strict_types=1);

namespace Rasuvaeff\Yii3AuditLog\Tests;

use DateTimeImmutable;
use Rasuvaeff\Yii3AuditLog\AuditActor;
use Rasuvaeff\Yii3AuditLog\AuditChangeSet;
use Rasuvaeff\Yii3AuditLog\AuditEvent;
use Rasuvaeff\Yii3AuditLog\AuditSubject;
use Rasuvaeff\Yii3AuditLog\InMemoryAuditWriter;
use Testo\Assert;
use Testo\Codecov\Covers;
use Testo\Lifecycle\BeforeTest;
use Testo\Test;

#[Test]
#[Covers(InMemoryAuditWriter::class)]
final class InMemoryAuditWriterTest
{
    private InMemoryAuditWriter $fixture;

    #[BeforeTest]
    public function setUp(): void
    {
        $this->fixture = new InMemoryAuditWriter();
    }

    private function event(string $id): AuditEvent
    {
        return new AuditEvent(
            id: $id,
            actor: AuditActor::system(),
            action: 'update',
            subject: AuditSubject::of(type: 'order', id: '1'),
            changeSet: AuditChangeSet::empty(),
            occurredAt: new DateTimeImmutable(),
        );
    }

    public function startsEmpty(): void
    {
        Assert::same($this->fixture->count(), 0);
        Assert::same($this->fixture->getEvents(), []);
    }

    public function writeStoresEvent(): void
    {
        $event = $this->event('evt-1');

        $this->fixture->write($event);

        Assert::same($this->fixture->count(), 1);
        Assert::same($this->fixture->getEvents()[0], $event);
    }

    public function writeStoresMultipleEvents(): void
    {
        $this->fixture->write($this->event('evt-1'));
        $this->fixture->write($this->event('evt-2'));

        Assert::same($this->fixture->count(), 2);
    }

    public function clearRemovesAllEvents(): void
    {
        $this->fixture->write($this->event('evt-1'));
        $this->fixture->clear();

        Assert::same($this->fixture->count(), 0);
        Assert::same($this->fixture->getEvents(), []);
    }
}
