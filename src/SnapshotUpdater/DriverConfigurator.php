<?php

declare(strict_types=1);

namespace Speicher210\FunctionalTestBundle\SnapshotUpdater;

use RuntimeException;
use Speicher210\FunctionalTestBundle\CoduoMatcherFactory;
use Speicher210\FunctionalTestBundle\SnapshotUpdater\Driver\Json;
use Speicher210\FunctionalTestBundle\SnapshotUpdater\Driver\Text;

use function sprintf;

final class DriverConfigurator
{
    private static Json|null $jsonDriver = null;

    private static Text|null $textDriver = null;

    private static bool $outputUpdaterEnabled = false;

    /**
     * @param array<string,string> $fields          The fields to update in the expected output.
     * @param list<string>         $matcherPatterns
     */
    public static function createDrivers(
        array $fields,
        array $matcherPatterns,
    ): void {
        self::$jsonDriver = new Json(
            CoduoMatcherFactory::getMatcher(),
            $fields,
            $matcherPatterns,
        );
        self::$textDriver = new Text();
    }

    public static function getJsonDriver(): Json
    {
        if (self::$outputUpdaterEnabled === false) {
            throw new RuntimeException(
                sprintf(
                    'Updater is not enabled. You should call %s::enableOutputUpdater first to enable it.',
                    self::class,
                ),
            );
        }

        if (self::$jsonDriver === null) {
            throw new RuntimeException(
                sprintf(
                    'Updater is not created. You should call %s::createOutputUpdater first to create it.',
                    self::class,
                ),
            );
        }

        return self::$jsonDriver;
    }

    public static function getTextDriver(): Text
    {
        if (self::$outputUpdaterEnabled === false) {
            throw new RuntimeException(
                sprintf(
                    'Updater is not enabled. You should call %s::enableOutputUpdater first to enable it.',
                    self::class,
                ),
            );
        }

        if (self::$textDriver === null) {
            throw new RuntimeException(
                sprintf(
                    'Updater is not created. You should call %s::createOutputUpdater first to create it.',
                    self::class,
                ),
            );
        }

        return self::$textDriver;
    }

    public static function isOutputUpdaterEnabled(): bool
    {
        return self::$outputUpdaterEnabled;
    }

    public static function enableOutputUpdater(): void
    {
        if (self::$jsonDriver === null) {
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
