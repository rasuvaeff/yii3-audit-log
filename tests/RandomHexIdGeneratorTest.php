<?php

declare(strict_types=1);

namespace Rasuvaeff\Yii3AuditLog\Tests;

use Rasuvaeff\Yii3AuditLog\RandomHexIdGenerator;
use Testo\Assert;
use Testo\Codecov\Covers;
use Testo\Test;

#[Test]
#[Covers(RandomHexIdGenerator::class)]
final class RandomHexIdGeneratorTest
{
    public function generatesThirtyTwoHexCharacters(): void
    {
        Assert::same(preg_match('/^[0-9a-f]{32}$/', (new RandomHexIdGenerator())->generate()), 1);
    }

    public function idsAreDistinct(): void
    {
        $generator = new RandomHexIdGenerator();
        $ids = [];

        for ($i = 0; $i < 100; ++$i) {
            $ids[$generator->generate()] = true;
        }

        Assert::same(count($ids), 100);
    }
}
