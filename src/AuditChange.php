<?php

declare(strict_types=1);

namespace Rasuvaeff\Yii3AuditLog;

use InvalidArgumentException;

/**
 * @api
 */
final readonly class AuditChange
{
    public function __construct(
        private string $field,
        private mixed $oldValue,
        private mixed $newValue,
    ) {
        if ($field === '') {
            throw new InvalidArgumentException('Change field must not be empty');
        }
    }

    public function getField(): string
    {
        return $this->field;
    }

    public function getOldValue(): mixed
    {
        return $this->oldValue;
    }

    public function getNewValue(): mixed
    {
        return $this->newValue;
    }
}
