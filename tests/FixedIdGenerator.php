<?php

declare(strict_types=1);

namespace Rasuvaeff\Yii3AuditLog\Tests;

use Rasuvaeff\Yii3AuditLog\AuditEventIdGeneratorInterface;

final readonly class FixedIdGenerator implements AuditEventIdGeneratorInterface
{
    /**
     * @param non-empty-string $id
     */
    public function __construct(
        private string $id,
    ) {}

    #[\Override]
    public function generate(): string
    {
        return $this->id;
    }
}
