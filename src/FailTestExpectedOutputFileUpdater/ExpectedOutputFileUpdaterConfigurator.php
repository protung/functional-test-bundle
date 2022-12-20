<?php

declare(strict_types=1);

namespace Speicher210\FunctionalTestBundle\FailTestExpectedOutputFileUpdater;

use RuntimeException;
use Speicher210\FunctionalTestBundle\CoduoMatcherFactory;

use function sprintf;

use const JSON_PRESERVE_ZERO_FRACTION;
use const JSON_PRETTY_PRINT;
use const JSON_UNESCAPED_SLASHES;

final class ExpectedOutputFileUpdaterConfigurator
{
    private static JsonFileUpdater|null $outputUpdater = null;

    private static bool $outputUpdaterEnabled = false;

    /**
     * @param array<string,string> $fields          The fields to update in the expected output.
     * @param list<string>         $matcherPatterns
     */
    public static function createOutputUpdater(
        array $fields,
        array $matcherPatterns,
        int $jsonEncodeOptions = JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_PRESERVE_ZERO_FRACTION,
    ): void {
        self::$outputUpdater = new JsonFileUpdater(
            CoduoMatcherFactory::getMatcher(),
            $fields,
            $matcherPatterns,
            $jsonEncodeOptions,
        );
    }

    public static function getOutputUpdater(): JsonFileUpdater
    {
        if (self::$outputUpdaterEnabled === false) {
            throw new RuntimeException(
                sprintf(
                    'Updater is not enabled. You should call %s::enableOutputUpdater first to enable it.',
                    self::class,
                ),
            );
        }

        if (self::$outputUpdater === null) {
            throw new RuntimeException(
                sprintf(
                    'Updater is not created. You should call %s::createOutputUpdater first to create it.',
                    self::class,
                ),
            );
        }

        return self::$outputUpdater;
    }

    public static function isOutputUpdaterEnabled(): bool
    {
        return self::$outputUpdaterEnabled;
    }

    public static function enableOutputUpdater(): void
    {
        if (self::$outputUpdater === null) {
            throw new RuntimeException(
                sprintf(
                    'Updater is not created. You should call %s::createOutputUpdater first to create it.',
                    self::class,
                ),
            );
        }

        self::$outputUpdaterEnabled = true;
    }

    public static function disableOutputUpdater(): void
    {
        self::$outputUpdaterEnabled = false;
    }
}
