<?php

declare(strict_types=1);

namespace Speicher210\FunctionalTestBundle\Constraint;

use PHPUnit\Framework\Constraint\Constraint;
use SebastianBergmann\Comparator\ComparisonFailure;
use Symfony\Component\HttpFoundation\Response;

final class ResponseHeaderSame extends Constraint
{
    use ConstraintExporter;
    use ConstraintEvaluate;

    /** @var string */
    private $headerName;

    /** @var string */
    private $expectedValue;

    public function __construct(string $headerName, string $expectedValue)
    {
        if (\method_exists(Constraint::class, '__construct')) {
            parent::__construct();
        }

        $this->headerName    = $headerName;
        $this->expectedValue = $expectedValue;
    }

    /**
     * @param mixed $other
     *
     * @return mixed|void
     *
     * {@inheritDoc}
     */
    protected function doEvaluate($other, string $description = '', bool $returnResult = false)
    {
        $success = false;

        if ($this->matches($other)) {
            $success = true;
        }

        if ($returnResult) {
            return $success;
        }

        if ($success) {
            return;
        }

        $actualValue       = $other->headers->get($this->headerName);
        $comparisonFailure = new ComparisonFailure(
            $this->expectedValue,
            $actualValue,
            $this->exporter()->export($this->expectedValue),
            $this->exporter()->export($actualValue)
        );

        $this->fail($other, $description, $comparisonFailure);
    }

    /**
     * {@inheritdoc}
     */
    public function toString() : string
    {
        return \sprintf('has header "%s" with value "%s"', $this->headerName, $this->expectedValue);
    }

    /**
     * @param Response $other
     *
     * {@inheritdoc}
     */
    protected function matches($other) : bool
    {
        return $this->expectedValue === $other->headers->get($this->headerName);
    }

    /**
     * @param Response $other
     *
     * {@inheritdoc}
     */
    protected function failureDescription($other) : string
    {
        return 'the response ' . $this->toString();
    }
}
