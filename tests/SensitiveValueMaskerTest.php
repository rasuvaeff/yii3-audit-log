<?php

declare(strict_types=1);

namespace Rasuvaeff\Yii3AuditLog\Tests;

use Rasuvaeff\Yii3AuditLog\AuditChange;
use Rasuvaeff\Yii3AuditLog\AuditChangeSet;
use Rasuvaeff\Yii3AuditLog\SensitiveValueMasker;
use Testo\Assert;
use Testo\Codecov\Covers;
use Testo\Data\DataProvider;
use Testo\Lifecycle\BeforeTest;
use Testo\Test;

#[Test]
#[Covers(SensitiveValueMasker::class)]
final class SensitiveValueMaskerTest
{
    private SensitiveValueMasker $fixture;

    #[BeforeTest]
    public function setUp(): void
    {
        $this->fixture = new SensitiveValueMasker();
    }

    public function masksMasksDefaultSensitiveKeys(): void
    {
        $result = $this->fixture->mask([
            'username' => 'john',
            'password' => 'secret123',
            'email' => 'john@example.com',
        ]);

        Assert::same($result['username'], 'john');
        Assert::same($result['password'], '***');
        Assert::same($result['email'], 'john@example.com');
    }

    public static function defaultSensitiveKeysProvider(): iterable
    {
        yield 'password' => ['password'];
        yield 'secret' => ['secret'];
        yield 'token' => ['token'];
        yield 'api_key' => ['api_key'];
        yield 'credit_card' => ['credit_card'];
    }

    #[DataProvider('defaultSensitiveKeysProvider')]
    public function masksDefaultKey(string $key): void
    {
        $result = $this->fixture->mask([$key => 'value']);

        Assert::same($result[$key], '***');
    }

    public function maskIsCaseInsensitive(): void
    {
        $result = $this->fixture->mask([
            'PASSWORD' => 'secret',
            'Password' => 'secret',
            'SECRET' => 'value',
        ]);

        Assert::same($result['PASSWORD'], '***');
        Assert::same($result['Password'], '***');
        Assert::same($result['SECRET'], '***');
    }

    public function preservesNonSensitiveValues(): void
    {
        $result = $this->fixture->mask(['name' => 'John', 'age' => 30]);

        Assert::same($result['name'], 'John');
        Assert::same($result['age'], 30);
    }

    public function customSensitiveKeys(): void
    {
        $masker = new SensitiveValueMasker(sensitiveKeys: ['ssn', 'pin']);
        $result = $masker->mask(['ssn' => '123-45-6789', 'name' => 'John', 'password' => 'pwd']);

        Assert::same($result['ssn'], '***');
        Assert::same($result['name'], 'John');
        Assert::same($result['password'], 'pwd');
    }

    public function customSensitiveKeysAreCaseInsensitive(): void
    {
        $masker = new SensitiveValueMasker(sensitiveKeys: ['Ssn']);
        $result = $masker->mask(['ssn' => '123-45-6789']);

        Assert::same($result['ssn'], '***');
    }

    public function maskChangeSetMaskesSensitiveFields(): void
    {
        $changeSet = new AuditChangeSet([
            new AuditChange(field: 'status', oldValue: 'new', newValue: 'paid'),
            new AuditChange(field: 'password', oldValue: 'old-pass', newValue: 'new-pass'),
        ]);

        $masked = $this->fixture->maskChangeSet($changeSet);

        $changes = $masked->getChanges();
        Assert::same($changes[0]->getField(), 'status');
        Assert::same($changes[0]->getOldValue(), 'new');
        Assert::same($changes[0]->getNewValue(), 'paid');

        Assert::same($changes[1]->getField(), 'password');
        Assert::same($changes[1]->getOldValue(), '***');
        Assert::same($changes[1]->getNewValue(), '***');
    }

    public function maskChangeSetPreservesCount(): void
    {
        $changeSet = new AuditChangeSet([
            new AuditChange(field: 'a', oldValue: 1, newValue: 2),
            new AuditChange(field: 'password', oldValue: 'x', newValue: 'y'),
        ]);

        $masked = $this->fixture->maskChangeSet($changeSet);

        Assert::same($masked->count(), 2);
    }
}
