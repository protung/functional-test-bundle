<?php

declare(strict_types=1);

namespace Speicher210\FunctionalTestBundle\Comparator;

use Money\Currencies\AggregateCurrencies;
use Money\Currencies\BitcoinCurrencies;
use Money\Currencies\ISOCurrencies;
use Money\Formatter\IntlMoneyFormatter;
use Money\Money;
use NumberFormatter;
use SebastianBergmann\Comparator\Comparator;
use SebastianBergmann\Comparator\ComparisonFailure;

use function assert;

/**
 * The comparator is for comparing Money objects in PHPUnit tests.
 *
 * This is here until support for PHPUnit 10 is in the library itself.
 *
 * @see https://github.com/moneyphp/money/pull/746
 */
final class MoneyPHP extends Comparator
{
    private IntlMoneyFormatter $formatter;

    public function __construct()
    {
        $currencies = new AggregateCurrencies(
            [
                new ISOCurrencies(),
                new BitcoinCurrencies(),
            ],
        );

        $numberFormatter = new NumberFormatter('en_US', NumberFormatter::CURRENCY);
        $this->formatter = new IntlMoneyFormatter($numberFormatter, $currencies);
    }

    public function accepts(mixed $expected, mixed $actual): bool
    {
        return $expected instanceof Money && $actual instanceof Money;
    }

    public function assertEquals(
        mixed $expected,
        mixed $actual,
        float $delta = 0.0,
        bool $canonicalize = false,
        bool $ignoreCase = false,
    ): void {
        assert($expected instanceof Money);
        assert($actual instanceof Money);

        if ($expected->equals($actual)) {
            return;
        }

        throw new ComparisonFailure($expected, $actual, $this->formatter->format($expected), $this->formatter->format($actual), 'Failed asserting that two Money objects are equal.');
    }
}
