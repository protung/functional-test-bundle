<?php

declare(strict_types=1);

namespace Speicher210\FunctionalTestBundle\Tests\SnapshotUpdater\Driver\Json;

use Generator;
use PHPUnit\Framework\TestCase;
use Psl\File;
use Psl\Json;
use SebastianBergmann\Comparator\ComparisonFailure;
use Speicher210\FunctionalTestBundle\CoduoMatcherFactory;
use Speicher210\FunctionalTestBundle\SnapshotUpdater\Driver\Json as JsonDriver;
use Speicher210\FunctionalTestBundle\SnapshotUpdater\Exception\ActualNotSerializable;

final class JsonTest extends TestCase
{
    public function testSerializeThrowsExceptionIfActualIsNotValidJson(): void
    {
        $comparisonFailureMock = new ComparisonFailure(null, '{invalid json}', '', '{invalid json}');

        $driver = new JsonDriver(CoduoMatcherFactory::getMatcher());

        $this->expectException(ActualNotSerializable::class);

        $driver->serialize($comparisonFailureMock);
    }

    public static function dataProviderTestSerialize(): Generator
    {
        yield ['testUpdatesEmptyFile'];
        yield ['testUpdatesOutput'];
        yield ['testUpdatesIndentToTwoSpaces'];
        yield ['testDoNotChangeTypeForEmptyObjectsAndArrays'];
        yield ['testDoNotChangeTypeForArrayOfEmptyObjects'];
        yield ['testUpdatesObjectToNull'];
        yield ['testUpdatesFieldsWithMatchedPatterns'];
        yield ['testUpdatesFieldsWithPatternToNull'];
        yield ['testUpdatesArrayToObject'];
        yield ['testUpdatesFieldsWithoutPatternToNull'];
        yield ['testUpdatesFieldsWithPatternToValueFromActualWhenNotMatchingPattern'];
        yield ['testUpdatesNullToOtherValues'];
        yield ['testUpdatesObjectWithArrayToEmptyObject'];
        yield ['testUpdatesObjectWithArrayToObjectWithEmptyArray'];
    }

    /**
     * @dataProvider dataProviderTestSerialize
     */
    public function testSerialize(string $scenario): void
    {
        try {
            $expectedAsString          = $this->getTestFile($scenario, 'comparisonFailureExpected');
            $comparisonFailureExpected = Json\decode($expectedAsString, false);
        } catch (Json\Exception\DecodeException) {
            $expectedAsString          = '';
            $comparisonFailureExpected = null;
        }

        $actualAsString              = $this->getTestFile($scenario, 'comparisonFailureActual');
        $comparisonFailureActualMock = Json\decode($actualAsString, false);

        $comparisonFailure = new ComparisonFailure($comparisonFailureExpected, $comparisonFailureActualMock, $expectedAsString, $actualAsString);

        $driver = new JsonDriver(CoduoMatcherFactory::getMatcher());
        $actual = $driver->serialize($comparisonFailure);

        self::assertSame($this->getTestFile($scenario, 'expected'), $actual);
    }

    private function getTestFile(string $scenario, string $fileName): string
    {
        return File\read(
            __DIR__ . '/' . $scenario . '/' . $fileName . '.json',
        );
    }
}
