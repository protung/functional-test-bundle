<?php

declare(strict_types=1);

namespace Speicher210\FunctionalTestBundle\FailTestExpectedOutputFileUpdater;

use Speicher210\FunctionalTestBundle\CoduoMatcherFactory;

final class ExpectedOutputFileUpdaterConfigurator
{
    /** @var JsonFileUpdater */
    private static $outputUpdater;

    /** @var bool */
    private static $outputUpdaterEnabled = false;

    /**
     * @param string[] $fields          The fields to update in the expected output.
     * @param string[] $matcherPatterns
     */
    public static function createOutputUpdater(array $fields, array $matcherPatterns) : void
    {
        self::$outputUpdater = new JsonFileUpdater(
            CoduoMatcherFactory::getMatcher(),
            $fields,
            $matcherPatterns
        );
    }

    public static function getOutputUpdater() : JsonFileUpdater
    {
        if (self::$outputUpdaterEnabled === false) {
            throw new \RuntimeException(
                \sprintf(
                    'Updater is not enabled. You should call %s::enableOutputUpdater first to enable it.',
                    self::class
                )
            );
        }

        if (self::$outputUpdater === null) {
            throw new \RuntimeException(
                \sprintf(
                    'Updater is not created. You should call %s::createOutputUpdater first to create it.',
                    self::class
                )
            );
        }

        return self::$outputUpdater;
    }

    public static function isOutputUpdaterEnabled() : bool
    {
        return self::$outputUpdaterEnabled;
    }

    public static function enableOutputUpdater() : void
    {
        if (self::$outputUpdater === null) {
            throw new \RuntimeException(
                \sprintf(
                    'Updater is not created. You should call %s::createOutputUpdater first to create it.',
                    self::class
                )
            );
        }

        self::$outputUpdaterEnabled = true;
    }

    public static function disableOutputUpdater() : void
    {
        self::$outputUpdaterEnabled = false;
    }
}
