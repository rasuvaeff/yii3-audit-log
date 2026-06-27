<?php

declare(strict_types=1);

namespace Rasuvaeff\Yii3AuditLog\Tests;

use Rasuvaeff\Yii3AuditLog\AuditMetadata;
use Testo\Assert;
use Testo\Codecov\Covers;
use Testo\Test;

#[Test]
#[Covers(AuditMetadata::class)]
final class AuditMetadataTest
{
    public function defaultsToAllNull(): void
    {
        $meta = new AuditMetadata();

        Assert::null($meta->getRequestId());
        Assert::null($meta->getIp());
        Assert::null($meta->getUserAgent());
    }

    public function holdsAllValues(): void
    {
        $meta = new AuditMetadata(
            requestId: 'req-abc',
            ip: '127.0.0.1',
            userAgent: 'Mozilla/5.0',
        );

        Assert::same($meta->getRequestId(), 'req-abc');
        Assert::same($meta->getIp(), '127.0.0.1');
        Assert::same($meta->getUserAgent(), 'Mozilla/5.0');
    }

    public function holdsPartialValues(): void
    {
        $meta = new AuditMetadata(requestId: 'req-1');

        Assert::same($meta->getRequestId(), 'req-1');
        Assert::null($meta->getIp());
    }
}
