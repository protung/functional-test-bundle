<?php

declare(strict_types=1);

namespace Speicher210\FunctionalTestBundle\Constraint;

use Coduo\PHPMatcher\Factory\MatcherFactory;
use Coduo\PHPMatcher\Matcher;
use PHPUnit\Framework\Constraint\Constraint;
use Symfony\Component\HttpFoundation\Response;

abstract class ResponseContentConstraint extends Constraint
{
    /** @var Matcher */
    private static $matcher;

    protected static function getMatcher() : Matcher
    {
        if (self::$matcher === null) {
            $factory       = new MatcherFactory();
            self::$matcher = $factory->createMatcher();
        }

        return self::$matcher;
    }

    /**
     * {@inheritdoc}
     */
    protected function additionalFailureDescription($other) : string
    {
        if ($other instanceof Response) {
            return static::getMatcher()->getError();
        }

        return parent::additionalFailureDescription($other);
    }
}
