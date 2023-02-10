<?php

declare(strict_types=1);

namespace Speicher210\FunctionalTestBundle\Test\Doctrine\DBAL\Types;

use Doctrine\DBAL\Platforms\AbstractPlatform;
use Doctrine\DBAL\Types\ConversionException;
use Doctrine\DBAL\Types\Type;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psl\Dict;

abstract class TypeTestCase extends TestCase
{
    /** @var AbstractPlatform&MockObject */
    protected AbstractPlatform|MockObject $platform;

    protected Type $type;

    /**
     * @return class-string<Type>
     */
    abstract protected static function classUnderTest(): string;

    abstract protected static function getTypeName(): string;

    protected function setUp(): void
    {
        $this->platform = $this->createMock(AbstractPlatform::class);
        $this->type     = Type::getType(Dict\flip(Type::getTypesMap())[static::classUnderTest()]);
    }

    public function testConvertsNullToDatabaseValue(): void
    {
        $actual = $this->type->convertToDatabaseValue(null, $this->platform);
        self::assertNull($actual);
    }

    public function testConvertsNullToPHPValue(): void
    {
        $actual = $this->type->convertToPHPValue(null, $this->platform);
        self::assertNull($actual);
    }

    public function testConvertsEmptyStringToPHPValue(): void
    {
        $actual = $this->type->convertToPHPValue('', $this->platform);

        self::assertNull($actual);
    }

    /**
     * @return iterable<array{0: mixed, 1: string}>
     */
    abstract public static function dataProviderConvertsToDatabaseInvalidValues(): iterable;

    /**
     * @return iterable<array{0: mixed, 1: string}>
     */
    abstract public static function dataProviderConvertsToPHPInvalidValues(): iterable;

    /**
     * @return iterable<array{0: mixed, 1: mixed}>
     */
    abstract public static function dataProviderTestConvertsToDatabaseValueValidValues(): iterable;

    /**
     * @return iterable<array{0: mixed, 1: mixed}>
     */
    abstract public static function dataProviderTestConvertsToPHPValueValidValues(): iterable;

    /**
     * @dataProvider dataProviderConvertsToDatabaseInvalidValues
     */
    public function testConvertsToDatabaseValueThrowsExceptionOnInvalidValue(mixed $value, string $expectedMessage): void
    {
        $this->expectException(ConversionException::class);
        $this->expectExceptionMessage($expectedMessage);

        $this->type->convertToDatabaseValue($value, $this->platform);
    }

    /**
     * @dataProvider dataProviderConvertsToPHPInvalidValues
     */
    public function testConvertsToPHPValueThrowsExceptionOnInvalidValue(mixed $value, string $expectedMessage): void
    {
        $this->expectException(ConversionException::class);
        $this->expectExceptionMessage($expectedMessage);

        $this->type->convertToPHPValue($value, $this->platform);
    }

    /**
     * @dataProvider dataProviderTestConvertsToDatabaseValueValidValues
     */
    public function testConvertsToDatabaseValue(mixed $value, mixed $expected): void
    {
        $actual = $this->type->convertToDatabaseValue($value, $this->platform);

        self::assertSame($expected, $actual);
    }

    /**
     * @dataProvider dataProviderTestConvertsToPHPValueValidValues
     */
    public function testConvertsToPHPValue(mixed $value, mixed $expected): void
    {
        $actual = $this->type->convertToPHPValue($value, $this->platform);

        self::assertEquals($expected, $actual);
    }

    public function testGetName(): void
    {
        self::assertSame(static::getTypeName(), $this->type->getName());
    }
}
