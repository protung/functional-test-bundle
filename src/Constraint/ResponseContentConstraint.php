<?php

declare(strict_types=1);

namespace Speicher210\FunctionalTestBundle\Constraint;

use Coduo\PHPMatcher\Matcher;
use PHPUnit\Framework\Constraint\Constraint;
use Psl\Type;
use Speicher210\FunctionalTestBundle\CoduoMatcherFactory;
use Symfony\Component\HttpFoundation\Response;

abstract class ResponseContentConstraint extends Constraint
{
    private static Matcher|null $matcher = null;

    protected static function getMatcher() : Matcher
    {
        if (self::$matcher === null) {
            self::$matcher = CoduoMatcherFactory::getMatcher();
        }

        return self::$matcher;
    }

    protected function additionalFailureDescription(mixed $other) : string
    {
        if ($other instanceof Response) {
            return Type\string()->coerce(static::getMatcher()->getError());
        }

        return parent::additionalFailureDescription($other);
    }
}
