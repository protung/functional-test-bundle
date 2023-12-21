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

        $comparisonFailure = new ComparisonFailure(null, $actualComparisonFailure, '', '');

        $driver = new XmlDriver();
        $actual = $driver->serialize($comparisonFailure);

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
        $comparisonFailure = new ComparisonFailure(null, $comparisonFailureActual, '', '');

        $driver = new XmlDriver();

        $this->expectException(ActualNotSerializable::class);
        $driver->serialize($comparisonFailure);
    }
}
