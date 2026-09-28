<?php

declare(strict_types=1);

namespace Speicher210\FunctionalTestBundle\Extension;

use InvalidArgumentException;
use PHPUnit\Event\TestRunner\Finished as TestRunnerFinishedEvent;
use PHPUnit\Event\TestRunner\FinishedSubscriber as TestRunnerFinishedSubscriber;
use PHPUnit\Event\TestRunner\Started as TestRunnerStartedEvent;
use PHPUnit\Event\TestRunner\StartedSubscriber as TestRunnerStartedSubscriber;
use PHPUnit\Runner\Extension\Extension;
use PHPUnit\Runner\Extension\Facade;
use PHPUnit\Runner\Extension\ParameterCollection;
use PHPUnit\TextUI\Configuration\Configuration;
use Psl\Json;
use Psl\Str;
use Psl\Type;
use Speicher210\FunctionalTestBundle\SnapshotUpdater\Driver\Json as JsonDriver;
use Speicher210\FunctionalTestBundle\SnapshotUpdater\DriverConfigurator;

/**
 * Updates the expected files of failing snapshot assertions with the actual output.
 *
 * The fields and matcher patterns can also be configured in phpunit.xml, as JSON:
 *
 *     <bootstrap class="Speicher210\FunctionalTestBundle\Extension\SnapshotUpdaterExtension">
 *         <parameter name="fields" value='{"createdAt": "@string@.isDateTime()"}'/>
 *         <parameter name="matcherPatterns" value='["@string@", "@integer@"]'/>
 *     </bootstrap>
 */
final class SnapshotUpdaterExtension implements Extension
{
    private const string PARAMETER_FIELDS = 'fields';

    private const string PARAMETER_MATCHER_PATTERNS = 'matcherPatterns';

    /**
     * @param array<string,string> $fields          Fields that will always be updated with a fixed value in the expected output. Ex: ['createdAt' => '@string@.isDateTime()']
     * @param list<string>         $matcherPatterns Patterns that should be kept when updating.
     */
    public function __construct(
        private readonly array $fields = [],
        private readonly array $matcherPatterns = JsonDriver::DEFAULT_MATCHER_PATTERNS,
    ) {
    }

    public function bootstrap(Configuration $configuration, Facade $facade, ParameterCollection $parameters): void
    {
        $fields = $parameters->has(self::PARAMETER_FIELDS)
            ? self::parameter($parameters, self::PARAMETER_FIELDS, Type\dict(Type\string(), Type\string()), 'a JSON object of strings')
            : $this->fields;

        $matcherPatterns = $parameters->has(self::PARAMETER_MATCHER_PATTERNS)
            ? self::parameter($parameters, self::PARAMETER_MATCHER_PATTERNS, Type\vec(Type\string()), 'a JSON list of strings')
            : $this->matcherPatterns;

        $facade->registerSubscriber(
            new class ($fields, $matcherPatterns) implements TestRunnerStartedSubscriber {
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

    /**
     * @param non-empty-string      $name
     * @param Type\TypeInterface<T> $type
     *
     * @return T
     *
     * @template T
     */
    private static function parameter(ParameterCollection $parameters, string $name, Type\TypeInterface $type, string $expected): mixed
    {
        $value = $parameters->get($name);

        try {
            return Json\typed($value, $type);
        } catch (Json\Exception\DecodeException $exception) {
            throw new InvalidArgumentException(
                Str\format('The "%s" parameter of %s must be %s, got: %s', $name, self::class, $expected, $value),
                previous: $exception,
            );
        }
    }
}
