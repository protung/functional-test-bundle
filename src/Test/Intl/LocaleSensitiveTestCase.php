<?php

declare(strict_types=1);

namespace Speicher210\FunctionalTestBundle\Test\Intl;

use Locale;
use Psl\Type;

trait LocaleSensitiveTestCase
{
    /** @var non-empty-string|null */
    private static string|null $originalLocale = null;

    /**
     * @param non-empty-string $locale
     */
    private static function setCurrentLocale(string $locale): void
    {
        self::backupLocale();

        Locale::setDefault($locale);
    }

    private static function backupLocale(): void
    {
        if (self::$originalLocale !== null) {
            return;
        }

        self::$originalLocale = Type\non_empty_string()->coerce(Locale::getDefault());
    }

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
        parent::tearDown();

        self::restoreLocale();
    }
}
