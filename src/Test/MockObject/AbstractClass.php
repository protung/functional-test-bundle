<?php

declare(strict_types=1);

namespace Speicher210\FunctionalTestBundle\Test\MockObject;

use InvalidArgumentException;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psl\Str;
use Psl\Vec;
use ReflectionClass;
use ReflectionMethod;
use ReflectionProperty;

use function get_parent_class;
use function property_exists;

/** @phpstan-require-extends TestCase */
trait AbstractClass
{
    /**
     * Returns a mock object for the specified abstract class with all abstract methods of the class mocked.
     * Concrete methods are not mocked by default.
     * To mock concrete methods, use the $mockedMethods parameter.
     * The original constructor is not called.
     *
     * @param class-string<RealInstanceType> $originalClassName
     * @param list<non-empty-string>         $mockedMethods
     * @param array<non-empty-string,mixed>  $mockedProperties  Collection of properties to mock, where the key is the property name and the value is the property value.
     *
     * @return MockObject&RealInstanceType
     *
     * @template RealInstanceType of object
     */
    protected function createMockForAbstractClass(string $originalClassName, array $mockedMethods = [], array $mockedProperties = []): MockObject
    {
        $abstractMethods = Vec\map(
            Vec\filter(
                (new ReflectionClass($originalClassName))->getMethods(),
                static fn (ReflectionMethod $method): bool => $method->isAbstract(),
            ),
            static fn (ReflectionMethod $method): string => $method->getName(),
        );

        $object = $this->createPartialMock(
            $originalClassName,
            Vec\unique([...$abstractMethods, ...$mockedMethods]),
        );

        foreach ($mockedProperties as $mockedPropertyName => $mockedPropertyValue) {
            $propertyReflection = $this->getPropertyReflection($originalClassName, $mockedPropertyName);
            $propertyReflection->setValue($object, $mockedPropertyValue);
        }

        return $object;
    }

    /**
     * @param class-string     $objectClass
     * @param non-empty-string $propertyName
     */
    private function getPropertyReflection(string $objectClass, string $propertyName): ReflectionProperty
    {
        if (property_exists($objectClass, $propertyName)) {
            return new ReflectionProperty($objectClass, $propertyName);
        }

        $parent = get_parent_class($objectClass);

        if ($parent !== false) {
            return $this->getPropertyReflection($parent, $propertyName);
        }

        throw new InvalidArgumentException(
            Str\format(
                'Property "%s" does not exist on object "%s" or parent classes.',
                $propertyName,
                $objectClass,
            ),
        );
    }
}
