<?php

declare(strict_types = 1);

namespace Speicher210\FunctionalTestBundle\Test;

use Coduo\PHPMatcher\Factory\SimpleFactory;
use Coduo\PHPMatcher\Matcher;
use Doctrine\Common\Persistence\ObjectManager;
use Doctrine\DBAL\Connection;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Tools\SchemaTool;
use Liip\FunctionalTestBundle\Test\WebTestCase as LiipWebTestCase;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Abstract class for test cases.
 */
abstract class WebTestCase extends LiipWebTestCase
{
    private static $mockedServices = [];

    /**
     * @var Matcher
     */
    private static $matcher;

    /**
     * Array with the number of assertions against expected files per test.
     *
     * @var array
     */
    private $assertionExpectedFiles = [];

    /**
     * {@inheritdoc}
     */
    protected static function createKernel(array $options = [])
    {
        $options['debug'] = false;

        return parent::createKernel($options);
    }

    /**
     * {@inheritdoc}
     */
    protected static function createClient(array $options = [], array $server = [])
    {
        $client = parent::createClient($options, $server);

        $container = $client->getContainer();
        if (!$container instanceof ContainerInterface) {
            throw new \RuntimeException('Unknown container.');
        }
        foreach (self::$mockedServices as $id => $mock) {
            if (!$container->has($id)) {
                throw new \InvalidArgumentException(\sprintf('Cannot mock a non-existent service: "%s"', $id));
            }

            $container->set($id, $mock);
        }

        return $client;
    }

    /**
     * {@inheritdoc}
     */
    public function setUp()
    {
        parent::setUp();

        /** @var EntityManager $emDefault */
        $emDefault = $this->getContainer()->get('doctrine.orm.entity_manager');
        $this->resetDatabaseSchema($emDefault);

        $this->prepareTestFixtures();
        $this->postFixtureSetup();

        self::$mockedServices = [];
    }

    /**
     * {@inheritdoc}
     */
    protected function tearDown()
    {
        $container = $this->getContainer();

        /** @var Connection[] $connections */
        $connections = $container->get('doctrine')->getConnections();
        foreach ($connections as $connection) {
            $connection->close();
        }

        parent::tearDown();

        $this->cleanupPHPUnit();
    }

    /**
     * Unset test case properties to speed up GC.
     */
    protected function cleanupPHPUnit(): void
    {
        $reflection = new \ReflectionObject($this);
        foreach ($reflection->getProperties() as $property) {
            if (!$property->isStatic() && 0 !== \strpos($property->getDeclaringClass()->getName(), 'PHPUnit\\')) {
                $property->setAccessible(true);
                $property->setValue($this, null);
            }
        }
    }

    /**
     * Mock a container service.
     *
     * @param string $idService The service ID.
     * @param mixed $mock The mock.
     */
    protected function mockContainerService(string $idService, $mock): void
    {
        self::$mockedServices[$idService] = $mock;
    }

    /**
     * Prepare the text fixtures and the expected content file.
     */
    protected function prepareTestFixtures(): void
    {
        $reflection = new \ReflectionObject($this);

        $fixturesFile = \dirname($reflection->getFileName()) . '/Fixtures/' . $this->getName() . '.php';

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
     * @return array
     */
    protected function getAlwaysLoadingFixtures(): array
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
    protected function resetDatabaseSchema(EntityManagerInterface $em): void
    {
        $metaData = $em->getMetadataFactory()->getAllMetadata();

        $schemaTool = new SchemaTool($em);
        $schemaTool->dropDatabase();
        if (!empty($metaData)) {
            $schemaTool->createSchema($metaData);
        }
    }

    /**
     * Get the object manager.
     *
     * @return ObjectManager
     */
    protected function getObjectManager(): ObjectManager
    {
        return $this->getContainer()->get('doctrine')->getManager();
    }

    /**
     * @return Matcher
     */
    protected static function getMatcher(): Matcher
    {
        if (self::$matcher === null) {
            $factory = new SimpleFactory();
            self::$matcher = $factory->createMatcher();
        }

        return self::$matcher;
    }

    /**
     * Get the expected response content file.
     *
     * @param string $type The file type (txt, yml, etc).
     *
     * @return string
     */
    protected function getExpectedResponseContentFile(string $type): string
    {
        $reflection = new \ReflectionObject($this);
        $testName = $this->getName(false);
        if (!isset($this->assertionExpectedFiles[$testName])) {
            $this->assertionExpectedFiles[$testName] = 1;
        } else {
            $this->assertionExpectedFiles[$testName]++;
        }

        $expectedFile = $testName . '-' . $this->assertionExpectedFiles[$testName] . '.' . $type;

        return \dirname($reflection->getFileName()) . '/Expected/' . $expectedFile;
    }

    /**
     * Get current expected response content file.
     *
     * @param string $type
     *
     * @return string
     */
    public function getCurrentExpectedResponseContentFile(string $type): string
    {
        $reflection = new \ReflectionObject($this);
        $testName = $this->getName(false);
        $expectedFileName = $this->getName(false) . '-' . $this->assertionExpectedFiles[$testName] ?? 1;

        return \dirname($reflection->getFileName()) . '/Expected/' . $expectedFileName . '.' . $type;
    }

    /**
     * @param string $expected Binary content of expected image.
     * @param string $actual Binary content of actual image.
     * @param float $threshold Similarity threshold.
     * @param string $message Fail message.
     */
    protected function assertImagesSimilarity(
        string $expected,
        string $actual,
        float $threshold = 0,
        string $message = 'Failed asserting that images are similar.'
    ): void {
        $expectedImagick = new \Imagick();
        $expectedImagick->readImageBlob($expected);
        $actualImagick = new \Imagick();
        $actualImagick->readImageBlob($actual);

        $result = $expectedImagick->compareImages($actualImagick, \Imagick::METRIC_MEANSQUAREERROR);

        static::assertLessThanOrEqual($threshold, $result[1], $message);
    }
}
