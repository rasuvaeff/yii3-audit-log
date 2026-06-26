<?php

declare(strict_types=1);

namespace Rasuvaeff\Yii3AuditLog\Tests;

use InvalidArgumentException;
use Rasuvaeff\Yii3AuditLog\AuditActor;
use Testo\Assert;
use Testo\Codecov\Covers;
use Testo\Test;

#[Test]
#[Covers(AuditActor::class)]
final class AuditActorTest
{
    public function createsUserActor(): void
    {
        $actor = AuditActor::user(id: '42', name: 'John');

        Assert::same($actor->getType(), 'user');
        Assert::same($actor->getId(), '42');
        Assert::same($actor->getName(), 'John');
        Assert::false($actor->isSystem());
    }

    public function createsUserActorWithoutName(): void
    {
        $actor = AuditActor::user(id: '42');

        Assert::null($actor->getName());
    }

    public function createsSystemActor(): void
    {
        $actor = AuditActor::system();

        Assert::same($actor->getType(), 'system');
        Assert::null($actor->getId());
        Assert::null($actor->getName());
        Assert::true($actor->isSystem());
    }

    public function createsCustomTypeActor(): void
    {
        $actor = new AuditActor(type: 'api', id: 'service-payments');

        Assert::same($actor->getType(), 'api');
        Assert::same($actor->getId(), 'service-payments');
        Assert::false($actor->isSystem());
    }

    public function throwsOnEmptyType(): void
    {
        try {
            new AuditActor(type: '', id: '1');
            Assert::fail('Expected InvalidArgumentException');
        } catch (InvalidArgumentException $e) {
            Assert::string($e->getMessage())->contains('Actor type must not be empty');
        }
    }
}
