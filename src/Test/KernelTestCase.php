<?php

declare(strict_types=1);

namespace Speicher210\FunctionalTestBundle\Test;

use Coduo\PHPMatcher\PHPUnit\PHPMatcherAssertions;
use Doctrine\Bundle\FixturesBundle\Loader\SymfonyFixturesLoader;
use Doctrine\Common\DataFixtures\Executor\ORMExecutor;
use Doctrine\Common\DataFixtures\FixtureInterface;
use Doctrine\DBAL\Connection;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\Persistence\ConnectionRegistry;
use Doctrine\Persistence\ManagerRegistry;
use Doctrine\Persistence\ObjectManager;
use Psl\Filesystem;
use Psl\Str;
use Psl\Type;
use Psl\Vec;
use ReflectionObject;
use RuntimeException;
use Speicher210\FunctionalTestBundle\Constraint\ImageSimilarity;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase as SymfonyKernelTestCase;
use Symfony\Component\HttpKernel\KernelInterface;

use function class_exists;
use function count;
use function interface_exists;
use function str_starts_with;

abstract class KernelTestCase extends SymfonyKernelTestCase
{
    use PHPMatcherAssertions;

    /**
     * Array with the number of assertions against expected files per test.
     *
     * @var array<string,int>
     */
    private array $assertionExpectedFiles = [];

    protected function setUp(): void
    {
        parent::setUp();

        static::bootKernel();

        $this->loadTestFixtures();
    }

    protected function tearDown(): void
    {
        $doctrine = Type\instance_of(ConnectionRegistry::class)->coerce(static::getContainer()->get('doctrine'));

        $connections = $doctrine->getConnections();
        foreach ($connections as $connection) {
            Type\instance_of(Connection::class)->coerce($connection)->close();
        }

        parent::tearDown();

        $this->cleanupPHPUnit();
    }

    /**
     * Unset test case properties to speed up GC.
     */
    protected function cleanupPHPUnit(): void
    {
        $reflection = new ReflectionObject($this);
        foreach ($reflection->getProperties() as $property) {
            if ($property->isStatic() || str_starts_with($property->getDeclaringClass()->getName(), 'PHPUnit\\')) {
                continue;
            }

            if ($property->getType() !== null && ! $property->getType()->allowsNull()) {
                continue;
            }

            $property->setAccessible(true);
            $property->setValue($this, null);
        }
    }

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
     * @param non-empty-string|class-string<TService> $id
     * @phpstan-param (non-empty-string&literal-string)|class-string<TService> $id
     *
     * @return TService
     *
     * @template TService of object
     */
    protected function getContainerService(string $id): object
    {
        if (static::getContainer()->has('test.' . $id)) {
            $service = static::getContainer()->get('test.' . $id);
        } else {
            $service = static::getContainer()->get($id);
        }

        if ($service === null) {
            throw new RuntimeException(Str\format('Service "%s" does not exist in the container.', $id));
        }

        if (class_exists($id, false) || interface_exists($id, false)) {
            return Type\instance_of($id)->coerce($service);
        }

        return $service;
    }

    protected function mockContainerService(string $id, object $service): void
    {
        if (static::getContainer()->has('test.' . $id)) {
            $id = 'test.' . $id;
        }

        static::getContainer()->set($id, $service);
    }

    protected function getObjectManager(): ObjectManager
    {
        $doctrine = Type\instance_of(ManagerRegistry::class)->coerce(static::getContainer()->get('doctrine'));

        return $doctrine->getManager();
    }

    protected function clearObjectManager(): void
    {
        $this->getObjectManager()->clear();
    }

    /**
     * Prepare the text fixtures and the expected content file.
     */
    protected function loadTestFixtures(): void
    {
        $fixtures = $this->getAlwaysLoadingFixtures();

        $fixturesFile = $this->getFixturesFileForTest();
        if (Filesystem\exists($fixturesFile)) {
            $fixtures = Vec\concat(
                $fixtures,
                require $fixturesFile,
            );
        }

        if (count($fixtures) <= 0) {
            return;
        }

        $this->loadFixtures($fixtures);
    }

    /**
     * @return non-empty-string
     */
    protected function getFixturesFileForTest(): string
    {
        return $this->getTestDirectory() . '/Fixtures/' . $this->getName(false) . '.php';
    }

    /**
     * @param list<class-string<FixtureInterface>> $classNames
     */
    private function loadFixtures(array $classNames = []): void
    {
        $fixtureLoader = new SymfonyFixturesLoader(static::getContainer());
        foreach ($classNames as $className) {
            $fixture = new $className();
            $fixtureLoader->addFixture($fixture);
        }

        $em = Type\instance_of(EntityManagerInterface::class)->coerce(
            static::getContainer()->get('doctrine.orm.entity_manager'),
        );

        $executor = new ORMExecutor($em);
        $executor->execute($fixtureLoader->getFixtures(), true);
    }

    /**
     * Get the fixtures to always load when preparing the test fixtures.
     *
     * @return list<class-string<FixtureInterface>>
     */
    protected function getAlwaysLoadingFixtures(): array
    {
        return [];
    }

    private function getTestNameForExpectedFiles(): string
    {
        return $this->getName(false) . ($this->dataName() !== '' ? '-' . $this->dataName() : '');
    }

    /**
     * Get the expected response content file.
     *
     * @param non-empty-string $type The file type (txt, yml, etc).
     *
     * @return non-empty-string
     */
    protected function getExpectedContentFile(string $type): string
    {
        $testName = $this->getTestNameForExpectedFiles();
        if (isset($this->assertionExpectedFiles[$testName])) {
            $this->assertionExpectedFiles[$testName]++;
        } else {
            $this->assertionExpectedFiles[$testName] = 1;
        }

        return $this->getCurrentExpectedResponseContentFile($type);
    }

    /**
     * @param non-empty-string $type
     *
     * @return non-empty-string
     */
    public function getCurrentExpectedResponseContentFile(string $type): string
    {
        $testName         = $this->getTestNameForExpectedFiles();
        $expectedFileName = $testName . '-' . ($this->assertionExpectedFiles[$testName] ?? 1);

        return $this->getTestDirectory() . '/Expected/' . $expectedFileName . '.' . $type;
    }

    protected function getTestDirectory(): string
    {
        $reflection = new ReflectionObject($this);
        $fileName   = Type\non_empty_string()->coerce($reflection->getFileName());

        return Filesystem\get_directory($fileName);
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
        string $message = '',
    ): void {
        static::assertThat($actual, new ImageSimilarity($expected, $threshold), $message);
    }
}
