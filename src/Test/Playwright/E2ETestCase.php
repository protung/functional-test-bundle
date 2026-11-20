<?php

declare(strict_types=1);

namespace Speicher210\FunctionalTestBundle\Test\Playwright;

use LogicException;
use Playwright\Console\ConsoleMessage;
use Playwright\Page\PageInterface;
use Playwright\PageError\PageErrorInterface;
use Playwright\Symfony\Test\PlaywrightTestCase;
use Psl\SecureRandom;
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

    private const int SETTLE_PERIOD = 50;

    /**
     * The token separates the state of one wait from the previous ones on the same page.
     */
    private const string NOTHING_IS_BUSY = <<<'JS'
        (arg) => {
            const now = performance.now();
            let wait = window.__speicher210NothingIsBusy;
            if (wait?.token !== arg.token) {
                wait = window.__speicher210NothingIsBusy = { token: arg.token, startedAt: now, busyAt: -Infinity };
            }

            if (document.querySelector('[aria-busy="true"]') !== null) {
                wait.busyAt = now;

                return false;
            }

            return now - wait.startedAt >= arg.quietPeriod && now - wait.busyAt >= arg.settlePeriod;
        }
        JS;

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
        $page ??= self::getCurrentPage();

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
     * Waits until no element of the page is marked with aria-busy, as Symfony UX Live Components and Turbo do while they load.
     *
     * Without a page, the current page of the test is checked.
     *
     * @param int<0, max> $quietPeriod Minimum wait in milliseconds, longer than the debounce of the Live Component models used.
     */
    final protected static function waitUntilNothingIsBusy(PageInterface|null $page = null, int $quietPeriod = 200): void
    {
        $page ??= self::getCurrentPage();

        // A Live Component starts its request only after a debounce: 0 ms for actions and on(change) models,
        // 150 ms by default for models updated on input. Right after the triggering event nothing is busy yet,
        // so the wait lasts at least the quiet period. After a request, the requests that a Live Component starts
        // itself, such as for emitted events, start within a few milliseconds, so it also lasts a settle period.
        $page->waitForFunction(
            self::NOTHING_IS_BUSY,
            ['token' => SecureRandom\string(16), 'quietPeriod' => $quietPeriod, 'settlePeriod' => self::SETTLE_PERIOD],
        );
    }

    private static function getCurrentPage(): PageInterface
    {
        return static::getPlaywrightClient()->getPage() ?? throw new LogicException('The test has no open page.');
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
