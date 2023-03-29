<?php

declare(strict_types=1);

namespace Speicher210\FunctionalTestBundle\Test\MockObject;

use InvalidArgumentException;
use PHPUnit\Framework\MockObject\MockObject;
use Psl\Str;
use ReflectionProperty;

use function get_parent_class;
use function property_exists;

trait AbstractClass
{
    /**
     * Returns a mock object for the specified abstract class with all abstract methods of the class mocked.
     * Concrete methods are not mocked by default.
     * To mock concrete methods, use the $mockedMethods parameter.
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
        $object = $this->getMockForAbstractClass(
            $originalClassName,
            [],
            '',
            false,
            false,
            true,
            $mockedMethods,
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

    /**
     * Returns a mock object for the specified abstract class with all abstract
     * methods of the class mocked. Concrete methods are not mocked by default.
     * To mock concrete methods, use the 7th parameter ($mockedMethods).
     *
     * @param array<mixed>                   $arguments         Constructor arguments
     * @param class-string<RealInstanceType> $originalClassName
     * @param list<non-empty-string>         $mockedMethods
     *
     * @return MockObject&RealInstanceType
     *
     * @psalm-template RealInstanceType of object
     */
    abstract protected function getMockForAbstractClass(string $originalClassName, array $arguments = [], string $mockClassName = '', bool $callOriginalConstructor = true, bool $callOriginalClone = true, bool $callAutoload = true, array $mockedMethods = [], bool $cloneArguments = false): MockObject;
}
