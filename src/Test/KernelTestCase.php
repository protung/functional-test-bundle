<?php

declare(strict_types=1);

namespace Speicher210\FunctionalTestBundle\Test;

use Coduo\PHPMatcher\PHPUnit\PHPMatcherAssertions;
use Doctrine\Bundle\FixturesBundle\Loader\SymfonyFixturesLoader;
use Doctrine\Common\DataFixtures\Executor\ORMExecutor;
use Doctrine\DBAL\Connection;
use Doctrine\Persistence\ObjectManager;
use Speicher210\FunctionalTestBundle\Constraint\ImageSimilarity;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase as SymfonyKernelTestCase;
use Symfony\Component\HttpKernel\KernelInterface;

abstract class KernelTestCase extends SymfonyKernelTestCase
{
    use PHPMatcherAssertions;

    protected function setUp() : void
    {
        parent::setUp();

        static::bootKernel();

        $this->loadTestFixtures();
    }

    protected function tearDown() : void
    {
        /** @var \Doctrine\Persistence\ConnectionRegistry $doctrine */
        $doctrine = static::$container->get('doctrine');
        /** @var Connection[] $connections */
        $connections = $doctrine->getConnections();
        foreach ($connections as $connection) {
            $connection->close();
        }

        parent::tearDown();

        $this->cleanupPHPUnit();
    }

    /**
     * Unset test case properties to speed up GC.
     */
    protected function cleanupPHPUnit() : void
    {
        $reflection = new \ReflectionObject($this);
        foreach ($reflection->getProperties() as $property) {
            if ($property->isStatic() || \strpos($property->getDeclaringClass()->getName(), 'PHPUnit\\') === 0) {
                continue;
            }

            if (\PHP_VERSION_ID >= 70400 && ! $property->getType()->allowsNull()) {
                continue;
            }

            $property->setAccessible(true);
            $property->setValue($this, null);
        }
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

    protected function getObjectManager() : ObjectManager
    {
        /** @var \Doctrine\Persistence\ManagerRegistry $doctrine */
        $doctrine = static::$container->get('doctrine');

        return $doctrine->getManager();
    }

    protected function clearObjectManager() : void
    {
        $this->getObjectManager()->clear();
    }

    /**
     * Prepare the text fixtures and the expected content file.
     */
    protected function loadTestFixtures() : void
    {
        $reflection = new \ReflectionObject($this);

        $fixtures = $this->getAlwaysLoadingFixtures();

        $fixturesFile = \dirname($reflection->getFileName()) . '/Fixtures/' . $this->getName(false) . '.php';
        if (\file_exists($fixturesFile)) {
            $fixtures = \array_merge($fixtures, require $fixturesFile);
        }

        if (\count($fixtures) <= 0) {
            return;
        }

        $this->loadFixtures($fixtures);
    }

    /**
     * @param string[] $classNames
     */
    private function loadFixtures(array $classNames = []) : void
    {
        $fixtureLoader = new SymfonyFixturesLoader(static::$container);
        foreach ($classNames as $className) {
            $fixture = new $className();
            $fixtureLoader->addFixture($fixture);
        }

        /** @var \Doctrine\ORM\EntityManagerInterface $em */
        $em       = static::$container->get('doctrine.orm.entity_manager');
        $executor = new ORMExecutor($em);
        $executor->execute($fixtureLoader->getFixtures(), true);
    }

    /**
     * Get the fixtures to always load when preparing the test fixtures.
     *
     * @return string[]
     */
    protected function getAlwaysLoadingFixtures() : array
    {
        return [];
    }

    /**
     * @param string $expected  Binary content of expected image.
     * @param string $actual    Binary content of actual image.
     * @param float  $threshold Similarity threshold.
     * @param string $message   Fail message.
     */
    public static function assertImageSimilarity(
        string $expected,
        string $actual,
        float $threshold = 0.0,
        string $message = ''
    ) : void {
        static::assertThat($actual, new ImageSimilarity($expected, $threshold), $message);
    }
}
