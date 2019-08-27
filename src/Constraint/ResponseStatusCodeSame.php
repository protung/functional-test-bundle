<?php

declare(strict_types=1);

namespace Speicher210\FunctionalTestBundle\Constraint;

use PHPUnit\Framework\Constraint\Constraint;
use Symfony\Component\HttpFoundation\Response;

final class ResponseStatusCodeSame extends Constraint
{
    /** @var int */
    private $statusCode;

    public function __construct(int $statusCode)
    {
        $this->statusCode = $statusCode;
    }

    /**
     * {@inheritdoc}
     */
    public function toString() : string
    {
        return 'status code is ' . $this->statusCode;
    }

    /**
     * @param Response $other
     *
     * {@inheritdoc}
     */
    protected function matches($other) : bool
    {
        return $this->statusCode === $other->getStatusCode();
    }

    /**
     * @param Response $other
     *
     * {@inheritdoc}
     */
    protected function failureDescription($other) : string
    {
        return \sprintf('response code "%s" matches expected "%s"', $other->getStatusCode(), $this->statusCode);
    }

    /**
     * @param Response $other
     *
     * {@inheritdoc}
     */
    protected function additionalFailureDescription($other) : string
    {
        return \sprintf('Response body was: %s', $other->getContent());
    }
}
