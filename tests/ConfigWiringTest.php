<?php

declare(strict_types=1);

namespace Rasuvaeff\Yii3AuditLog\Tests;

use DateTimeImmutable;
use Rasuvaeff\Yii3AuditLog\AuditActor;
use Rasuvaeff\Yii3AuditLog\AuditChange;
use Rasuvaeff\Yii3AuditLog\AuditChangeSet;
use Rasuvaeff\Yii3AuditLog\AuditEventIdGeneratorInterface;
use Rasuvaeff\Yii3AuditLog\AuditLogger;
use Rasuvaeff\Yii3AuditLog\AuditSubject;
use Rasuvaeff\Yii3AuditLog\InMemoryAuditWriter;
use Rasuvaeff\Yii3AuditLog\RandomHexIdGenerator;
use Rasuvaeff\Yii3AuditLog\SensitiveValueMasker;
use Testo\Assert;
use Testo\Codecov\CoversNothing;
use Testo\Test;

/**
 * config/di.php is covered by neither psalm (src-only) nor cs, so a typo in
 * the factory signature would only surface in an application. This exercises
 * the definitions the config plugin ships.
 */
#[Test]
#[CoversNothing]
final class ConfigWiringTest
{
    public function idGeneratorDefaultsToTheHistoricalFormat(): void
    {
        Assert::same($this->definitions()[AuditEventIdGeneratorInterface::class], RandomHexIdGenerator::class);
    }

    public function loggerFactoryBuildsAWorkingLoggerFromTheInjectedGenerator(): void
    {
        $definitions = $this->definitions();
        $factory = $definitions[AuditLogger::class];
        Assert::true(is_callable($factory));

        $writer = new InMemoryAuditWriter();
        /** @var AuditLogger $logger */
        $logger = $factory(
            $writer,
            new StubClock(new DateTimeImmutable('2026-06-01 12:00:00')),
            new SensitiveValueMasker(),
            new FixedIdGenerator('wired-id'),
        );

        $logger->log(
            actor: AuditActor::system(),
            action: 'update',
            subject: AuditSubject::of(type: 'order', id: '42'),
            changes: new AuditChangeSet([new AuditChange('password', 'old', 'new')]),
        );

        $event = $writer->getEvents()[0];
        Assert::same($event->getId(), 'wired-id');
        // masker and skipEmptyChangeSets come from params in the same factory
        Assert::same($event->getChangeSet()->getChanges()[0]->getNewValue(), '***');
    }

    /**
     * @return array<string, mixed>
     */
    private function definitions(): array
    {
        // config/di.php reads $params from the enclosing scope
        $params = require dirname(__DIR__) . '/config/params.php';

        return require dirname(__DIR__) . '/config/di.php';
    }
}
