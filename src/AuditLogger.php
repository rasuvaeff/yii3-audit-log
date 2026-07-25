<?php

declare(strict_types=1);

namespace Rasuvaeff\Yii3AuditLog;

use Psr\Clock\ClockInterface;

/**
 * @api
 */
final readonly class AuditLogger
{
    private AuditEventIdGeneratorInterface $idGenerator;

    /**
     * @param ?AuditEventIdGeneratorInterface $idGenerator null keeps the historical
     *                                                     format ({@see RandomHexIdGenerator})
     */
    public function __construct(
        private AuditWriter $writer,
        private ClockInterface $clock,
        private ?SensitiveValueMasker $masker = null,
        private bool $skipEmptyChangeSets = true,
        ?AuditEventIdGeneratorInterface $idGenerator = null,
    ) {
        $this->idGenerator = $idGenerator ?? new RandomHexIdGenerator();
    }

    public function log(
        AuditActor $actor,
        string $action,
        AuditSubject $subject,
        AuditChangeSet $changes,
        ?AuditMetadata $metadata = null,
    ): void {
        $changeSet = $this->masker instanceof SensitiveValueMasker
            ? $this->masker->maskChangeSet($changes)
            : $changes;

        if ($this->skipEmptyChangeSets && $changeSet->isEmpty()) {
            return;
        }

        $event = new AuditEvent(
            id: $this->idGenerator->generate(),
            actor: $actor,
            action: $action,
            subject: $subject,
            changeSet: $changeSet,
            occurredAt: $this->clock->now(),
            metadata: $metadata,
        );

        $this->writer->write($event);
    }

    public function logCreate(
        AuditActor $actor,
        AuditSubject $subject,
        AuditChangeSet $changes,
        ?AuditMetadata $metadata = null,
    ): void {
        $this->log(
            actor: $actor,
            action: 'create',
            subject: $subject,
            changes: $changes,
            metadata: $metadata,
        );
    }

    public function logChange(
        AuditActor $actor,
        AuditSubject $subject,
        AuditChangeSet $changes,
        ?AuditMetadata $metadata = null,
    ): void {
        $this->log(
            actor: $actor,
            action: 'update',
            subject: $subject,
            changes: $changes,
            metadata: $metadata,
        );
    }

    public function logDelete(
        AuditActor $actor,
        AuditSubject $subject,
        AuditChangeSet $changes,
        ?AuditMetadata $metadata = null,
    ): void {
        $this->log(
            actor: $actor,
            action: 'delete',
            subject: $subject,
            changes: $changes,
            metadata: $metadata,
        );
    }
}
