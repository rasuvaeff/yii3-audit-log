<?php

declare(strict_types=1);

namespace Rasuvaeff\Yii3AuditLog;

/**
 * @api
 */
final class InMemoryAuditWriter implements AuditWriter
{
    /** @var list<AuditEvent> */
    private array $events = [];

    #[\Override]
    public function write(AuditEvent $event): void
    {
        $this->events[] = $event;
    }

    /**
     * @return list<AuditEvent>
     */
    public function getEvents(): array
    {
        return $this->events;
    }

    public function count(): int
    {
        return count($this->events);
    }

    public function clear(): void
    {
        $this->events = [];
    }
}
