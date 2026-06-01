<?php

declare(strict_types=1);

namespace Rasuvaeff\Yii3AuditLog\Tests;

use InvalidArgumentException;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Rasuvaeff\Yii3AuditLog\AuditActor;

#[CoversClass(AuditActor::class)]
final class AuditActorTest extends TestCase
{
    #[Test]
    public function createsUserActor(): void
    {
        $actor = AuditActor::user(id: '42', name: 'John');

        $this->assertSame('user', $actor->getType());
        $this->assertSame('42', $actor->getId());
        $this->assertSame('John', $actor->getName());
        $this->assertFalse($actor->isSystem());
    }

    #[Test]
    public function createsUserActorWithoutName(): void
    {
        $actor = AuditActor::user(id: '42');

        $this->assertNull($actor->getName());
    }

    #[Test]
    public function createsSystemActor(): void
    {
        $actor = AuditActor::system();

        $this->assertSame('system', $actor->getType());
        $this->assertNull($actor->getId());
        $this->assertNull($actor->getName());
        $this->assertTrue($actor->isSystem());
    }

    #[Test]
    public function createsCustomTypeActor(): void
    {
        $actor = new AuditActor(type: 'api', id: 'service-payments');

        $this->assertSame('api', $actor->getType());
        $this->assertSame('service-payments', $actor->getId());
        $this->assertFalse($actor->isSystem());
    }

    #[Test]
    public function throwsOnEmptyType(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Actor type must not be empty');

        new AuditActor(type: '', id: '1');
    }
}
