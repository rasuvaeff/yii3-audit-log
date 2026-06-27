<?php

declare(strict_types=1);

namespace Rasuvaeff\Yii3AuditLog\Tests;

use InvalidArgumentException;
use Rasuvaeff\Yii3AuditLog\AuditSubject;
use Testo\Assert;
use Testo\Codecov\Covers;
use Testo\Test;

#[Test]
#[Covers(AuditSubject::class)]
final class AuditSubjectTest
{
    public function createsViaFactory(): void
    {
        $subject = AuditSubject::of(type: 'order', id: '42');

        Assert::same($subject->getType(), 'order');
        Assert::same($subject->getId(), '42');
    }

    public function throwsOnEmptyType(): void
    {
        try {
            AuditSubject::of(type: '', id: '1');
            Assert::fail('Expected InvalidArgumentException');
        } catch (InvalidArgumentException $e) {
            Assert::string($e->getMessage())->contains('Subject type must not be empty');
        }
    }

    public function throwsOnEmptyId(): void
    {
        try {
            AuditSubject::of(type: 'order', id: '');
            Assert::fail('Expected InvalidArgumentException');
        } catch (InvalidArgumentException $e) {
            Assert::string($e->getMessage())->contains('Subject id must not be empty');
        }
    }
}
