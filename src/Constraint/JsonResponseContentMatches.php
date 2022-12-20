<?php

declare(strict_types=1);

namespace Speicher210\FunctionalTestBundle\Constraint;

use PHPUnit\Util\Json;
use Psl;
use SebastianBergmann\Comparator\ComparisonFailure;
use Symfony\Component\HttpFoundation\Response;

final class JsonResponseContentMatches extends ResponseContentConstraint
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
        if ($other instanceof Response) {
            return self::getMatcher()->match($other->getContent(), $this->expectedContent);
        }

        return false;
    }

    protected function failureDescription(mixed $other): string
    {
        if ($other instanceof Response) {
            return Psl\Str\format(
                '"%s" matches JSON string "%s"',
                Json::prettify(Psl\Type\string()->coerce($other->getContent())),
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
        if ($other instanceof Response) {
            $actual = Psl\Type\string()->coerce($other->getContent());
            if ($comparisonFailure === null) {
                [$error] = Json::canonicalize($actual);

                if ($error) {
                    parent::fail($other, $description);
                }

                [$error] = Json::canonicalize($this->expectedContent);

                if ($error) {
                    parent::fail($other, $description);
                }

                $comparisonFailure = new ComparisonFailure(
                    Psl\Json\decode($this->expectedContent, false),
                    Psl\Json\decode($actual, false),
                    Json::prettify($this->expectedContent),
                    Json::prettify($actual),
                    false,
                    'Failed asserting that two json values are equal.',
                );
            }
        }

        parent::fail($other, $description, $comparisonFailure);
    }
}
