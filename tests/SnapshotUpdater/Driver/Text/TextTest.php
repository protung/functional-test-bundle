<?php

declare(strict_types=1);

namespace Speicher210\FunctionalTestBundle\Tests\SnapshotUpdater\Driver\Text;

use Generator;
use PHPUnit\Framework\TestCase;
use SebastianBergmann\Comparator\ComparisonFailure;
use Speicher210\FunctionalTestBundle\SnapshotUpdater\Driver\Text as TextDriver;
use Speicher210\FunctionalTestBundle\SnapshotUpdater\Exception\ActualNotSerializable;
use stdClass;
use Stringable;

final class TextTest extends TestCase
{
    public static function dataProviderTestSerialize(): Generator
    {
        yield 'empty-string' => ['', ''];
        yield 'string' => ['test', 'test'];
        yield 'string with white text' => [' te st ', ' te st '];
        yield 'Stringable' => [
            new class implements Stringable
            {
                public function __toString(): string
                {
                    return 'from Stringable';
                }
            },
            'from Stringable',
        ];

        yield 'int' => [123, '123'];
    }

    /**
     * @dataProvider dataProviderTestSerialize
     */
    public function testSerialize(string|Stringable|int $comparisonFailureActual, string $expected): void
    {
        $comparisonFailureMock = $this->createMock(ComparisonFailure::class);
        $comparisonFailureMock->expects(self::once())->method('getActual')->willReturn($comparisonFailureActual);

        $driver = new TextDriver();
        $actual = $driver->serialize($comparisonFailureMock);

        self::assertSame($expected, $actual);
    }

    public static function dataProviderTestSerializeThrowsExceptionIfComparisonFailureActualIsNotSerializable(): Generator
    {
        yield 'float' => [1.2];
        yield 'boolean' => [true];
        yield 'object' => [new stdClass()];
        yield 'array' => [['string']];
    }

    /**
     * @dataProvider dataProviderTestSerializeThrowsExceptionIfComparisonFailureActualIsNotSerializable
     */
    public function testSerializeThrowsExceptionIfComparisonFailureActualIsNotSerializable(mixed $comparisonFailureActual): void
    {
        $comparisonFailureMock = $this->createMock(ComparisonFailure::class);
        $comparisonFailureMock->expects(self::once())->method('getActual')->willReturn($comparisonFailureActual);

        $driver = new TextDriver();

        $this->expectException(ActualNotSerializable::class);
        $driver->serialize($comparisonFailureMock);
    }
}
