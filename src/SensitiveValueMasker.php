<?php

declare(strict_types=1);

namespace Rasuvaeff\Yii3AuditLog;

/**
 * @api
 */
final readonly class SensitiveValueMasker
{
    private const string MASK = '***';

    private const array DEFAULT_KEYS = [
        'password',
        'secret',
        'token',
        'api_key',
        'credit_card',
    ];

    /** @var list<string> */
    private array $sensitiveKeys;

    /**
     * @param list<string> $sensitiveKeys
     */
    public function __construct(array $sensitiveKeys = self::DEFAULT_KEYS)
    {
        $this->sensitiveKeys = array_map(strtolower(...), $sensitiveKeys);
    }

    /**
     * @param array<string, mixed> $values
     * @return array<string, mixed>
     */
    public function mask(array $values): array
    {
        $result = [];

        foreach ($values as $key => $value) {
            $result[$key] = $this->isSensitive($key) ? self::MASK : $value;
        }

        return $result;
    }

    public function maskChangeSet(AuditChangeSet $changeSet): AuditChangeSet
    {
        $changes = [];

        foreach ($changeSet->getChanges() as $change) {
            if ($this->isSensitive($change->getField())) {
                $changes[] = new AuditChange(
                    field: $change->getField(),
                    oldValue: self::MASK,
                    newValue: self::MASK,
                );
            } else {
                $changes[] = $change;
            }
        }

        return new AuditChangeSet($changes);
    }

    private function isSensitive(string $key): bool
    {
        return in_array(strtolower($key), $this->sensitiveKeys, strict: true);
    }
}
