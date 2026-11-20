<?php

declare(strict_types=1);

namespace Speicher210\FunctionalTestBundle\Tests\Test\Playwright;

use Closure;
use LogicException;
use Override;
use PHPUnit\Framework\ExpectationFailedException;
use PHPUnit\Framework\TestCase;
use Playwright\Console\ConsoleMessage;
use Playwright\Page\PageInterface;
use Playwright\PageError\PageErrorInterface;
use Playwright\Symfony\Client\PlaywrightKernelClient;
use Speicher210\FunctionalTestBundle\Test\Playwright\E2ETestCase;

final class E2ETestCaseTest extends TestCase
{
    public function testAssertPageHasNoErrorsPassesWithoutErrors(): void
    {
        $page = $this->createPage([], [self::consoleMessage('log', 'Page loaded')]);

        self::checkPage($page);
    }

    public function testAssertPageHasNoErrorsFailsOnPageError(): void
    {
        $page = $this->createPage([self::pageError('Boom')], []);

        self::assertFailsWith(
            ['page errors' => ['Boom'], 'console errors' => []],
            static fn () => self::checkPage($page),
        );
    }

    public function testAssertPageHasNoErrorsFailsOnConsoleError(): void
    {
        $page = $this->createPage([], [self::consoleMessage('error', 'Failed to load resource')]);

        self::assertFailsWith(
            ['page errors' => [], 'console errors' => ['Failed to load resource']],
            static fn () => self::checkPage($page),
        );
    }

    public function testAssertPageHasNoErrorsIgnoresConsoleWarnings(): void
    {
        $page = $this->createPage([], [self::consoleMessage('warning', 'Deprecated API')]);

        self::checkPage($page);
    }

    public function testAssertPageHasNoErrorsListsAllErrors(): void
    {
        $page = $this->createPage(
            [self::pageError('Boom'), self::pageError('Bang')],
            [
                self::consoleMessage('error', 'First'),
                self::consoleMessage('warning', 'Deprecated API'),
                self::consoleMessage('error', 'Second'),
            ],
        );

        self::assertFailsWith(
            ['page errors' => ['Boom', 'Bang'], 'console errors' => ['First', 'Second']],
            static fn () => self::checkPage($page),
        );
    }

    public function testAssertPageHasNoErrorsChecksTheCurrentPageByDefault(): void
    {
        $client = self::createStub(PlaywrightKernelClient::class);
        $client->method('getPage')->willReturn($this->createPage([self::pageError('Boom')], []));

        $testCase = new class ('testName') extends E2ETestCase
        {
            public static PlaywrightKernelClient|null $client = null;

            public static function checkCurrentPage(): void
            {
                self::assertPageHasNoErrors();
            }

            #[Override]
            protected static function getPlaywrightClient(): PlaywrightKernelClient
            {
                return self::$client ?? throw new LogicException('No client was set.');
            }
        };

        $testCase::$client = $client;

        self::assertFailsWith(
            ['page errors' => ['Boom'], 'console errors' => []],
            static fn () => $testCase::checkCurrentPage(),
        );
    }

    private static function checkPage(PageInterface $page): void
    {
        $testCase = new class ('testName') extends E2ETestCase
        {
            public static function checkPage(PageInterface $page): void
            {
                self::assertPageHasNoErrors($page);
            }
        };

        $testCase::checkPage($page);
    }

    /**
     * @param array{'page errors': list<string>, 'console errors': list<string>} $expectedErrors
     * @param Closure(): void                                                    $assertion
     */
    private static function assertFailsWith(array $expectedErrors, Closure $assertion): void
    {
        try {
            $assertion();
        } catch (ExpectationFailedException $exception) {
            self::assertSame($expectedErrors, $exception->getComparisonFailure()?->getActual());

            return;
        }

        self::fail('assertPageHasNoErrors() did not fail.');
    }

    /**
     * @param list<PageErrorInterface> $pageErrors
     * @param list<ConsoleMessage>     $consoleMessages
     */
    private function createPage(array $pageErrors, array $consoleMessages): PageInterface
    {
        $page = $this->createMock(PageInterface::class);
        $page->expects($this->once())
            ->method('pageErrors')
            ->with(['filter' => 'all'])
            ->willReturn($pageErrors);
        $page->expects($this->once())
            ->method('consoleMessages')
            ->with(['filter' => 'all'])
            ->willReturn($consoleMessages);

        return $page;
    }

    private static function pageError(string $message): PageErrorInterface
    {
        $error = self::createStub(PageErrorInterface::class);
        $error->method('message')->willReturn($message);

        return $error;
    }

    private static function consoleMessage(string $type, string $text): ConsoleMessage
    {
        return new ConsoleMessage(['type' => $type, 'text' => $text]);
    }
}
