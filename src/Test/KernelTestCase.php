<?php

declare(strict_types=1);

namespace Speicher210\FunctionalTestBundle\Test;

use Coduo\PHPMatcher\PHPUnit\PHPMatcherAssertions;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase as SymfonyKernelTestCase;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpKernel\Kernel;
use Symfony\Component\HttpKernel\KernelInterface;

abstract class KernelTestCase extends SymfonyKernelTestCase
{
    use PHPMatcherAssertions;

    /**
     * Property to hold the container for Symfony < 4.1 compatibility.
     *
     * @var ContainerInterface
     */
    protected static $container;

    protected function setUp() : void
    {
        parent::setUp();

        static::bootKernel();
    }

    /**
     * {@inheritdoc}
     */
    protected static function bootKernel(array $options = []) : KernelInterface
    {
        $kernel = parent::bootKernel($options);

        // Symfony < 4.1 compatibility.
        /** @var bool $isSymfony4 */
        $isSymfony4 = \version_compare(Kernel::VERSION, '4.1.0', '>='); // make PHPStan happy
        if ($isSymfony4 === false) {
            static::$container = static::$kernel->getContainer();
        }

        return $kernel;
    }

    /**
     * {@inheritdoc}
     */
    protected static function createKernel(array $options = []) : KernelInterface
    {
        $options['debug']       = $options['debug'] ?? false;
        $options['environment'] = $options['environment'] ?? 'test';

        return parent::createKernel($options);
    }

    /**
     * @return mixed
     */
    protected function getContainerService(string $id)
    {
        if (static::$container->has('test.' . $id)) {
            $id = 'test.' . $id;
        }

        return static::$container->get($id);
    }

    /**
     * @param mixed $service
     */
    protected function mockContainerService(string $id, $service) : void
    {
        if (static::$container->has('test.' . $id)) {
            $id = 'test.' . $id;
        }

        static::$container->set($id, $service);
    }
}
