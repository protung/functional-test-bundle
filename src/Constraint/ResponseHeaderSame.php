<?php

declare(strict_types=1);

namespace Speicher210\FunctionalTestBundle\Constraint;

use PHPUnit\Framework\Constraint\Constraint;
use Psl\Str;
use Psl\Type;
use SebastianBergmann\Comparator\ComparisonFailure;
use SebastianBergmann\Exporter\Exporter;
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

    public function evaluate(mixed $other, string $description = '', bool $returnResult = false): bool|null
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

        $valueExporter = new Exporter();

        $actualValue       = Type\instance_of(Response::class)->coerce($other)->headers->get($this->headerName);
        $comparisonFailure = new ComparisonFailure(
            $this->expectedValue,
            $actualValue,
            $valueExporter->export($this->expectedValue),
            $valueExporter->export($actualValue),
        );

        $this->fail($other, $description, $comparisonFailure);
    }

    public function toString(): string
    {
        return Str\format('has header "%s" with value "%s"', $this->headerName, $this->expectedValue);
    }

    /**
     * @psalm-assert-if-true Response $other
     */
    protected function matches(mixed $other): bool
    {
        if ($other instanceof Response) {
            return $this->expectedValue === $other->headers->get($this->headerName);
        }

        return false;
    }

    protected function failureDescription(mixed $other): string
    {
        if ($other instanceof Response) {
            return 'the response ' . $this->toString();
        }

        return parent::failureDescription($other);
    }
}
