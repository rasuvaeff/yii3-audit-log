<?php

declare(strict_types=1);

namespace Rasuvaeff\Yii3AuditLog\Tests;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Rasuvaeff\Yii3AuditLog\AuditMetadata;

#[CoversClass(AuditMetadata::class)]
final class AuditMetadataTest extends TestCase
{
    #[Test]
    public function defaultsToAllNull(): void
    {
        $meta = new AuditMetadata();

        $this->assertNull($meta->getRequestId());
        $this->assertNull($meta->getIp());
        $this->assertNull($meta->getUserAgent());
    }

    #[Test]
    public function holdsAllValues(): void
    {
        $meta = new AuditMetadata(
            requestId: 'req-abc',
            ip: '127.0.0.1',
            userAgent: 'Mozilla/5.0',
        );

        $this->assertSame('req-abc', $meta->getRequestId());
        $this->assertSame('127.0.0.1', $meta->getIp());
        $this->assertSame('Mozilla/5.0', $meta->getUserAgent());
    }

    #[Test]
    public function holdsPartialValues(): void
    {
        $meta = new AuditMetadata(requestId: 'req-1');

        $this->assertSame('req-1', $meta->getRequestId());
        $this->assertNull($meta->getIp());
    }
}
