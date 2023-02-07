<?php

declare(strict_types=1);

namespace Speicher210\FunctionalTestBundle\Test;

use PHPUnit\Framework\Constraint\Callback;

use function func_get_args;

trait PHPUnitHelper
{
    /**
     * @param array<mixed> ...$arguments
     *
     * @return Callback<mixed>
     */
    public static function withConsecutive(array ...$arguments): Callback
    {
        return new Callback(
            static function () use ($arguments): bool {
                static $call = 0;

                $expected = $arguments[$call] ?? [];

                self::assertEquals($expected, func_get_args());

                $call++;

                return true;
            },
        );
    }
}
