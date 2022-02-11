<?php

declare(strict_types=1);

namespace Speicher210\FunctionalTestBundle\Constraint;

use PHPUnit\Framework\Constraint\Constraint;
use Psl\Str;
use Psl\Type;
use Symfony\Component\HttpFoundation\Response;

final class ResponseStatusCodeSame extends Constraint
{
    private int $statusCode;

    public function __construct(int $statusCode)
    {
        $this->statusCode = $statusCode;
    }

    public function toString(): string
    {
        return 'status code is ' . $this->statusCode;
    }

    /**
     * @psalm-assert-if-true Response $other
     */
    protected function matches(mixed $other): bool
    {
        if ($other instanceof Response) {
            return $this->statusCode === $other->getStatusCode();
        }

        return false;
    }

    protected function failureDescription(mixed $other): string
    {
        if ($other instanceof Response) {
            return Str\format('response code "%s" matches expected "%s"', $other->getStatusCode(), $this->statusCode);
        }

        return parent::failureDescription($other);
    }

    protected function additionalFailureDescription(mixed $other): string
    {
        $responseContent = Type\object(Response::class)->coerce($other)->getContent();

        return Str\format(
            'Response body was: %s',
            $responseContent !== false ? $responseContent : ''
        );
    }
}
