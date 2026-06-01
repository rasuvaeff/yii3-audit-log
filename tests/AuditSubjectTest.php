<?php

declare(strict_types=1);

namespace Rasuvaeff\Yii3AuditLog\Tests;

use InvalidArgumentException;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Rasuvaeff\Yii3AuditLog\AuditSubject;

#[CoversClass(AuditSubject::class)]
final class AuditSubjectTest extends TestCase
{
    #[Test]
    public function createsViaFactory(): void
    {
        $subject = AuditSubject::of(type: 'order', id: '42');

        $this->assertSame('order', $subject->getType());
        $this->assertSame('42', $subject->getId());
    }

    #[Test]
    public function throwsOnEmptyType(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Subject type must not be empty');

        AuditSubject::of(type: '', id: '1');
    }

    #[Test]
    public function throwsOnEmptyId(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Subject id must not be empty');

        AuditSubject::of(type: 'order', id: '');
    }
}
