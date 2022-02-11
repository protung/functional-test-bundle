<?php

declare(strict_types=1);

namespace Speicher210\FunctionalTestBundle;

use Coduo\PHPMatcher\Backtrace\VoidBacktrace;
use Coduo\PHPMatcher\Factory\MatcherFactory;
use Coduo\PHPMatcher\Matcher;

final class CoduoMatcherFactory
{
    private static Matcher|null $matcher = null;

    private function __construct()
    {
    }

    public static function getMatcher() : Matcher
    {
        if (self::$matcher === null) {
            self::$matcher = (new MatcherFactory())->createMatcher(new VoidBacktrace());
        }

        return self::$matcher;
    }
}
