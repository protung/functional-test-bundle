<?php

declare(strict_types=1);

namespace Speicher210\FunctionalTestBundle\Test\Symfony\Clock;

use Symfony\Component\Clock\Test\ClockSensitiveTrait;

trait SymfonyClockSensitiveTestCase
{
    use ClockSensitiveTrait;

    private static string $dateTimeToFreeze = '2022-09-13 17:48:56.123';

    protected function setUp(): void
    {
        self::mockTime(static::$dateTimeToFreeze);

        parent::setUp();
    }
}
