<?php

declare(strict_types=1);

namespace Rasuvaeff\Yii3AuditLog\Tests;

use InvalidArgumentException;
use Rasuvaeff\Yii3AuditLog\AuditChange;
use Testo\Assert;
use Testo\Codecov\Covers;
use Testo\Test;

#[Test]
#[Covers(AuditChange::class)]
final class AuditChangeTest
{
    public function holdsValues(): void
    {
        $change = new AuditChange(field: 'status', oldValue: 'new', newValue: 'paid');

        Assert::same($change->getField(), 'status');
        Assert::same($change->getOldValue(), 'new');
        Assert::same($change->getNewValue(), 'paid');
    }

    public function acceptsNullValues(): void
    {
        $change = new AuditChange(field: 'note', oldValue: null, newValue: 'added');

        Assert::null($change->getOldValue());
        Assert::same($change->getNewValue(), 'added');
    }

    public function acceptsMixedTypes(): void
    {
        $change = new AuditChange(field: 'amount', oldValue: 0, newValue: 99.95);

        Assert::same($change->getOldValue(), 0);
        Assert::same($change->getNewValue(), 99.95);
    }

    public function throwsOnEmptyField(): void
    {
        try {
            new AuditChange(field: '', oldValue: null, newValue: null);
            Assert::fail('Expected InvalidArgumentException');
        } catch (InvalidArgumentException $e) {
            Assert::string($e->getMessage())->contains('Change field must not be empty');
        }
    }
}
