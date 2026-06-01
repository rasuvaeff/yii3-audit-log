<?php

declare(strict_types=1);

namespace Rasuvaeff\Yii3AuditLog\Tests;

use DateTimeImmutable;
use Psr\Clock\ClockInterface;

final readonly class StubClock implements ClockInterface
{
    public function __construct(private DateTimeImmutable $now) {}

    #[\Override]
    public function now(): DateTimeImmutable
    {
        return $this->now;
    }
}
