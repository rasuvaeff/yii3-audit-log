<?php

declare(strict_types=1);

namespace Rasuvaeff\Yii3AuditLog\Tests;

use InvalidArgumentException;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Rasuvaeff\Yii3AuditLog\AuditChange;

#[CoversClass(AuditChange::class)]
final class AuditChangeTest extends TestCase
{
    #[Test]
    public function holdsValues(): void
    {
        $change = new AuditChange(field: 'status', oldValue: 'new', newValue: 'paid');

        $this->assertSame('status', $change->getField());
        $this->assertSame('new', $change->getOldValue());
        $this->assertSame('paid', $change->getNewValue());
    }

    #[Test]
    public function acceptsNullValues(): void
    {
        $change = new AuditChange(field: 'note', oldValue: null, newValue: 'added');

        $this->assertNull($change->getOldValue());
        $this->assertSame('added', $change->getNewValue());
    }

    #[Test]
    public function acceptsMixedTypes(): void
    {
        $change = new AuditChange(field: 'amount', oldValue: 0, newValue: 99.95);

        $this->assertSame(0, $change->getOldValue());
        $this->assertSame(99.95, $change->getNewValue());
    }

    #[Test]
    public function throwsOnEmptyField(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Change field must not be empty');

        new AuditChange(field: '', oldValue: null, newValue: null);
    }
}
