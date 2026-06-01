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
use Rasuvaeff\Yii3AuditLog\InMemoryAuditWriter;

#[CoversClass(InMemoryAuditWriter::class)]
final class InMemoryAuditWriterTest extends TestCase
{
    private InMemoryAuditWriter $fixture;

    #[\Override]
    protected function setUp(): void
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

    #[Test]
    public function startsEmpty(): void
    {
        $this->assertSame(0, $this->fixture->count());
        $this->assertSame([], $this->fixture->getEvents());
    }

    #[Test]
    public function writeStoresEvent(): void
    {
        $event = $this->event('evt-1');

        $this->fixture->write($event);

        $this->assertSame(1, $this->fixture->count());
        $this->assertSame($event, $this->fixture->getEvents()[0]);
    }

    #[Test]
    public function writeStoresMultipleEvents(): void
    {
        $this->fixture->write($this->event('evt-1'));
        $this->fixture->write($this->event('evt-2'));

        $this->assertSame(2, $this->fixture->count());
    }

    #[Test]
    public function clearRemovesAllEvents(): void
    {
        $this->fixture->write($this->event('evt-1'));
        $this->fixture->clear();

        $this->assertSame(0, $this->fixture->count());
        $this->assertSame([], $this->fixture->getEvents());
    }
}
