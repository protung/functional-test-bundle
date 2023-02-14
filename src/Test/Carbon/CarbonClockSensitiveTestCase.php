<?php

declare(strict_types=1);

namespace Speicher210\FunctionalTestBundle\Test\Carbon;

use Carbon\Carbon;
use Carbon\CarbonImmutable;
use DateTimeImmutable;
use DateTimeInterface;

trait CarbonClockSensitiveTestCase
{
    private static string $dateTimeToFreeze = '2022-09-13 17:48:56.123';

    protected static function freezeClock(DateTimeInterface|string|null $dateTime = null): void
    {
        if ($dateTime === null) {
            $dateTime = (new DateTimeImmutable('now'))->format(DateTimeInterface::RFC3339_EXTENDED);
        }

        Carbon::setTestNow($dateTime);
        CarbonImmutable::setTestNow($dateTime);
    }

    protected static function unfreezeClock(): void
    {
        Carbon::setTestNow(null);
        CarbonImmutable::setTestNow(null);
    }

    protected function setUp(): void
    {
        self::freezeClock(static::$dateTimeToFreeze);

        parent::setUp();
    }

    protected function tearDown(): void
    {
        self::unfreezeClock();

        parent::tearDown();
    }
}
