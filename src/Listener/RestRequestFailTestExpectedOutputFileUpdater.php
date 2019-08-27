<?php

declare(strict_types=1);

namespace Speicher210\FunctionalTestBundle\Listener;

use PHPUnit\Framework\AssertionFailedError;
use PHPUnit\Framework\ExpectationFailedException;
use PHPUnit\Framework\Test;
use PHPUnit\Framework\TestListener as TestListenerInterface;
use PHPUnit\Framework\TestListenerDefaultImplementation;
use PHPUnit\Framework\TestSuite;
use Speicher210\FunctionalTestBundle\FailTestExpectedOutputFileUpdater\ExpectedOutputFileUpdaterConfigurator;
use Speicher210\FunctionalTestBundle\FailTestExpectedOutputFileUpdater\RestRequestFailTestExpectedOutput;
use Speicher210\FunctionalTestBundle\Test\RestControllerWebTestCase;

/**
 * PHPUnit test listener that updates the rest requests expected output files.
 */
final class RestRequestFailTestExpectedOutputFileUpdater implements TestListenerInterface
{
    use RestRequestFailTestExpectedOutput;
    use TestListenerDefaultImplementation;

    public function startTestSuite(TestSuite $suite) : void
    {
        ExpectedOutputFileUpdaterConfigurator::createOutputUpdater($this->fields, $this->matcherPatterns);
        ExpectedOutputFileUpdaterConfigurator::enableOutputUpdater();
    }

    public function addFailure(Test $test, AssertionFailedError $e, float $time) : void
    {
        if (! $e instanceof ExpectationFailedException || $e->getComparisonFailure() === null) {
            return;
        }

        if (! $test instanceof RestControllerWebTestCase) {
            return;
        }

        if (! ExpectedOutputFileUpdaterConfigurator::isOutputUpdaterEnabled()) {
            return;
        }

        ExpectedOutputFileUpdaterConfigurator::getOutputUpdater()->updateExpectedFile(
            $test->getCurrentExpectedResponseContentFile('json'),
            $e->getComparisonFailure()
        );
    }
}
