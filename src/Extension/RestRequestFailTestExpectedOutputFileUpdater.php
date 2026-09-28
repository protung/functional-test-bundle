<?php

declare(strict_types=1);

namespace Speicher210\FunctionalTestBundle\Extension;

use PHPUnit\Event\TestRunner\Finished as TestRunnerFinishedEvent;
use PHPUnit\Event\TestRunner\FinishedSubscriber as TestRunnerFinishedSubscriber;
use PHPUnit\Event\TestRunner\Started as TestRunnerStartedEvent;
use PHPUnit\Event\TestRunner\StartedSubscriber as TestRunnerStartedSubscriber;
use PHPUnit\Runner\Extension\Extension;
use PHPUnit\Runner\Extension\Facade;
use PHPUnit\Runner\Extension\ParameterCollection;
use PHPUnit\TextUI\Configuration\Configuration;
use Speicher210\FunctionalTestBundle\SnapshotUpdater\Driver\Json;
use Speicher210\FunctionalTestBundle\SnapshotUpdater\DriverConfigurator;

final class RestRequestFailTestExpectedOutputFileUpdater implements Extension
{
    /**
     * @param array<string,string> $fields          Fields that will always be updated with a fixed value in the expected output. Ex: ['createdAt' => '@string@.isDateTime()']
     * @param list<string>         $matcherPatterns Patterns that should be kept when updating.
     */
    public function __construct(
        private readonly array $fields = [],
        private readonly array $matcherPatterns = Json::DEFAULT_MATCHER_PATTERNS,
    ) {
    }

    public function bootstrap(Configuration $configuration, Facade $facade, ParameterCollection $parameters): void
    {
        $facade->registerSubscriber(
            new class ($this->fields, $this->matcherPatterns) implements TestRunnerStartedSubscriber {
                /**
                 * @param array<string,string> $fields          The fields to update in the expected output.
                 * @param list<string>         $matcherPatterns
                 */
                public function __construct(private readonly array $fields, private readonly array $matcherPatterns)
                {
                }

                public function notify(TestRunnerStartedEvent $event): void
                {
                    DriverConfigurator::createDrivers($this->fields, $this->matcherPatterns);
                    DriverConfigurator::enableOutputUpdater();
                }
            },
        );

        $facade->registerSubscriber(
            new class () implements TestRunnerFinishedSubscriber {
                public function notify(TestRunnerFinishedEvent $event): void
                {
                    DriverConfigurator::disableOutputUpdater();
                }
            },
        );
    }
}
