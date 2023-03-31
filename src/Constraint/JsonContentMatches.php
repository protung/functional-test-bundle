<?php

declare(strict_types=1);

namespace Speicher210\FunctionalTestBundle\Constraint;

use PHPUnit\Util\Json;
use Psl;
use SebastianBergmann\Comparator\ComparisonFailure;

use function is_string;

final class JsonContentMatches extends ResponseContentConstraint
{
    private string $expectedContent;

    public function __construct(string $expectedContent)
    {
        $this->expectedContent = $expectedContent;
    }

    public function toString(): string
    {
        return 'content is ' . $this->expectedContent;
    }

    protected function matches(mixed $other): bool
    {
        if (is_string($other)) {
            return self::getMatcher()->match($other, $this->expectedContent);
        }

        return false;
    }

    protected function failureDescription(mixed $other): string
    {
        if (is_string($other)) {
            return Psl\Str\format(
                '"%s" matches JSON string "%s"',
                Json::prettify($other),
                Json::prettify($this->expectedContent),
            );
        }

        return parent::failureDescription($other);
    }

    /**
     * {@inheritDoc}
     */
    protected function fail(mixed $other, $description, ComparisonFailure|null $comparisonFailure = null): void
    {
        if (is_string($other) && $comparisonFailure === null) {
            [$error] = Json::canonicalize($other);

            if ($error) {
                parent::fail($other, $description);
            }

            [$error] = Json::canonicalize($this->expectedContent);

            if ($error) {
                parent::fail($other, $description);
            }

            $comparisonFailure = new ComparisonFailure(
                Psl\Json\decode($this->expectedContent, false),
                Psl\Json\decode($other, false),
                Json::prettify($this->expectedContent),
                Json::prettify($other),
                false,
                'Failed asserting that two json values are equal.',
            );
        }

        parent::fail($other, $description, $comparisonFailure);
    }
}
