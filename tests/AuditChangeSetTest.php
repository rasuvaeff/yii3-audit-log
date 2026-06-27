<?php

declare(strict_types=1);

namespace Rasuvaeff\Yii3AuditLog\Tests;

use Rasuvaeff\Yii3AuditLog\AuditChange;
use Rasuvaeff\Yii3AuditLog\AuditChangeSet;
use Testo\Assert;
use Testo\Codecov\Covers;
use Testo\Test;

#[Test]
#[Covers(AuditChangeSet::class)]
final class AuditChangeSetTest
{
    public function fromArraysDetectsChangedFields(): void
    {
        $set = AuditChangeSet::fromArrays(
            old: ['status' => 'new', 'total' => 0],
            new: ['status' => 'paid', 'total' => 0],
        );

        Assert::same($set->count(), 1);
        Assert::same($set->getChanges()[0]->getField(), 'status');
        Assert::same($set->getChanges()[0]->getOldValue(), 'new');
        Assert::same($set->getChanges()[0]->getNewValue(), 'paid');
    }

    public function fromArraysDetectsAddedFields(): void
    {
        $set = AuditChangeSet::fromArrays(
            old: [],
            new: ['note' => 'added'],
        );

        Assert::same($set->count(), 1);
        Assert::null($set->getChanges()[0]->getOldValue());
        Assert::same($set->getChanges()[0]->getNewValue(), 'added');
    }

    public function fromArraysDetectsRemovedFields(): void
    {
        $set = AuditChangeSet::fromArrays(
            old: ['note' => 'removed'],
            new: [],
        );

        Assert::same($set->count(), 1);
        Assert::same($set->getChanges()[0]->getOldValue(), 'removed');
        Assert::null($set->getChanges()[0]->getNewValue());
    }

    public function fromArraysProducesEmptySetForIdenticalArrays(): void
    {
        $set = AuditChangeSet::fromArrays(
            old: ['status' => 'paid', 'total' => 99],
            new: ['status' => 'paid', 'total' => 99],
        );

        Assert::true($set->isEmpty());
        Assert::same($set->count(), 0);
    }

    public function fromArraysHandlesMultipleChanges(): void
    {
        $set = AuditChangeSet::fromArrays(
            old: ['a' => 1, 'b' => 2, 'c' => 3],
            new: ['a' => 1, 'b' => 99, 'c' => 100],
        );

        Assert::same($set->count(), 2);
    }

    public function emptyFactoryCreatesEmptySet(): void
    {
        $set = AuditChangeSet::empty();

        Assert::true($set->isEmpty());
        Assert::same($set->getChanges(), []);
    }

    public function constructorWithChanges(): void
    {
        $change = new AuditChange(field: 'status', oldValue: 'a', newValue: 'b');
        $set = new AuditChangeSet([$change]);

        Assert::false($set->isEmpty());
        Assert::same($set->count(), 1);
    }
}
