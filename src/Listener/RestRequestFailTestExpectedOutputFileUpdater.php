<?php

declare(strict_types = 1);

namespace Speicher210\FunctionalTestBundle\Listener;

use Exception;
use PHPUnit_Framework_AssertionFailedError;
use PHPUnit_Framework_ExpectationFailedException;
use PHPUnit_Framework_Test;
use PHPUnit_Framework_TestListener as TestListenerInterface;
use PHPUnit_Framework_TestSuite;
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
    private $fields = [];

    /**
     * Array of patterns that should be kept when updating.
     *
     * @var array
     */
    private $matcherPatterns = [];

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
    public function addFailure(PHPUnit_Framework_Test $test, PHPUnit_Framework_AssertionFailedError $e, $time)
    {
        if (!$e instanceof PHPUnit_Framework_ExpectationFailedException || $e->getComparisonFailure() === null) {
            return;
        }

        if (!$test instanceof RestControllerWebTestCase) {
            return;
        }
        $expectedFile = $test->getCurrentExpectedResponseContentFile('json');
        if (!file_exists($expectedFile)) {
            return;
        }

        // Always encode and decode in order to convert everything into an array.
        $expected = json_decode(json_encode($e->getComparisonFailure()->getExpected()), true);
        if (JSON_ERROR_NONE !== json_last_error()) {
            // probably not expecting json.
            return;
        }
        // Always encode and decode in order to convert everything into an array.
        $actual = json_decode(json_encode($e->getComparisonFailure()->getActual()), true);
        if (JSON_ERROR_NONE !== json_last_error()) {
            // probably not expecting json.
            return;
        }

        try {
            array_walk_recursive(
                $actual,
                function (&$value, $key) use ($expected) {
                    if (array_key_exists($key, $this->fields)) {
                        $value = $this->fields[$key];
                    }
                }
            );

            $actual = $this->updateExpectedOutput($actual, $expected);

            file_put_contents($expectedFile, json_encode($actual, JSON_PRETTY_PRINT));
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

            if (is_array($actualField) && is_array($expected[$actualKey])) {
                $actualField = $this->updateExpectedOutput($actualField, $expected[$actualKey]);
                continue;
            }

            foreach ($this->matcherPatterns as $matcherPattern) {
                if (strpos((string)$expected[$actualKey], $matcherPattern) === 0) {
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
    public function addError(PHPUnit_Framework_Test $test, Exception $e, $time)
    {
        // do nothing
    }

    /**
     * {@inheritdoc}
     */
    public function addIncompleteTest(PHPUnit_Framework_Test $test, Exception $e, $time)
    {
        // do nothing
    }

    /**
     * {@inheritdoc}
     */
    public function addRiskyTest(PHPUnit_Framework_Test $test, Exception $e, $time)
    {
        // do nothing
    }

    /**
     * {@inheritdoc}
     */
    public function addSkippedTest(PHPUnit_Framework_Test $test, Exception $e, $time)
    {
        // do nothing
    }

    /**
     * {@inheritdoc}
     */
    public function startTestSuite(PHPUnit_Framework_TestSuite $suite)
    {
        // do nothing
    }

    /**
     * {@inheritdoc}
     */
    public function endTestSuite(PHPUnit_Framework_TestSuite $suite)
    {
        // do nothing
    }

    /**
     * {@inheritdoc}
     */
    public function startTest(PHPUnit_Framework_Test $test)
    {
        // do nothing
    }

    /**
     * {@inheritdoc}
     */
    public function endTest(PHPUnit_Framework_Test $test, $time)
    {
        // do nothing
    }
}
