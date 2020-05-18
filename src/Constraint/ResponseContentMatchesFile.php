<?php

declare(strict_types=1);

namespace Speicher210\FunctionalTestBundle\Constraint;

use Symfony\Component\HttpFoundation\Response;

final class ResponseContentMatchesFile extends ResponseContentConstraint
{
    /** @var string */
    private $expectedFile;

    public function __construct(string $expectedFile)
    {
        $this->expectedFile = $expectedFile;
    }

    /**
     * {@inheritdoc}
     */
    public function toString() : string
    {
        return \sprintf('content matches "%s" file', $this->expectedFile);
    }

    /**
     * {@inheritdoc}
     */
    protected function matches($other) : bool
    {
        if ($other instanceof Response) {
            return static::getMatcher()->match($other->getContent(), \file_get_contents($this->expectedFile));
        }

        return false;
    }

    /**
     * {@inheritdoc}
     */
    protected function failureDescription($other) : string
    {
        if ($other instanceof Response) {
            return \sprintf('response content matches content of file "%s"', $this->expectedFile);
        }

        return parent::failureDescription($other);
    }
}
