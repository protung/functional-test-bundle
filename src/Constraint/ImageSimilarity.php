<?php

declare(strict_types=1);

namespace Speicher210\FunctionalTestBundle\Constraint;

use Imagick;
use PHPUnit\Framework\Constraint\Constraint;
use Psl\Type;
use SebastianBergmann\Comparator\ComparisonFailure;

use function is_string;

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

    protected function fail(mixed $other, string $description, ComparisonFailure|null $comparisonFailure = null): never
    {
        parent::fail($other, $description, $comparisonFailure ?? $this->createComparisonFailure($other));
    }

    private function createComparisonFailure(mixed $other): ComparisonFailure|null
    {
        if (! is_string($other)) {
            return null;
        }

        return new ComparisonFailure(
            $this->expectedImageContent,
            $other,
            '', // we do not want to have a diff between the actual and expected if the failure is printed as the content is binary
            '', // we do not want to have a diff between the actual and expected if the failure is printed as the content is binary
            'Failed asserting that images are similar.',
        );
    }

    protected function failureDescription(mixed $other): string
    {
        return 'images are similar';
    }
}
