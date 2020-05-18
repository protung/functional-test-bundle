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
     * {@inheritdoc}
     */
    protected function matches($other) : bool
    {
        if ($other instanceof Response) {
            return $this->statusCode === $other->getStatusCode();
        }

        return false;
    }

    /**
     * {@inheritdoc}
     */
    protected function failureDescription($other) : string
    {
        if ($other instanceof Response) {
            return \sprintf('response code "%s" matches expected "%s"', $other->getStatusCode(), $this->statusCode);
        }

        return parent::failureDescription($other);
    }

    /**
     * {@inheritdoc}
     */
    protected function additionalFailureDescription($other) : string
    {
        return \sprintf('Response body was: %s', $other->getContent());
    }
}
