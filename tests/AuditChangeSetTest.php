<?php

declare(strict_types=1);

namespace Rasuvaeff\Yii3AuditLog\Tests;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Rasuvaeff\Yii3AuditLog\AuditChange;
use Rasuvaeff\Yii3AuditLog\AuditChangeSet;

#[CoversClass(AuditChangeSet::class)]
final class AuditChangeSetTest extends TestCase
{
    #[Test]
    public function fromArraysDetectsChangedFields(): void
    {
        $set = AuditChangeSet::fromArrays(
            old: ['status' => 'new', 'total' => 0],
            new: ['status' => 'paid', 'total' => 0],
        );

        $this->assertSame(1, $set->count());
        $this->assertSame('status', $set->getChanges()[0]->getField());
        $this->assertSame('new', $set->getChanges()[0]->getOldValue());
        $this->assertSame('paid', $set->getChanges()[0]->getNewValue());
    }

    #[Test]
    public function fromArraysDetectsAddedFields(): void
    {
        $set = AuditChangeSet::fromArrays(
            old: [],
            new: ['note' => 'added'],
        );

        $this->assertSame(1, $set->count());
        $this->assertNull($set->getChanges()[0]->getOldValue());
        $this->assertSame('added', $set->getChanges()[0]->getNewValue());
    }

    #[Test]
    public function fromArraysDetectsRemovedFields(): void
    {
        $set = AuditChangeSet::fromArrays(
            old: ['note' => 'removed'],
            new: [],
        );

        $this->assertSame(1, $set->count());
        $this->assertSame('removed', $set->getChanges()[0]->getOldValue());
        $this->assertNull($set->getChanges()[0]->getNewValue());
    }

    #[Test]
    public function fromArraysProducesEmptySetForIdenticalArrays(): void
    {
        $set = AuditChangeSet::fromArrays(
            old: ['status' => 'paid', 'total' => 99],
            new: ['status' => 'paid', 'total' => 99],
        );

        $this->assertTrue($set->isEmpty());
        $this->assertSame(0, $set->count());
    }

    #[Test]
    public function fromArraysHandlesMultipleChanges(): void
    {
        $set = AuditChangeSet::fromArrays(
            old: ['a' => 1, 'b' => 2, 'c' => 3],
            new: ['a' => 1, 'b' => 99, 'c' => 100],
        );

        $this->assertSame(2, $set->count());
    }

    #[Test]
    public function emptyFactoryCreatesEmptySet(): void
    {
        $set = AuditChangeSet::empty();

        $this->assertTrue($set->isEmpty());
        $this->assertSame([], $set->getChanges());
    }

    #[Test]
    public function constructorWithChanges(): void
    {
        $change = new AuditChange(field: 'status', oldValue: 'a', newValue: 'b');
        $set = new AuditChangeSet([$change]);

        $this->assertFalse($set->isEmpty());
        $this->assertSame(1, $set->count());
    }
}
