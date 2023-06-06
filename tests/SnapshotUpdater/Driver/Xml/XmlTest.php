<?php

declare(strict_types=1);

namespace Speicher210\FunctionalTestBundle\Tests\SnapshotUpdater\Driver\Xml;

use DOMDocument;
use Generator;
use PHPUnit\Framework\TestCase;
use SebastianBergmann\Comparator\ComparisonFailure;
use Speicher210\FunctionalTestBundle\SnapshotUpdater\Driver\Xml as XmlDriver;
use Speicher210\FunctionalTestBundle\SnapshotUpdater\Exception\ActualNotSerializable;
use stdClass;

final class XmlTest extends TestCase
{
    public function testSerialize(): void
    {
        $actualComparisonFailure = new DOMDocument();
        $actualComparisonFailure->load(__DIR__ . '/testSerialize/comparisonFailureActual.xml');

        $comparisonFailureMock = $this->createMock(ComparisonFailure::class);
        $comparisonFailureMock->expects(self::once())->method('getActual')->willReturn($actualComparisonFailure);

        $driver = new XmlDriver();
        $actual = $driver->serialize($comparisonFailureMock);

        self::assertStringEqualsFile(__DIR__ . '/testSerialize/expected.xml', $actual);
    }

    public static function dataProviderTestSerializeThrowsExceptionIfComparisonFailureActualIsNotSerializable(): Generator
    {
        yield 'string' => ['string'];
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

        $driver = new XmlDriver();

        $this->expectException(ActualNotSerializable::class);
        $driver->serialize($comparisonFailureMock);
    }
}
