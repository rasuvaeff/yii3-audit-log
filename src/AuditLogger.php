<?php

declare(strict_types=1);

namespace Rasuvaeff\Yii3AuditLog;

use Psr\Clock\ClockInterface;

/**
 * @api
 */
final readonly class AuditLogger
{
    public function __construct(
        private AuditWriter $writer,
        private ClockInterface $clock,
        private ?SensitiveValueMasker $masker = null,
        private bool $skipEmptyChangeSets = true,
    ) {}

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
            id: bin2hex(random_bytes(16)),
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
