<?php

declare(strict_types=1);

namespace Speicher210\FunctionalTestBundle\Constraint;

use PHPUnit\Framework\Constraint\Constraint;
use Psl\Type;

final class ImageSimilarity extends Constraint
{
    private string $expectedImageContent;

    private float $similarityThreshold;

    public function __construct(string $expectedImageContent, float $similarityThreshold)
    {
        $this->expectedImageContent = $expectedImageContent;
        $this->similarityThreshold  = $similarityThreshold;
    }

    public function toString() : string
    {
        return 'image is similar to ' . $this->expectedImageContent;
    }

    protected function matches(mixed $other) : bool
    {
        $expectedImagick = new \Imagick();
        $expectedImagick->readImageBlob($this->expectedImageContent);
        $actualImagick = new \Imagick();
        $actualImagick->readImageBlob(Type\string()->coerce($other));

        $result = $expectedImagick->compareImages($actualImagick, \Imagick::METRIC_MEANSQUAREERROR);

        return $result[1] <= $this->similarityThreshold;
    }

    /**
     * {@inheritdoc}
     */
    protected function failureDescription($other) : string
    {
        return \sprintf('images are similar');
    }
}
