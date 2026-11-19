<?php

declare(strict_types=1);

namespace Speicher210\FunctionalTestBundle\Test\Concern;

use Doctrine\Bundle\FixturesBundle\Loader\SymfonyFixturesLoader;
use Doctrine\Common\DataFixtures\Executor\ORMExecutor;
use Doctrine\Common\DataFixtures\FixtureInterface;
use Doctrine\ORM\EntityManagerInterface;
use Psl\Str;
use Psl\Type;
use Psl\Vec;
use ReflectionAttribute;
use ReflectionClass;
use ReflectionObject;
use Speicher210\FunctionalTestBundle\Attribute\WithFixture;
use Speicher210\FunctionalTestBundle\Attribute\WithFixtureForTest;
use Speicher210\FunctionalTestBundle\Test\Loader\LoaderAsService;
use Symfony\Bridge\Doctrine\DataFixtures\ContainerAwareLoader;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

use function assert;
use function class_exists;
use function file_exists;
use function is_subclass_of;

/**
 * Loads the Doctrine fixtures of a test, from #[WithFixture] attributes and from the fixtures file of the test.
 *
 * @phpstan-require-extends KernelTestCase
 */
trait LoadsFixtures
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

    abstract protected function getTestDirectory(): string;

    /**
     * Prepare the text fixtures and the expected content file.
     */
    protected function loadTestFixtures(): void
    {
        $this->loadFixtureClasses(
            ...$this->getAlwaysLoadingFixtures(),
            ...$this->getFixturesFromTestAttributes(),
        );

        $fixturesFile = $this->getFixturesFileForTest();
        if (! file_exists($fixturesFile)) {
            return;
        }

        $this->loadFixtureClasses(
            ...require $fixturesFile,
        );
    }

    /**
     * @return iterable<int, class-string<FixtureInterface>>
     */
    private function getFixturesFromTestAttributes(): iterable
    {
        $reflection = new ReflectionObject($this);

        $testName = $this->name();

        $currentTestMethodReflection = $reflection->getMethod($testName);
        $currentTestAttributes       = $currentTestMethodReflection->getAttributes(WithFixture::class);

        yield from $this->loadFixturesFromAttributes($currentTestAttributes);
    }

    /**
     * @param iterable<ReflectionAttribute<WithFixture|WithFixtureForTest>> $attributes
     *
     * @return iterable<int, class-string<FixtureInterface>>
     */
    private function loadFixturesFromAttributes(iterable $attributes): iterable
    {
        $reflection = new ReflectionObject($this);

        $testName = $this->name();

        foreach ($attributes as $attribute) {
            $attributeInstance = $attribute->newInstance();

            if ($attributeInstance instanceof WithFixtureForTest) {
                assert(
                    $reflection->hasMethod($attributeInstance->testName),
                    Str\format(
                        'Test method "%s" configured in attribute WithFixtureForTest does not exist in the test "%s".',
                        $attributeInstance->testName,
                        static::class,
                    ),
                );

                if ($attributeInstance->testName !== $testName) {
                    continue;
                }
            }

            yield $attributeInstance->fixture;
        }
    }

    /**
     * @param ReflectionObject|ReflectionClass<object> $reflection
     *
     * @return iterable<ReflectionAttribute<WithFixture>>
     */
    private function getFixturesFromTraits(ReflectionObject|ReflectionClass $reflection): iterable
    {
        if ($reflection->getParentClass() !== false) {
            yield from $this->getFixturesFromTraits($reflection->getParentClass());
        }

        yield from Vec\flat_map(
            $reflection->getTraits(),
            fn (ReflectionClass $class) => [
                ...$class->getAttributes(WithFixture::class),
                ...$this->getFixturesFromTraits($class),
            ],
        );
    }

    /**
     * @return non-empty-string
     */
    protected function getFixturesFileForTest(): string
    {
        return $this->getTestDirectory() . '/Fixtures/' . $this->name() . '.php';
    }

    /**
     * @param class-string<FixtureInterface> ...$classNames
     */
    private function loadFixtureClasses(string ...$classNames): void
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
            if (is_subclass_of($className, LoaderAsService::class, true)) {
                $fixture = $this->getContainerService(FixtureInterface::class, $className);
            } else {
                $fixture = new $className();
            }

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
        $reflection = new ReflectionObject($this);

        $classAttributes = [
            ...$this->getFixturesFromTraits($reflection),
            ...$reflection->getAttributes(WithFixture::class),
            ...$reflection->getAttributes(WithFixtureForTest::class),
        ];

        yield from $this->loadFixturesFromAttributes($classAttributes);
    }
}
