<?php

declare(strict_types=1);

namespace Speicher210\FunctionalTestBundle\SnapshotUpdater;

use Psl\Str;
use RuntimeException;
use Speicher210\FunctionalTestBundle\CoduoMatcherFactory;

final class DriverConfigurator
{
    private static Driver\Json|null $jsonDriver = null;

    private static Driver\Text|null $textDriver = null;

    private static Driver\Xml|null $xmlDriver = null;

    private static bool $outputUpdaterEnabled = false;

    /**
     * @param array<string,string> $fields          The fields to update in the expected output.
     * @param list<string>         $matcherPatterns
     */
    public static function createDrivers(
        array $fields,
        array $matcherPatterns,
    ): void {
        self::$jsonDriver = new Driver\Json(
            CoduoMatcherFactory::getMatcher(),
            $fields,
            $matcherPatterns,
        );
        self::$textDriver = new Driver\Text();
        self::$xmlDriver  = new Driver\Xml();
    }

    public static function getJsonDriver(): Driver\Json
    {
        if (self::$outputUpdaterEnabled === false) {
            throw new RuntimeException(
                Str\format(
                    'Updater is not enabled. You should call %s::enableOutputUpdater first to enable it.',
                    self::class,
                ),
            );
        }

        if (self::$jsonDriver === null) {
            throw new RuntimeException(
                Str\format(
                    'Updater is not created. You should call %s::createOutputUpdater first to create it.',
                    self::class,
                ),
            );
        }

        return self::$jsonDriver;
    }

    public static function getTextDriver(): Driver\Text
    {
        if (self::$outputUpdaterEnabled === false) {
            throw new RuntimeException(
                Str\format(
                    'Updater is not enabled. You should call %s::enableOutputUpdater first to enable it.',
                    self::class,
                ),
            );
        }

        if (self::$textDriver === null) {
            throw new RuntimeException(
                Str\format(
                    'Updater is not created. You should call %s::createOutputUpdater first to create it.',
                    self::class,
                ),
            );
        }

        return self::$textDriver;
    }

    public static function getXmlDriver(): Driver\Xml
    {
        if (self::$outputUpdaterEnabled === false) {
            throw new RuntimeException(
                Str\format(
                    'Updater is not enabled. You should call %s::enableOutputUpdater first to enable it.',
                    self::class,
                ),
            );
        }

        if (self::$xmlDriver === null) {
            throw new RuntimeException(
                Str\format(
                    'Updater is not created. You should call %s::createOutputUpdater first to create it.',
                    self::class,
                ),
            );
        }

        return self::$xmlDriver;
    }

    public static function isOutputUpdaterEnabled(): bool
    {
        return self::$outputUpdaterEnabled;
    }

    public static function enableOutputUpdater(): void
    {
        if (self::$jsonDriver === null || self::$textDriver === null || self::$xmlDriver === null) {
            throw new RuntimeException(
                Str\format(
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
