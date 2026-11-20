<?php

declare(strict_types=1);

namespace Speicher210\FunctionalTestBundle\Test\Playwright;

use LogicException;
use Playwright\Console\ConsoleMessage;
use Playwright\Page\PageInterface;
use Playwright\PageError\PageErrorInterface;
use Playwright\Symfony\Test\PlaywrightTestCase;
use Psl\Vec;
use Speicher210\FunctionalTestBundle\Test\Concern\AssertsResponseSnapshots;
use Speicher210\FunctionalTestBundle\Test\Concern\FunctionalTest;

/**
 * End-to-end tests in a real browser with Playwright, with the fixtures, service mocks and expected files of the bundle.
 *
 * The browser requests are handled in-process by the kernel of the test.
 */
abstract class E2ETestCase extends PlaywrightTestCase
{
    use AssertsResponseSnapshots;
    use FunctionalTest;

    protected function setUp(): void
    {
        self::backupLocale();

        parent::setUp();

        static::bootKernel();

        $this->mockServices();
        $this->loadTestFixtures();
    }

    protected function tearDown(): void
    {
        $this->closeDatabaseConnections();

        parent::tearDown();

        self::restoreLocale();

        $this->cleanupPHPUnit();
    }

    /**
     * Asserts that the page threw no uncaught JavaScript error and logged no console error since it was opened.
     *
     * Without a page, the current page of the test is checked.
     */
    final protected static function assertPageHasNoErrors(PageInterface|null $page = null): void
    {
        $page ??= static::getPlaywrightClient()->getPage() ?? throw new LogicException('The test has no open page.');

        // By default, Playwright only returns what happened since the last navigation.
        $pageErrors      = $page->pageErrors(['filter' => 'all']);
        $consoleMessages = $page->consoleMessages(['filter' => 'all']);

        self::assertSame(
            ['page errors' => [], 'console errors' => []],
            [
                'page errors' => Vec\map(
                    $pageErrors,
                    static fn (PageErrorInterface $error): string => $error->message(),
                ),
                'console errors' => Vec\map(
                    Vec\filter(
                        $consoleMessages,
                        static fn (ConsoleMessage $message): bool => $message->type() === 'error',
                    ),
                    static fn (ConsoleMessage $message): string => $message->text(),
                ),
            ],
        );
    }

    /**
     * Playwright's hook for loading fixtures is not supported, the fixtures of a test are declared with #[WithFixture] or a fixtures file.
     *
     * @param array<mixed> $fixtures
     */
    final protected function loadFixtures(array $fixtures): never
    {
        throw new LogicException(
            'E2ETestCase does not support loadFixtures(). Declare the fixtures with #[WithFixture] or in the fixtures file of the test.',
        );
    }
}
