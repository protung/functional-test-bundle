<?php

declare(strict_types=1);

namespace Speicher210\FunctionalTestBundle\Test\Loader;

use Doctrine\Common\DataFixtures\AbstractFixture;
use Doctrine\Common\Persistence\ObjectManager;
use Symfony\Component\DependencyInjection\ContainerAwareInterface;
use Symfony\Component\DependencyInjection\ContainerAwareTrait;
use Symfony\Component\DependencyInjection\ContainerInterface;

abstract class AbstractLoader extends AbstractFixture implements ContainerAwareInterface
{
    use ContainerAwareTrait;

    /** @var ObjectManager */
    private $manager;

    /**
     * Get the container.
     */
    protected function getContainer() : ?ContainerInterface
    {
        return $this->container;
    }

    /**
     * Code to run before loading the fixtures.
     */
    protected function beforeLoad() : void
    {
    }

    public function load(ObjectManager $manager) : void
    {
        $this->manager = $manager;

        $this->beforeLoad();
        $this->doLoad();
        $this->afterLoad();
    }

    /**
     * Code to run after loading the fixtures.
     */
    protected function afterLoad() : void
    {
        $this->manager->flush();
        $this->manager->clear();
    }

    abstract protected function doLoad() : void;

    public function getManager() : ObjectManager
    {
        return $this->manager;
    }

    /**
     * @param mixed $entity
     */
    public function persist($entity) : void
    {
        $this->getManager()->persist($entity);
    }
}
