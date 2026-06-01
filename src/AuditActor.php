<?php

declare(strict_types=1);

namespace Rasuvaeff\Yii3AuditLog;

use InvalidArgumentException;

/**
 * @api
 */
final readonly class AuditActor
{
    public function __construct(
        private string $type,
        private ?string $id,
        private ?string $name = null,
    ) {
        if ($type === '') {
            throw new InvalidArgumentException('Actor type must not be empty');
        }
    }

    public static function user(string $id, ?string $name = null): self
    {
        return new self(type: 'user', id: $id, name: $name);
    }

    public static function system(): self
    {
        return new self(type: 'system', id: null);
    }

    public function getType(): string
    {
        return $this->type;
    }

    public function getId(): ?string
    {
        return $this->id;
    }

    public function getName(): ?string
    {
        return $this->name;
    }

    public function isSystem(): bool
    {
        return $this->type === 'system';
    }
}
