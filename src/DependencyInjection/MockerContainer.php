<?php

namespace Speicher210\FunctionalTestBundle\DependencyInjection;

use Symfony\Component\DependencyInjection\Container;

/**
 * Container for mocking services.
 */
class MockerContainer extends Container
{
    /**
     * @var array $mockedServices
     */
    static private $mockedServices = array();

    /**
     * Takes an id of the service as the first argument.
     * Any other arguments are passed to the Mockery factory.
     *
     * @param string $id The service ID.
     * @param mixed $mock The mocked service. Will be set only if not previously set.
     *
     * @return mixed
     */
    public function mock($id, $mock)
    {
        if (!$this->has($id)) {
            throw new \InvalidArgumentException(sprintf('Cannot mock a non-existent service: "%s"', $id));
        }

        if (!array_key_exists($id, self::$mockedServices)) {
            self::$mockedServices[$id] = $mock;
        }

        return self::$mockedServices[$id];
    }

    /**
     * Unmock a service.
     *
     * @param string $id
     */
    public function unmock($id)
    {
        unset(self::$mockedServices[$id]);
    }

    /**
     * Unmock all mocked services.
     */
    public function unmockAll()
    {
        self::$mockedServices = array();
    }

    /**
     * {@inheritdoc}
     */
    public function get($id, $invalidBehavior = self::EXCEPTION_ON_INVALID_REFERENCE)
    {
        if (array_key_exists($id, self::$mockedServices)) {
            return self::$mockedServices[$id];
        }

        return parent::get($id, $invalidBehavior);
    }

    /**
     * {@inheritdoc}
     */
    public function has($id)
    {
        if (array_key_exists($id, self::$mockedServices)) {
            return true;
        }

        return parent::has($id);
    }
}
