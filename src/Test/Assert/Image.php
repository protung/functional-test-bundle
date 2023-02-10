<?php

declare(strict_types=1);

namespace Speicher210\FunctionalTestBundle\Test\Assert;

use Speicher210\FunctionalTestBundle\Constraint\ImageSimilarity;

trait Image
{
    /**
     * @param string $expected  Binary content of expected image.
     * @param string $actual    Binary content of actual image.
     * @param float  $threshold Similarity threshold.
     * @param string $message   Fail message.
     */
    public static function assertImageSimilarity(
        string $expected,
        string $actual,
        float $threshold = 0.0,
        string $message = '',
    ): void {
        static::assertThat($actual, new ImageSimilarity($expected, $threshold), $message);
    }
}
