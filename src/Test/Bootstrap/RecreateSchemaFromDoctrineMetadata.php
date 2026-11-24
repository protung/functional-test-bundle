<?php

declare(strict_types=1);

namespace Speicher210\FunctionalTestBundle\Test\Bootstrap;

use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Tools\SchemaTool;
use Doctrine\Persistence\ManagerRegistry;
use Psl\Type;
use Symfony\Component\HttpKernel\KernelInterface;

/**
 * Drops the database schema and creates it again from the Doctrine metadata.
 */
final readonly class RecreateSchemaFromDoctrineMetadata
{
    /**
     * @param non-empty-string|null $entityManager The name of the entity manager, the default one if null.
     */
    public function __construct(private string|null $entityManager = null)
    {
    }

    public function __invoke(KernelInterface $kernel): void
    {
        $doctrine      = Type\instance_of(ManagerRegistry::class)->coerce($kernel->getContainer()->get('doctrine'));
        $entityManager = Type\instance_of(EntityManagerInterface::class)->coerce($doctrine->getManager($this->entityManager));

        $schemaTool = new SchemaTool($entityManager);
        $schemaTool->dropDatabase();
        $schemaTool->createSchema($entityManager->getMetadataFactory()->getAllMetadata());
    }
}
