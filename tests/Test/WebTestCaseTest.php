<?php

declare(strict_types=1);

namespace Speicher210\FunctionalTestBundle\Tests\Test;

use PHPUnit\Framework\TestCase;
use Speicher210\FunctionalTestBundle\Test\WebTestCase;

final class WebTestCaseTest extends TestCase
{
    public function testGetCurrentExpectedResponseContentFile(): void
    {
        $testClass = new class () extends WebTestCase
        {
            /**
             * @param non-empty-string $type
             *
             * @return non-empty-string
             */
            public function testGetExpectedResponseContentFile(string $type): string
            {
                return parent::getExpectedResponseContentFile($type);
            }
        };

        self::assertStringEndsWith('/Expected/-1.type', $testClass->getCurrentExpectedResponseContentFile('type'));

        $testClass->setName('testName');
        self::assertStringEndsWith(
            '/Expected/testName-1.type',
            $testClass->getCurrentExpectedResponseContentFile('type'),
        );

        $testClass->testGetExpectedResponseContentFile('type');
        self::assertStringEndsWith(
            '/Expected/testName-1.type',
            $testClass->getCurrentExpectedResponseContentFile('type'),
        );

        $testClass->testGetExpectedResponseContentFile('type');
        self::assertStringEndsWith(
            '/Expected/testName-2.type',
            $testClass->getCurrentExpectedResponseContentFile('type'),
        );
    }
}
