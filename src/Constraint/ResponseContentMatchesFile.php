<?php

declare(strict_types=1);

namespace Speicher210\FunctionalTestBundle\Constraint;

use Symfony\Component\HttpFoundation\Response;

use function file_get_contents;
use function sprintf;

final class ResponseContentMatchesFile extends ResponseContentConstraint
{
    private string $expectedFile;

    public function __construct(string $expectedFile)
    {
        $this->expectedFile = $expectedFile;
    }

    public function toString(): string
    {
        return sprintf('content matches "%s" file', $this->expectedFile);
    }

    /**
     * @psalm-assert-if-true Response $other
     */
    protected function matches(mixed $other): bool
    {
        if ($other instanceof Response) {
            return self::getMatcher()->match($other->getContent(), file_get_contents($this->expectedFile));
        }

        return false;
    }

    /**
     * {@inheritdoc}
     */
    protected function failureDescription($other): string
    {
        if ($other instanceof Response) {
            return sprintf('response content matches content of file "%s"', $this->expectedFile);
        }

        return parent::failureDescription($other);
    }
}
