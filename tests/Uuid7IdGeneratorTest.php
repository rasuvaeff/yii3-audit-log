<?php

declare(strict_types=1);

namespace Rasuvaeff\Yii3AuditLog\Tests;

use Rasuvaeff\Yii3AuditLog\Uuid7IdGenerator;
use Testo\Assert;
use Testo\Codecov\Covers;
use Testo\Test;

#[Test]
#[Covers(Uuid7IdGenerator::class)]
final class Uuid7IdGeneratorTest
{
    public function generatesThirtyTwoHexCharactersLikeTheDefaultFormat(): void
    {
        // the width is the point: it drops into the existing VARCHAR(32)
        // column of yii3-audit-log-db with no migration
        Assert::same(preg_match('/^[0-9a-f]{32}$/', (new Uuid7IdGenerator())->generate()), 1);
    }

    public function carriesTheUuidVersionAndVariantNibbles(): void
    {
        $id = (new Uuid7IdGenerator())->generate();

        Assert::same($id[12], '7');
        Assert::true(in_array($id[16], ['8', '9', 'a', 'b'], true));
    }

    public function consecutiveIdsIncreaseAsStrings(): void
    {
        // the whole reason to pick this generator: ids sort chronologically,
        // including several generated within the same millisecond
        $generator = new Uuid7IdGenerator();
        $previous = $generator->generate();

        for ($i = 0; $i < 50; ++$i) {
            $current = $generator->generate();
            Assert::true($current > $previous);
            $previous = $current;
        }
    }
}
