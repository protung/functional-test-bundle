<?php

declare(strict_types = 1);

namespace Speicher210\FunctionalTestBundle\Tests;

use org\bovigo\vfs\vfsStream;
use PHPUnit\Framework\ExpectationFailedException;
use PHPUnit\Framework\TestCase;
use SebastianBergmann\Comparator\ComparisonFailure;
use Speicher210\FunctionalTestBundle\Listener\RestRequestFailTestExpectedOutputFileUpdater;
use Speicher210\FunctionalTestBundle\Test\RestControllerWebTestCase;

final class RestRequestFailTestExpectedOutputFileUpdaterTest extends TestCase
{
    public function testUpdatesEmptyFile(): void
    {
        $this->assertAddFailureUpdatesExpectedOutput();
    }

    public function testUpdatesOutput(): void
    {
        $this->assertAddFailureUpdatesExpectedOutput();
    }

    public function testUpdatesIndentToTwoSpaces(): void
    {
        $this->assertAddFailureUpdatesExpectedOutput();
    }

    public function testDoNotChangeTypeForEmptyObjectsAndArrays(): void
    {
        $this->assertAddFailureUpdatesExpectedOutput();
    }

    public function testUpdatesObjectToNull(): void
    {
        $this->assertAddFailureUpdatesExpectedOutput();
    }

    public function testUpdatesFieldsWithMatchedPatterns(): void
    {
        $this->assertAddFailureUpdatesExpectedOutput();
    }

    public function testUpdatesFieldsWithPatternToNull(): void
    {
        $this->assertAddFailureUpdatesExpectedOutput();
    }

    public function testUpdatesFieldsWithoutPatternToNull(): void
    {
        $this->assertAddFailureUpdatesExpectedOutput();
    }

    public function testUpdatesFieldsWithPatternToValueFromActualWhenNotMatchingPattern(): void
    {
        $this->assertAddFailureUpdatesExpectedOutput();
    }

    public function testUpdatesNullToOtherValues(): void
    {
        $this->assertAddFailureUpdatesExpectedOutput();
    }

    public function testUpdatesArrayToObject(): void
    {
        $this->assertAddFailureUpdatesExpectedOutput();
    }

    public function testUpdatesObjectWithArrayToEmptyObject(): void
    {
        $this->assertAddFailureUpdatesExpectedOutput();
    }

    public function testUpdatesObjectWithArrayToObjectWithEmptyArray(): void
    {
        $this->assertAddFailureUpdatesExpectedOutput();
    }

    private function assertAddFailureUpdatesExpectedOutput(): void
    {
        $listener = new RestRequestFailTestExpectedOutputFileUpdater();

        $test = $this->createMock(RestControllerWebTestCase::class);

        $root = vfsStream::setup();
        $expectedFile = vfsStream::newFile('expected')->at($root);

        $test->expects(self::once())->method('getCurrentExpectedResponseContentFile')->willReturn($expectedFile->url());

        $expectedMock = \json_decode($this->getTestFile('originalExpected'));
        $actualMock = \json_decode($this->getTestFile('actual'));

        $comparisonFailureMock = $this->createMock(ComparisonFailure::class);
        $comparisonFailureMock->expects(self::once())->method('getExpected')->willReturn($expectedMock);
        $comparisonFailureMock->expects(self::once())->method('getActual')->willReturn($actualMock);

        $exception = $this->createMock(ExpectationFailedException::class);
        $exception->expects(self::any())->method('getComparisonFailure')->willReturn($comparisonFailureMock);

        $listener->addFailure($test, $exception, 0.0);

        self::assertSame($this->getTestFile('updatedExpected'), $expectedFile->getContent());
    }

    private function getTestFile(string $fileName): string
    {
        $reflection = new \ReflectionObject($this);

        $expectedFile = \dirname($reflection->getFileName()) . '/' . $this->getName() . '/' . $fileName . '.json';

        return \file_get_contents($expectedFile);
    }
}
