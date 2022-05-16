<?php

declare(strict_types=1);

namespace Speicher210\FunctionalTestBundle\Constraint;

use PHPUnit\Framework\Constraint\Constraint;
use Psl\Type;
use SebastianBergmann\Comparator\ComparisonFailure;
use Symfony\Component\HttpFoundation\Response;

final class ResponseHeaderSame extends Constraint
{
    private string $headerName;

    private string $expectedValue;

    public function __construct(string $headerName, string $expectedValue)
    {
        $this->headerName    = $headerName;
        $this->expectedValue = $expectedValue;
    }

    public function evaluate(mixed $other, string $description = '', bool $returnResult = false) : ?bool
    {
        $success = false;

        if ($this->matches($other)) {
            $success = true;
        }

        if ($returnResult) {
            return $success;
        }

        if ($success) {
            return null;
        }

        $actualValue       = Type\instance_of(Response::class)->coerce($other)->headers->get($this->headerName);
        $comparisonFailure = new ComparisonFailure(
            $this->expectedValue,
            $actualValue,
            $this->exporter()->export($this->expectedValue),
            $this->exporter()->export($actualValue)
        );

        $this->fail($other, $description, $comparisonFailure);
    }

    public function toString() : string
    {
        return \sprintf('has header "%s" with value "%s"', $this->headerName, $this->expectedValue);
    }

    /**
     * @psalm-assert-if-true Response $other
     */
    protected function matches(mixed $other) : bool
    {
        if ($other instanceof Response) {
            return $this->expectedValue === $other->headers->get($this->headerName);
        }

        return false;
    }

    /**
     * {@inheritdoc}
     */
    protected function failureDescription($other) : string
    {
        if ($other instanceof Response) {
            return 'the response ' . $this->toString();
        }

        return parent::failureDescription($other);
    }
}
