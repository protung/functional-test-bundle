<?php

declare(strict_types=1);

namespace Speicher210\FunctionalTestBundle\Extension;

use PHPUnit\Runner\AfterLastTestHook;
use PHPUnit\Runner\BeforeFirstTestHook;
use Speicher210\FunctionalTestBundle\FailTestExpectedOutputFileUpdater\ExpectedOutputFileUpdaterConfigurator;
use Speicher210\FunctionalTestBundle\FailTestExpectedOutputFileUpdater\RestRequestFailTestExpectedOutput;

/**
 * PHPUnit test extension that updates the rest requests expected output files.
 */
final class RestRequestFailTestExpectedOutputFileUpdater implements BeforeFirstTestHook, AfterLastTestHook
{
    use RestRequestFailTestExpectedOutput;

    public function executeBeforeFirstTest() : void
    {
        ExpectedOutputFileUpdaterConfigurator::createOutputUpdater($this->fields, $this->matcherPatterns);
        ExpectedOutputFileUpdaterConfigurator::enableOutputUpdater();
    }

    public function executeAfterLastTest() : void
    {
        ExpectedOutputFileUpdaterConfigurator::disableOutputUpdater();
    }
}
