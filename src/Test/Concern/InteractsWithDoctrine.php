<?php

declare(strict_types=1);

namespace Speicher210\FunctionalTestBundle\Test\Concern;

use Doctrine\DBAL\Connection;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\Persistence\ConnectionRegistry;
use Doctrine\Persistence\ManagerRegistry;
use Doctrine\Persistence\ObjectManager;
use Psl\Type;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

use function assert;

/**
 * Helpers for the Doctrine object manager, entities and database connections.
 *
 * @phpstan-require-extends KernelTestCase
 */
trait InteractsWithDoctrine
{
    /**
     * @param class-string<TService> $class
     * @param non-empty-string|null  $id
     *
     * @return TService
     *
     * @template TService of object
     */
    abstract protected function getContainerService(string $class, string|null $id = null): object;

    protected function getObjectManager(): ObjectManager
    {
        $doctrine = Type\instance_of(ManagerRegistry::class)->coerce(static::getContainer()->get('doctrine'));

        return $doctrine->getManager();
    }

    protected function getEntityManager(): EntityManagerInterface
    {
        return Type\instance_of(EntityManagerInterface::class)->coerce($this->getObjectManager());
    }

    protected function clearObjectManager(): void
    {
        $this->getObjectManager()->clear();
    }

    /**
     * @param class-string<TEntity> $entityClass
     *
     * @return TEntity
     *
     * @template TEntity of object
     */
    protected function getEntity(string $entityClass, int|string|object $entityId): object
    {
        $entity = $this->findEntity($entityClass, $entityId);

        assert($entity instanceof $entityClass);

        return $entity;
    }

    /**
     * @param class-string<TEntity> $entityClass
     *
     * @return TEntity|null
     *
     * @template TEntity of object
     */
    protected function findEntity(string $entityClass, int|string|object $entityId): object|null
    {
        return $this->getObjectManager()->find($entityClass, $entityId);
    }

    /**
     * @param class-string<TEntity> $entityClass
     *
     * @return iterable<TEntity>
     *
     * @template TEntity of object
     */
    protected function findAllEntities(string $entityClass): iterable
    {
        return $this->getObjectManager()->getRepository($entityClass)->findAll();
    }

    protected function getDefaultDatabaseConnection(): Connection
    {
        return $this->getContainerService(Connection::class);
    }

    protected function restartDatabaseSequences(): void
    {
        $connection = $this->getDefaultDatabaseConnection();

        $schemaManager = $connection->createSchemaManager();
        foreach ($schemaManager->listSequences() as $sequence) {
            $schemaManager->dropSequence($sequence->getName());
            $schemaManager->createSequence($sequence);
        }
    }

    private function closeDatabaseConnections(): void
    {
        $doctrine = Type\instance_of(ConnectionRegistry::class)->coerce(static::getContainer()->get('doctrine'));

        $connections = $doctrine->getConnections();
        foreach ($connections as $connection) {
            Type\instance_of(Connection::class)->coerce($connection)->close();
        }
    }
}
