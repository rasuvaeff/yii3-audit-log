<?php

declare(strict_types=1);

namespace Rasuvaeff\Yii3AuditLog\Tests;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Rasuvaeff\Yii3AuditLog\AuditChange;
use Rasuvaeff\Yii3AuditLog\AuditChangeSet;
use Rasuvaeff\Yii3AuditLog\SensitiveValueMasker;

#[CoversClass(SensitiveValueMasker::class)]
final class SensitiveValueMaskerTest extends TestCase
{
    private SensitiveValueMasker $fixture;

    #[\Override]
    protected function setUp(): void
    {
        $this->fixture = new SensitiveValueMasker();
    }

    #[Test]
    public function masksMasksDefaultSensitiveKeys(): void
    {
        $result = $this->fixture->mask([
            'username' => 'john',
            'password' => 'secret123',
            'email' => 'john@example.com',
        ]);

        $this->assertSame('john', $result['username']);
        $this->assertSame('***', $result['password']);
        $this->assertSame('john@example.com', $result['email']);
    }

    /**
     * @return iterable<string, array{string}>
     */
    public static function defaultSensitiveKeysProvider(): iterable
    {
        yield 'password' => ['password'];
        yield 'secret' => ['secret'];
        yield 'token' => ['token'];
        yield 'api_key' => ['api_key'];
        yield 'credit_card' => ['credit_card'];
    }

    #[Test]
    #[DataProvider('defaultSensitiveKeysProvider')]
    public function masksDefaultKey(string $key): void
    {
        $result = $this->fixture->mask([$key => 'value']);

        $this->assertSame('***', $result[$key]);
    }

    #[Test]
    public function maskIsCaseInsensitive(): void
    {
        $result = $this->fixture->mask([
            'PASSWORD' => 'secret',
            'Password' => 'secret',
            'SECRET' => 'value',
        ]);

        $this->assertSame('***', $result['PASSWORD']);
        $this->assertSame('***', $result['Password']);
        $this->assertSame('***', $result['SECRET']);
    }

    #[Test]
    public function preservesNonSensitiveValues(): void
    {
        $result = $this->fixture->mask(['name' => 'John', 'age' => 30]);

        $this->assertSame('John', $result['name']);
        $this->assertSame(30, $result['age']);
    }

    #[Test]
    public function customSensitiveKeys(): void
    {
        $masker = new SensitiveValueMasker(sensitiveKeys: ['ssn', 'pin']);
        $result = $masker->mask(['ssn' => '123-45-6789', 'name' => 'John', 'password' => 'pwd']);

        $this->assertSame('***', $result['ssn']);
        $this->assertSame('John', $result['name']);
        // password is not in custom list
        $this->assertSame('pwd', $result['password']);
    }

    #[Test]
    public function customSensitiveKeysAreCaseInsensitive(): void
    {
        $masker = new SensitiveValueMasker(sensitiveKeys: ['Ssn']);
        $result = $masker->mask(['ssn' => '123-45-6789']);

        $this->assertSame('***', $result['ssn']);
    }

    #[Test]
    public function maskChangeSetMaskesSensitiveFields(): void
    {
        $changeSet = new AuditChangeSet([
            new AuditChange(field: 'status', oldValue: 'new', newValue: 'paid'),
            new AuditChange(field: 'password', oldValue: 'old-pass', newValue: 'new-pass'),
        ]);

        $masked = $this->fixture->maskChangeSet($changeSet);

        $changes = $masked->getChanges();
        $this->assertSame('status', $changes[0]->getField());
        $this->assertSame('new', $changes[0]->getOldValue());
        $this->assertSame('paid', $changes[0]->getNewValue());

        $this->assertSame('password', $changes[1]->getField());
        $this->assertSame('***', $changes[1]->getOldValue());
        $this->assertSame('***', $changes[1]->getNewValue());
    }

    #[Test]
    public function maskChangeSetPreservesCount(): void
    {
        $changeSet = new AuditChangeSet([
            new AuditChange(field: 'a', oldValue: 1, newValue: 2),
            new AuditChange(field: 'password', oldValue: 'x', newValue: 'y'),
        ]);

        $masked = $this->fixture->maskChangeSet($changeSet);

        $this->assertSame(2, $masked->count());
    }
}
