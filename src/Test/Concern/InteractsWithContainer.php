<?php

declare(strict_types=1);

namespace Speicher210\FunctionalTestBundle\Test\Concern;

use Psl\Type;
use RuntimeException;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\HttpKernel\KernelInterface;

/**
 * Creates the test kernel and gives access to container services, also to replace them with mocks.
 *
 * @phpstan-require-extends KernelTestCase
 */
trait InteractsWithContainer
{
    /**
     * @param array<mixed> $options
     */
    protected static function createKernel(array $options = []): KernelInterface
    {
        $options['debug']       ??= false;
        $options['environment'] ??= 'test';

        return parent::createKernel($options);
    }

    protected static function getKernel(): KernelInterface
    {
        if (static::$kernel === null) {
            throw new RuntimeException('Kernel not created. Was it booted ?');
        }

        return static::$kernel;
    }

    /**
     * @param class-string<TService> $class
     * @param non-empty-string|null  $id
     *
     * @return TService
     *
     * @template TService of object
     */
    protected function getContainerService(string $class, string|null $id = null): object
    {
        $id ??= $class;

        if (static::getContainer()->has('test.' . $id)) {
            $service = static::getContainer()->get('test.' . $id);
        } else {
            $service = static::getContainer()->get($id);
        }

        return Type\instance_of($class)->coerce($service);
    }

    private function mockServices(): void
    {
        foreach ($this->servicesToMock() as $id => $service) {
            $this->mockContainerService($id, $service);
        }
    }

    /**
     * @return iterable<non-empty-string,object>
     */
    protected function servicesToMock(): iterable
    {
        return [];
    }

    /**
     * @param non-empty-string $id
     */
    protected function mockContainerService(string $id, object $service): void
    {
        if (static::getContainer()->has('test.' . $id)) {
            $id = 'test.' . $id;
        }

        static::getContainer()->set($id, $service);
    }
}
