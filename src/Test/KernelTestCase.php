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
use Psl\Type;
use ReflectionObject;
use RuntimeException;
use Speicher210\FunctionalTestBundle\Test\Intl\LocaleSensitiveTestCase;
use Symfony\Bridge\Doctrine\DataFixtures\ContainerAwareLoader;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase as SymfonyKernelTestCase;
use Symfony\Component\HttpKernel\KernelInterface;

use function class_exists;
use function str_starts_with;

abstract class KernelTestCase extends SymfonyKernelTestCase
{
    use Assert\Image;
    use PHPMatcherAssertions;
    use PHPUnitHelper;
    use LocaleSensitiveTestCase {
        setUp as localeSensitiveTestCaseSetUp;
        tearDown as localeSensitiveTestCaseTearDown;
    }

    /**
     * Array with the number of assertions against expected files per test.
     *
     * @var array<string,int>
     */
    private array $assertionExpectedFiles = [];

    protected function setUp(): void
    {
        self::backupLocale();

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

        self::restoreLocale();

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
     * @param class-string<TService> $class
     * @param non-empty-string|null  $id
     *
     * @return TService
     *
     * @template TService of object
     */
    protected function getContainerService(string $class, string|null $id = null): object
    {
        $id ??= $class;

        if (static::getContainer()->has('test.' . $id)) {
            $service = static::getContainer()->get('test.' . $id);
        } else {
            $service = static::getContainer()->get($id);
        }

        return Type\instance_of($class)->coerce($service);
    }

    /**
     * @param non-empty-string $id
     */
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

    protected function getDefaultDatabaseConnection(): Connection
    {
        return $this->getContainerService(Connection::class);
    }

    protected function restartDatabaseSequences(): void
    {
        $connection = $this->getDefaultDatabaseConnection();

        $schemaManager = $connection->getSchemaManager();
        foreach ($schemaManager->listSequences() as $sequence) {
            $schemaManager->dropAndCreateSequence($sequence);
        }
    }

    /**
     * Prepare the text fixtures and the expected content file.
     */
    protected function loadTestFixtures(): void
    {
        $this->loadFixtures(
            ...$this->getAlwaysLoadingFixtures(),
        );

        $fixturesFile = $this->getFixturesFileForTest();
        if (! Filesystem\exists($fixturesFile)) {
            return;
        }

        $this->loadFixtures(
            ...require $fixturesFile,
        );
    }

    /**
     * @return non-empty-string
     */
    protected function getFixturesFileForTest(): string
    {
        return $this->getTestDirectory() . '/Fixtures/' . $this->getName(false) . '.php';
    }

    /**
     * @param class-string<FixtureInterface> ...$classNames
     */
    private function loadFixtures(string ...$classNames): void
    {
        if ($classNames === []) {
            return;
        }

        if (class_exists(ContainerAwareLoader::class)) {
            $fixtureLoader = new SymfonyFixturesLoader(static::getContainer());
        } else {
            $fixtureLoader = new SymfonyFixturesLoader();
        }

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
     * @return iterable<int, class-string<FixtureInterface>>
     */
    protected function getAlwaysLoadingFixtures(): iterable
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
}
