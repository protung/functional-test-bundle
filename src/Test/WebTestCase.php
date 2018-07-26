<?php

declare(strict_types=1);

namespace Speicher210\FunctionalTestBundle\Test;

use Coduo\PHPMatcher\Factory\SimpleFactory;
use Coduo\PHPMatcher\Matcher;
use Doctrine\Common\Persistence\ObjectManager;
use Doctrine\DBAL\Connection;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Tools\SchemaTool;
use Liip\FunctionalTestBundle\Test\WebTestCase as LiipWebTestCase;
use Symfony\Bundle\FrameworkBundle\Client;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpKernel\KernelInterface;

/**
 * Abstract class for test cases.
 */
abstract class WebTestCase extends LiipWebTestCase
{
    /** @var object[] */
    private static $mockedServices = [];

    /** @var Matcher */
    private static $matcher;

    /**
     * Array with the number of assertions against expected files per test.
     *
     * @var array<string,int>
     */
    private $assertionExpectedFiles = [];

    /**
     * {@inheritdoc}
     */
    protected static function createKernel(array $options = []) : KernelInterface
    {
        $options['debug'] = false;

        return parent::createKernel($options);
    }

    /**
     * {@inheritdoc}
     */
    protected static function createClient(array $options = [], array $server = []) : Client
    {
        $client = parent::createClient($options, $server);

        $container = $client->getContainer();
        if (! $container instanceof ContainerInterface) {
            throw new \RuntimeException('Unknown container.');
        }
        foreach (self::$mockedServices as $id => $mock) {
            if (! $container->has($id)) {
                throw new \InvalidArgumentException(\sprintf('Cannot mock a non-existent service: "%s"', $id));
            }

            $container->set($id, $mock);
        }

        return $client;
    }

    public function setUp() : void
    {
        parent::setUp();

        /** @var EntityManager $emDefault */
        $emDefault = $this->getContainer()->get('doctrine.orm.entity_manager');
        $this->resetDatabaseSchema($emDefault);

        $this->prepareTestFixtures();
        $this->postFixtureSetup();

        self::$mockedServices = [];
    }

    protected function tearDown() : void
    {
        /** @var \Doctrine\Common\Persistence\ConnectionRegistry $doctrine */
        $doctrine = $this->getContainer()->get('doctrine');
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
     * Mock a container service.
     *
     * @param string $idService The service ID.
     * @param mixed  $mock      The mock.
     */
    protected function mockContainerService(string $idService, $mock) : void
    {
        self::$mockedServices[$idService] = $mock;
    }

    /**
     * Prepare the text fixtures and the expected content file.
     */
    protected function prepareTestFixtures() : void
    {
        $reflection = new \ReflectionObject($this);

        $fixturesFile = \dirname($reflection->getFileName()) . '/Fixtures/' . $this->getName(false) . '.php';

        $alwaysLoadingFixtures = $this->getAlwaysLoadingFixtures();

        if (\file_exists($fixturesFile)) {
            $this->loadFixtures(\array_merge($alwaysLoadingFixtures, require $fixturesFile));
        } elseif (\count($alwaysLoadingFixtures) > 0) {
            $this->loadFixtures($alwaysLoadingFixtures);
        }
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
     * Reset the database schema.
     *
     * @param EntityManagerInterface $em The entity manager.
     *
     * @throws \Doctrine\ORM\Tools\ToolsException
     */
    protected function resetDatabaseSchema(EntityManagerInterface $em) : void
    {
        $metaData = $em->getMetadataFactory()->getAllMetadata();

        $schemaTool = new SchemaTool($em);
        $schemaTool->dropDatabase();
        if ($metaData === []) {
            return;
        }

        $schemaTool->createSchema($metaData);
    }

    protected function getObjectManager() : ObjectManager
    {
        /** @var \Doctrine\Common\Persistence\ManagerRegistry $doctrine */
        $doctrine = $this->getContainer()->get('doctrine');

        return $doctrine->getManager();
    }

    protected static function getMatcher() : Matcher
    {
        if (self::$matcher === null) {
            $factory       = new SimpleFactory();
            self::$matcher = $factory->createMatcher();
        }

        return self::$matcher;
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
        if (! isset($this->assertionExpectedFiles[$testName])) {
            $this->assertionExpectedFiles[$testName] = 1;
        } else {
            $this->assertionExpectedFiles[$testName]++;
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
        float $threshold = 0,
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
