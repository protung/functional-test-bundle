<?php

declare(strict_types = 1);

namespace Speicher210\FunctionalTestBundle\Listener;

use PHPUnit\Framework\AssertionFailedError;
use PHPUnit\Framework\ExpectationFailedException;
use PHPUnit\Framework\Test;
use PHPUnit\Framework\TestListener as TestListenerInterface;
use PHPUnit\Framework\TestListenerDefaultImplementation;
use Speicher210\FunctionalTestBundle\Test\RestControllerWebTestCase;

/**
 * PHPUnit test listener that updates the rest requests expected output files.
 */
final class RestRequestFailTestExpectedOutputFileUpdater implements TestListenerInterface
{
    use TestListenerDefaultImplementation;

    /**
     * Fields that will always be updated with a fixed value.
     *
     * Ex: ['createdAt' => '@string@.isDateTime()']
     *
     * @var array
     */
    private $fields;

    /**
     * Array of patterns that should be kept when updating.
     *
     * @var array
     */
    private $matcherPatterns;

    /**
     * @param array $fields The fields to update in the expected output.
     * @param array $matcherPatterns
     */
    public function __construct(
        array $fields = [],
        array $matcherPatterns = [
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
            '@uuid@'
        ]
    ) {
        $this->fields = $fields;
        $this->matcherPatterns = $matcherPatterns;
    }

    /**
     * {@inheritdoc}
     */
    public function addFailure(Test $test, AssertionFailedError $e, float $time): void
    {
        if (!$e instanceof ExpectationFailedException || $e->getComparisonFailure() === null) {
            return;
        }

        if (!$test instanceof RestControllerWebTestCase) {
            return;
        }
        $expectedFile = $test->getCurrentExpectedResponseContentFile('json');
        if (!\file_exists($expectedFile)) {
            return;
        }

        // Always encode and decode in order to convert everything into an array.
        $expected = $originalExpected = $e->getComparisonFailure()->getExpected();
        if ($expected !== null) {
            $expected = \json_decode(\json_encode($expected), true);
            $expected = $this->parseExpectedData($expected, [], $originalExpected);
            if (\JSON_ERROR_NONE !== \json_last_error()) {
                // probably not expecting json.
                return;
            }
        } else {
            $expected = [];
        }

        // Always encode and decode in order to convert everything into an array.
        $actual = \json_decode(\json_encode($e->getComparisonFailure()->getActual()), true);
        if (\JSON_ERROR_NONE !== \json_last_error()) {
            // probably not expecting json.
            return;
        }

        try {
            \array_walk_recursive(
                $actual,
                function (&$value, $key) {
                    if (\array_key_exists($key, $this->fields)) {
                        $value = $this->fields[$key];
                    }
                }
            );

            $actual = $this->updateExpectedOutput($actual, $expected);

            // Indent the output with 2 spaces instead of 4.
            $data = \preg_replace_callback(
                '/^ +/m',
                function ($m) {
                    return \str_repeat(' ', \strlen($m[0]) / 2);
                },
                \json_encode($actual, \JSON_PRETTY_PRINT)
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
     * @param array $actual
     * @param array $expected
     *
     * @return array
     */
    private function updateExpectedOutput(array $actual, array $expected): array
    {
        foreach ($actual as $actualKey => &$actualField) {
            if (!isset($expected[$actualKey])) {
                continue;
            }

            if (\is_array($actualField)) {
                if (\count($actualField) === 0) {
                    $actualField = $expected[$actualKey];
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
                if (\is_string($expected[$actualKey]) && \strpos($expected[$actualKey], $matcherPattern) === 0) {
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
     * @param array $expectedData
     * @param array $parentKeys
     * @param mixed $originalExpected
     * @return array
     */
    private function parseExpectedData(array &$expectedData, array $parentKeys, $originalExpected): array
    {
        if (\is_object($originalExpected)) {
            foreach ($expectedData as $key => &$value) {
                $keys = $parentKeys;
                if (\is_array($value)) {
                    $keys[] = $key;
                    if (empty($value)) {
                        $value = $this->getOriginalEmptyJsonValue($originalExpected, $keys);
                    } else {
                        $value = $this->parseExpectedData($value, $keys, $originalExpected);
                    }
                }
            }
        }

        return $expectedData;
    }

    /**
     * Try to determine if original expected contained empty object or empty array.
     *
     * @param mixed $originalExpected
     * @param array $keys
     * @return mixed Either empty array or empty object
     */
    private function getOriginalEmptyJsonValue($originalExpected, array $keys)
    {
        if (!\is_object($originalExpected)) {
            return [];
        }

        $key = \array_shift($keys);
        if (isset($originalExpected->{$key})) {
            if (\count($keys)) {
                return $this->getOriginalEmptyJsonValue($originalExpected->{$key}, $keys);
            }
            return $originalExpected->{$key};
        }

        return [];
    }
}
