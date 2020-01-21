<?php

declare(strict_types=1);

namespace Speicher210\FunctionalTestBundle\Tests\FailTestExpectedOutputFileUpdater\JsonFileUpdater;

use Coduo\PHPMatcher\Factory\MatcherFactory;
use org\bovigo\vfs\vfsStream;
use PHPUnit\Framework\TestCase;
use SebastianBergmann\Comparator\ComparisonFailure;
use Speicher210\FunctionalTestBundle\FailTestExpectedOutputFileUpdater\JsonFileUpdater;

final class JsonFileUpdaterTest extends TestCase
{
    public function testUpdatesEmptyFile() : void
    {
        $this->assertUpdateExpectedFileUpdatesExpectedOutput();
    }

    public function testUpdatesOutput() : void
    {
        $this->assertUpdateExpectedFileUpdatesExpectedOutput();
    }

    public function testUpdatesIndentToTwoSpaces() : void
    {
        $this->assertUpdateExpectedFileUpdatesExpectedOutput();
    }

    public function testDoNotChangeTypeForEmptyObjectsAndArrays() : void
    {
        $this->assertUpdateExpectedFileUpdatesExpectedOutput();
    }

    public function testUpdatesObjectToNull() : void
    {
        $this->assertUpdateExpectedFileUpdatesExpectedOutput();
    }

    public function testUpdatesFieldsWithMatchedPatterns() : void
    {
        $this->assertUpdateExpectedFileUpdatesExpectedOutput();
    }

    public function testUpdatesFieldsWithPatternToNull() : void
    {
        $this->assertUpdateExpectedFileUpdatesExpectedOutput();
    }

    public function testUpdatesFieldsWithoutPatternToNull() : void
    {
        $this->assertUpdateExpectedFileUpdatesExpectedOutput();
    }

    public function testUpdatesFieldsWithPatternToValueFromActualWhenNotMatchingPattern() : void
    {
        $this->assertUpdateExpectedFileUpdatesExpectedOutput();
    }

    public function testUpdatesNullToOtherValues() : void
    {
        $this->assertUpdateExpectedFileUpdatesExpectedOutput();
    }

    public function testUpdatesArrayToObject() : void
    {
        $this->assertUpdateExpectedFileUpdatesExpectedOutput();
    }

    public function testUpdatesObjectWithArrayToEmptyObject() : void
    {
        $this->assertUpdateExpectedFileUpdatesExpectedOutput();
    }

    public function testUpdatesObjectWithArrayToObjectWithEmptyArray() : void
    {
        $this->assertUpdateExpectedFileUpdatesExpectedOutput();
    }

    private function assertUpdateExpectedFileUpdatesExpectedOutput() : void
    {
        $root         = vfsStream::setup();
        $expectedFile = vfsStream::newFile('expected')->at($root);

        $expectedMock = \json_decode($this->getTestFile('originalExpected'), false);
        $actualMock   = \json_decode($this->getTestFile('actual'), false);

        $comparisonFailureMock = $this->createMock(ComparisonFailure::class);
        $comparisonFailureMock->expects(self::once())->method('getExpected')->willReturn($expectedMock);
        $comparisonFailureMock->expects(self::once())->method('getActual')->willReturn($actualMock);

        $jsonFileUpdater = new JsonFileUpdater((new MatcherFactory())->createMatcher());
        $jsonFileUpdater->updateExpectedFile($expectedFile->url(), $comparisonFailureMock);

        self::assertSame($this->getTestFile('updatedExpected'), $expectedFile->getContent());
    }

    private function getTestFile(string $fileName) : string
    {
        $reflection = new \ReflectionObject($this);

        $expectedFile = \dirname($reflection->getFileName()) . '/' . $this->getName() . '/' . $fileName . '.json';

        return \file_get_contents($expectedFile);
    }
}
