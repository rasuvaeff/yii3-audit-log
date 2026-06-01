<?php

declare(strict_types=1);

namespace Rasuvaeff\Yii3AuditLog;

/**
 * @api
 */
final readonly class AuditChangeSet
{
    /**
     * @param list<AuditChange> $changes
     */
    public function __construct(private array $changes) {}

    /**
     * @param array<string, mixed> $old
     * @param array<string, mixed> $new
     */
    public static function fromArrays(array $old, array $new): self
    {
        $changes = [];
        $allKeys = array_unique(array_merge(array_keys($old), array_keys($new)));

        foreach ($allKeys as $key) {
            if (self::valueOrNull(values: $old, key: $key) !== self::valueOrNull(values: $new, key: $key)) {
                $changes[] = new AuditChange(
                    field: $key,
                    oldValue: self::valueOrNull(values: $old, key: $key),
                    newValue: self::valueOrNull(values: $new, key: $key),
                );
            }
        }

        return new self($changes);
    }

    public static function empty(): self
    {
        return new self([]);
    }

    public function isEmpty(): bool
    {
        return $this->changes === [];
    }

    /**
     * @return list<AuditChange>
     */
    public function getChanges(): array
    {
        return $this->changes;
    }

    public function count(): int
    {
        return count($this->changes);
    }

    /**
     * @param array<string, mixed> $values
     */
    private static function valueOrNull(array $values, string $key): mixed
    {
        return $values[$key] ?? null;
    }
}
