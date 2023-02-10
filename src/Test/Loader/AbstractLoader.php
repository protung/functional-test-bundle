<?php

declare(strict_types=1);

namespace Speicher210\FunctionalTestBundle\Test\Loader;

use Doctrine\Common\DataFixtures\AbstractFixture;
use Doctrine\Persistence\ObjectManager;
use Generator;

abstract class AbstractLoader extends AbstractFixture
{
    public function load(ObjectManager $manager): void
    {
        foreach ($this->doLoad() as $entity) {
            $manager->persist($entity);
        }

        $manager->flush();
        $manager->clear();
    }

    /**
     * @return Generator<object>
     */
    abstract protected function doLoad(): Generator;
}
