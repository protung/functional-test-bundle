<?php

declare(strict_types = 1);

namespace Speicher210\FunctionalTestBundle\Listener;

use Exception;
use PHPUnit\Framework\AssertionFailedError;
use PHPUnit\Framework\ExpectationFailedException;
use PHPUnit\Framework\Test;
use PHPUnit\Framework\TestListener as TestListenerInterface;
use PHPUnit\Framework\TestSuite;
use PHPUnit\Framework\Warning;
use Speicher210\FunctionalTestBundle\Test\RestControllerWebTestCase;

/**
 * PHPUnit test listener that updates the rest requests expected output files.
 */
final class RestRequestFailTestExpectedOutputFileUpdater implements TestListenerInterface
{
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
    )
    {
        $this->fields = $fields;
        $this->matcherPatterns = $matcherPatterns;
    }

    /**
     * {@inheritdoc}
     */
    public function addFailure(Test $test, AssertionFailedError $e, $time)
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
        $expected = \json_decode(\json_encode($e->getComparisonFailure()->getExpected()), true);
        if (\JSON_ERROR_NONE !== \json_last_error()) {
            // probably not expecting json.
            return;
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
                function (&$value, $key) use ($expected) {
                    if (\array_key_exists($key, $this->fields)) {
                        $value = $this->fields[$key];
                    }
                }
            );

            $actual = $this->updateExpectedOutput($actual, $expected);

            \file_put_contents($expectedFile, \json_encode($actual, \JSON_PRETTY_PRINT));
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

            if (\is_array($actualField) && \is_array($expected[$actualKey])) {
                $actualField = $this->updateExpectedOutput($actualField, $expected[$actualKey]);
                continue;
            }

            foreach ($this->matcherPatterns as $matcherPattern) {
                if (\strpos((string)$expected[$actualKey], $matcherPattern) === 0) {
                    $actualField = $expected[$actualKey];
                    break;
                }
            }
        }

        return $actual;
    }

    /**
     * {@inheritdoc}
     */
    public function addError(Test $test, Exception $e, $time)
    {
        // do nothing
    }

    /**
     * {@inheritdoc}
     */
    public function addWarning(Test $test, Warning $e, $time)
    {
        // do nothing
    }

    /**
     * {@inheritdoc}
     */
    public function addIncompleteTest(Test $test, Exception $e, $time)
    {
        // do nothing
    }

    /**
     * {@inheritdoc}
     */
    public function addRiskyTest(Test $test, Exception $e, $time)
    {
        // do nothing
    }

    /**
     * {@inheritdoc}
     */
    public function addSkippedTest(Test $test, Exception $e, $time)
    {
        // do nothing
    }

    /**
     * {@inheritdoc}
     */
    public function startTestSuite(TestSuite $suite)
    {
        // do nothing
    }

    /**
     * {@inheritdoc}
     */
    public function endTestSuite(TestSuite $suite)
    {
        // do nothing
    }

    /**
     * {@inheritdoc}
     */
    public function startTest(Test $test)
    {
        // do nothing
    }

    /**
     * {@inheritdoc}
     */
    public function endTest(Test $test, $time)
    {
        // do nothing
    }
}
