<?php

declare(strict_types=1);

namespace Speicher210\FunctionalTestBundle\Test\Bootstrap;

use Psl\Type;
use ReflectionMethod;
use Speicher210\FunctionalTestBundle\Test\KernelTestCase;
use Symfony\Component\HttpKernel\KernelInterface;

/**
 * Prepares what the tests need before the first one runs, such as the database schema.
 *
 * Call it from the PHPUnit bootstrap file: an exception thrown there stops the test run.
 */
final class TestEnvironment
{
    private function __construct()
    {
    }

    /**
     * Boots the test kernel, runs the steps in the given order and shuts the kernel down.
     *
     * @param callable(KernelInterface):mixed ...$steps
     */
    public static function prepare(callable ...$steps): void
    {
        $kernel = self::createKernel();
        $kernel->boot();

        try {
            foreach ($steps as $step) {
                $step($kernel);
            }
        } finally {
            $kernel->shutdown();
        }
    }

    private static function createKernel(): KernelInterface
    {
        $method = new ReflectionMethod(KernelTestCase::class, 'createKernel');

        return Type\instance_of(KernelInterface::class)->coerce($method->invoke(null));
    }
}
