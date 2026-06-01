<?php

declare(strict_types=1);

namespace Rasuvaeff\Yii3AuditLog;

use DateTimeImmutable;
use InvalidArgumentException;

/**
 * @api
 */
final readonly class AuditEvent
{
    public function __construct(
        private string $id,
        private AuditActor $actor,
        private string $action,
        private AuditSubject $subject,
        private AuditChangeSet $changeSet,
        private DateTimeImmutable $occurredAt,
        private ?AuditMetadata $metadata = null,
    ) {
        if ($id === '') {
            throw new InvalidArgumentException('Event id must not be empty');
        }

        if ($action === '') {
            throw new InvalidArgumentException('Event action must not be empty');
        }
    }

    public function getId(): string
    {
        return $this->id;
    }

    public function getActor(): AuditActor
    {
        return $this->actor;
    }

    public function getAction(): string
    {
        return $this->action;
    }

    public function getSubject(): AuditSubject
    {
        return $this->subject;
    }

    public function getChangeSet(): AuditChangeSet
    {
        return $this->changeSet;
    }

    public function getOccurredAt(): DateTimeImmutable
    {
        return $this->occurredAt;
    }

    public function getMetadata(): ?AuditMetadata
    {
        return $this->metadata;
    }
}
