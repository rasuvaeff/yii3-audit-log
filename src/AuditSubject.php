<?php

declare(strict_types=1);

namespace Rasuvaeff\Yii3AuditLog;

use InvalidArgumentException;

/**
 * @api
 */
final readonly class AuditSubject
{
    public function __construct(
        private string $type,
        private string $id,
    ) {
        if ($type === '') {
            throw new InvalidArgumentException('Subject type must not be empty');
        }

        if ($id === '') {
            throw new InvalidArgumentException('Subject id must not be empty');
        }
    }

    public static function of(string $type, string $id): self
    {
        return new self(type: $type, id: $id);
    }

    public function getType(): string
    {
        return $this->type;
    }

    public function getId(): string
    {
        return $this->id;
    }
}
