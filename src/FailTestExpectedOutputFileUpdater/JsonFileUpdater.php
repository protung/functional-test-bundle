<?php

declare(strict_types=1);

namespace Speicher210\FunctionalTestBundle\FailTestExpectedOutputFileUpdater;

use Coduo\PHPMatcher\Matcher;
use Psl;
use Psl\Json\Exception\DecodeException;
use SebastianBergmann\Comparator\ComparisonFailure;

final class JsonFileUpdater
{
    public const DEFAULT_MATCHER_PATTERNS = [
        '@string@',
        '@integer@',
        '@number@',
        '@double@',
        '@boolean@',
        '@array@',
        '@...@',
        '@null@',
        '@*@',
        '@wildcard@',
        '@uuid@',
    ];

    /**
     * Fields that will always be updated with a fixed value.
     *
     * Ex: ['createdAt' => '@string@.isDateTime()']
     *
     * @var array<string,string>
     */
    private array $fields;

    /**
     * Array of patterns that should be kept when updating.
     *
     * @var array<string>
     */
    private array $matcherPatterns;

    private int $jsonEncodeOptions;

    private Matcher $matcher;

    /**
     * @param array<string,string> $fields          The fields to update in the expected output.
     * @param list<string>         $matcherPatterns
     */
    public function __construct(
        Matcher $matcher,
        array $fields = [],
        array $matcherPatterns = self::DEFAULT_MATCHER_PATTERNS,
        int $jsonEncodeOptions = \JSON_PRETTY_PRINT | \JSON_UNESCAPED_SLASHES | \JSON_PRESERVE_ZERO_FRACTION
    ) {
        $this->fields            = $fields;
        $this->matcherPatterns   = $matcherPatterns;
        $this->matcher           = $matcher;
        $this->jsonEncodeOptions = $jsonEncodeOptions;
    }

    public function updateExpectedFile(string $expectedFile, ComparisonFailure $comparisonFailure): void
    {
        if (! \file_exists($expectedFile)) {
            return;
        }

        // Always encode and decode in order to convert everything into an array.
        $expected = $originalExpected = $comparisonFailure->getExpected();
        if ($expected !== null) {
            try {
                $expected = Psl\Json\decode(Psl\Json\encode($expected, false, $this->jsonEncodeOptions), true);
            } catch (DecodeException) {
                // probably not expecting json.
                return;
            }

            $expected = $this->parseExpectedData($expected, [], $originalExpected);
        } else {
            $expected = [];
        }

        // Always encode and decode in order to convert everything into an array.
        try {
            $actual = Psl\Type\dict(Psl\Type\array_key(), Psl\Type\mixed())->coerce(
                Psl\Json\decode(
                    Psl\Json\encode($comparisonFailure->getActual(), false, $this->jsonEncodeOptions),
                    true
                )
            );
        } catch (DecodeException) {
            // probably not expecting json.
            return;
        }

        try {
            \array_walk_recursive(
                $actual,
                function (&$value, $key): void {
                    if (! \array_key_exists($key, $this->fields)) {
                        return;
                    }

                    $value = $this->fields[$key];
                }
            );

            $actual = $this->updateExpectedOutput($actual, $expected);

            // Indent the output with 2 spaces instead of 4.
            $data = Psl\Regex\replace_with(
                Psl\Json\encode($actual, false, $this->jsonEncodeOptions),
                '/^ +/m',
                static fn (array $m): string => Psl\Str\repeat(' ', Psl\Str\length($m[0]) / 2),
            );

            \file_put_contents($expectedFile, $data);
        } catch (\Throwable $e) {
            print $e->getTraceAsString();
            exit;
        }
    }

    /**
     * Update the expected output.
     *
     * @param mixed[] $actual
     * @param mixed[] $expected
     *
     * @return mixed[]
     */
    private function updateExpectedOutput(array $actual, array $expected): array
    {
        foreach ($actual as $actualKey => &$actualField) {
            if (! isset($expected[$actualKey])) {
                continue;
            }

            if (\is_array($actualField)) {
                if (\count($actualField) === 0) {
                    // Value for actual should be an empty object if expected had any properties, otherwise empty array.
                    $actualField = \is_array(
                        Psl\Json\decode(Psl\Json\encode($expected[$actualKey], false, $this->jsonEncodeOptions), false)
                    ) ? [] : new \stdClass();
                    continue;
                }

                if (\is_array($expected[$actualKey])) {
                    $actualField = $this->updateExpectedOutput($actualField, $expected[$actualKey]);
                    continue;
                }

                if (\is_object($expected[$actualKey])) {
                    // This is possible only for empty objects so we can safely pass an empty array as $expected.
                    $actualField = $this->updateExpectedOutput($actualField, []);
                    continue;
                }
            }

            foreach ($this->matcherPatterns as $matcherPattern) {
                if (\is_string($expected[$actualKey]) && \str_starts_with($expected[$actualKey], $matcherPattern)) {
                    if (! $this->matcher->match($actualField, $expected[$actualKey])) {
                        break;
                    }

                    $actualField = $expected[$actualKey];
                    break;
                }
            }
        }

        return $actual;
    }

    /**
     * Perform additional parsing for array with expected data based on the original expected.
     *
     * @param array<mixed> $expectedData
     * @param list<string> $parentKeys
     *
     * @return array<mixed>
     */
    private function parseExpectedData(array &$expectedData, array $parentKeys, mixed $originalExpected): array
    {
        if (\is_object($originalExpected) || \is_array($originalExpected)) {
            foreach ($expectedData as $key => &$value) {
                $keys = $parentKeys;
                if (! \is_array($value)) {
                    continue;
                }

                $keys[] = $key;
                if ($value === []) {
                    $value = $this->getOriginalEmptyJsonValue($originalExpected, $keys);
                } else {
                    $value = $this->parseExpectedData($value, $keys, $originalExpected);
                }
            }
        }

        return $expectedData;
    }

    /**
     * Try to determine if original expected contained empty object or empty array.
     *
     * @param non-empty-list<string> $keys
     *
     * @return mixed Either empty array or empty object
     */
    private function getOriginalEmptyJsonValue(mixed $originalExpected, array $keys): mixed
    {
        if (\is_array($originalExpected)) {
            $key = \array_shift($keys);
            if (\array_key_exists($key, $originalExpected)) {
                if (\count($keys) > 0) {
                    return $this->getOriginalEmptyJsonValue($originalExpected[$key], $keys);
                }

                return $originalExpected[$key];
            }
        }

        if (! \is_object($originalExpected)) {
            return [];
        }

        $key = \array_shift($keys);
        if (isset($originalExpected->{$key})) {
            if (\count($keys) > 0) {
                return $this->getOriginalEmptyJsonValue($originalExpected->{$key}, $keys);
            }

            return $originalExpected->{$key};
        }

        return [];
    }
}
