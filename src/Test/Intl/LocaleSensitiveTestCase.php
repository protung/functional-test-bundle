<?php

declare(strict_types=1);

namespace Speicher210\FunctionalTestBundle\Test\Intl;

use Locale;

trait LocaleSensitiveTestCase
{
    private static string|null $originalLocale = null;

    /**
     * @param non-empty-string $locale
     */
    private static function setCurrentLocale(string $locale): void
    {
        self::backupLocale();

        Locale::setDefault($locale);
    }

    /**
     * @internal
     */
    private static function backupLocale(): void
    {
        if (self::$originalLocale !== null) {
            return;
        }

        self::$originalLocale = Locale::getDefault();
    }

    /**
     * @internal
     */
    private static function restoreLocale(): void
    {
        if (self::$originalLocale === null) {
            return;
        }

        self::setCurrentLocale(self::$originalLocale);
    }

    protected function setUp(): void
    {
        self::backupLocale();

        parent::setUp();
    }

    protected function tearDown(): void
    {
        self::restoreLocale();

        parent::tearDown();
    }
}
