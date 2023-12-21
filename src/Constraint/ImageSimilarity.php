<?php

declare(strict_types=1);

namespace Speicher210\FunctionalTestBundle\Constraint;

use Imagick;
use PHPUnit\Framework\Constraint\Constraint;
use Psl\Type;

final class ImageSimilarity extends Constraint
{
    private string $expectedImageContent;

    private float $similarityDelta;

    public function __construct(string $expectedImageContent, float $similarityDelta)
    {
        $this->expectedImageContent = $expectedImageContent;
        $this->similarityDelta      = $similarityDelta;
    }

    public function toString(): string
    {
        return 'image is similar to ' . $this->expectedImageContent;
    }

    protected function matches(mixed $other): bool
    {
        $expectedImagick = new Imagick();
        $expectedImagick->readImageBlob($this->expectedImageContent);
        $actualImagick = new Imagick();
        $actualImagick->readImageBlob(Type\string()->coerce($other));

        $result = $expectedImagick->compareImages($actualImagick, Imagick::METRIC_MEANSQUAREERROR);

        return $result[1] <= $this->similarityDelta;
    }

    protected function failureDescription(mixed $other): string
    {
        return 'images are similar';
    }
}
