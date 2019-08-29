<?php

declare(strict_types=1);

namespace Speicher210\FunctionalTestBundle\Test;

use Doctrine\Bundle\FixturesBundle\Loader\SymfonyFixturesLoader;
use Doctrine\Common\DataFixtures\Executor\ORMExecutor;
use Doctrine\Common\Persistence\ObjectManager;
use Doctrine\DBAL\Connection;
use Symfony\Bundle\FrameworkBundle\Client;
use Symfony\Component\DependencyInjection\ContainerInterface;

abstract class WebTestCase extends KernelTestCase
{
    /**
     * Array with the number of assertions against expected files per test.
     *
     * @var array<string,int>
     */
    private $assertionExpectedFiles = [];

    /**
     * {@inheritdoc}
     */
    protected static function createClient(array $server = []) : Client
    {
        /** @var \Symfony\Bundle\FrameworkBundle\Client $client */
        $client = static::$container->get('test.client');
        $client->setServerParameters($server);

        $container = $client->getContainer();
        if (! $container instanceof ContainerInterface) {
            throw new \RuntimeException('Unknown container.');
        }

        return $client;
    }

    protected function setUp() : void
    {
        parent::setUp();

        $this->loadTestFixtures();
    }

    protected function tearDown() : void
    {
        /** @var \Doctrine\Common\Persistence\ConnectionRegistry $doctrine */
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

            $property->setAccessible(true);
            $property->setValue($this, null);
        }
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
     * Get the fixtures to always load when preparing the test fixtures.
     *
     * @return string[]
     */
    protected function getAlwaysLoadingFixtures() : array
    {
        return [];
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

    protected function getObjectManager() : ObjectManager
    {
        /** @var \Doctrine\Common\Persistence\ManagerRegistry $doctrine */
        $doctrine = static::$container->get('doctrine');

        return $doctrine->getManager();
    }

    /**
     * Get the expected response content file.
     *
     * @param string $type The file type (txt, yml, etc).
     */
    protected function getExpectedResponseContentFile(string $type) : string
    {
        $reflection = new \ReflectionObject($this);
        $testName   = $this->getName(false);
        if (isset($this->assertionExpectedFiles[$testName])) {
            $this->assertionExpectedFiles[$testName]++;
        } else {
            $this->assertionExpectedFiles[$testName] = 1;
        }

        $expectedFile = $testName . '-' . $this->assertionExpectedFiles[$testName] . '.' . $type;

        return \dirname($reflection->getFileName()) . '/Expected/' . $expectedFile;
    }

    public function getCurrentExpectedResponseContentFile(string $type) : string
    {
        $reflection       = new \ReflectionObject($this);
        $testName         = $this->getName(false);
        $expectedFileName = $this->getName(false) . '-' . $this->assertionExpectedFiles[$testName] ?? 1;

        return \dirname($reflection->getFileName()) . '/Expected/' . $expectedFileName . '.' . $type;
    }

    /**
     * @param string $expected  Binary content of expected image.
     * @param string $actual    Binary content of actual image.
     * @param float  $threshold Similarity threshold.
     * @param string $message   Fail message.
     */
    protected function assertImagesSimilarity(
        string $expected,
        string $actual,
        float $threshold = 0.0,
        string $message = 'Failed asserting that images are similar.'
    ) : void {
        $expectedImagick = new \Imagick();
        $expectedImagick->readImageBlob($expected);
        $actualImagick = new \Imagick();
        $actualImagick->readImageBlob($actual);

        $result = $expectedImagick->compareImages($actualImagick, \Imagick::METRIC_MEANSQUAREERROR);

        static::assertLessThanOrEqual($threshold, $result[1], $message);
    }
}
