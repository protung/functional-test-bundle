<?php

declare(strict_types=1);

namespace Speicher210\FunctionalTestBundle\Tests\FailTestExpectedOutputFileUpdater\JsonFileUpdater;

use org\bovigo\vfs\vfsStream;
use PHPUnit\Framework\TestCase;
use Psl\File;
use Psl\Filesystem;
use Psl\Json;
use Psl\Type;
use SebastianBergmann\Comparator\ComparisonFailure;
use Speicher210\FunctionalTestBundle\CoduoMatcherFactory;
use Speicher210\FunctionalTestBundle\FailTestExpectedOutputFileUpdater\JsonFileUpdater;

final class JsonFileUpdaterTest extends TestCase
{
    public function testUpdatesEmptyFile(): void
    {
        $this->assertUpdateExpectedFileUpdatesExpectedOutput();
    }

    public function testUpdatesOutput(): void
    {
        $this->assertUpdateExpectedFileUpdatesExpectedOutput();
    }

    public function testUpdatesIndentToTwoSpaces(): void
    {
        $this->assertUpdateExpectedFileUpdatesExpectedOutput();
    }

    public function testDoNotChangeTypeForEmptyObjectsAndArrays(): void
    {
        $this->assertUpdateExpectedFileUpdatesExpectedOutput();
    }

    public function testDoNotChangeTypeForArrayOfEmptyObjects(): void
    {
        $this->assertUpdateExpectedFileUpdatesExpectedOutput();
    }

    public function testUpdatesObjectToNull(): void
    {
        $this->assertUpdateExpectedFileUpdatesExpectedOutput();
    }

    public function testUpdatesFieldsWithMatchedPatterns(): void
    {
        $this->assertUpdateExpectedFileUpdatesExpectedOutput();
    }

    public function testUpdatesFieldsWithPatternToNull(): void
    {
        $this->assertUpdateExpectedFileUpdatesExpectedOutput();
    }

    public function testUpdatesFieldsWithoutPatternToNull(): void
    {
        $this->assertUpdateExpectedFileUpdatesExpectedOutput();
    }

    public function testUpdatesFieldsWithPatternToValueFromActualWhenNotMatchingPattern(): void
    {
        $this->assertUpdateExpectedFileUpdatesExpectedOutput();
    }

    public function testUpdatesNullToOtherValues(): void
    {
        $this->assertUpdateExpectedFileUpdatesExpectedOutput();
    }

    public function testUpdatesArrayToObject(): void
    {
        $this->assertUpdateExpectedFileUpdatesExpectedOutput();
    }

    public function testUpdatesObjectWithArrayToEmptyObject(): void
    {
        $this->assertUpdateExpectedFileUpdatesExpectedOutput();
    }

    public function testUpdatesObjectWithArrayToObjectWithEmptyArray(): void
    {
        $this->assertUpdateExpectedFileUpdatesExpectedOutput();
    }

    private function assertUpdateExpectedFileUpdatesExpectedOutput(): void
    {
        $root         = vfsStream::setup();
        $expectedFile = vfsStream::newFile('expected')->at($root);

        try {
            $expectedMock = Json\decode($this->getTestFile('originalExpected'), false);
        } catch (Json\Exception\DecodeException) {
            $expectedMock = null;
        }

        try {
            $actualMock = Json\decode($this->getTestFile('actual'), false);
        } catch (Json\Exception\DecodeException) {
            $actualMock = null;
        }

        $comparisonFailureMock = $this->createMock(ComparisonFailure::class);
        $comparisonFailureMock->expects(self::once())->method('getExpected')->willReturn($expectedMock);
        $comparisonFailureMock->expects(self::once())->method('getActual')->willReturn($actualMock);

        $jsonFileUpdater = new JsonFileUpdater(CoduoMatcherFactory::getMatcher());
        $jsonFileUpdater->updateExpectedFile($expectedFile->url(), $comparisonFailureMock);

        self::assertSame($this->getTestFile('updatedExpected'), $expectedFile->getContent());
    }

    private function getTestFile(string $fileName): string
    {
        $reflection = new \ReflectionObject($this);

        $file = Type\non_empty_string()->coerce($reflection->getFileName());

        return File\read(
            Filesystem\get_directory($file) . '/' . $this->getName() . '/' . $fileName . '.json'
        );
    }
}
