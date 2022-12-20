<?php

declare(strict_types=1);

namespace Speicher210\FunctionalTestBundle\Extension;

use PHPUnit\Runner\AfterLastTestHook;
use PHPUnit\Runner\BeforeFirstTestHook;
use Speicher210\FunctionalTestBundle\FailTestExpectedOutputFileUpdater\ExpectedOutputFileUpdaterConfigurator;
use Speicher210\FunctionalTestBundle\FailTestExpectedOutputFileUpdater\JsonFileUpdater;

/**
 * PHPUnit test extension that updates the rest requests expected output files.
 */
final class RestRequestFailTestExpectedOutputFileUpdater implements BeforeFirstTestHook, AfterLastTestHook
{
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
     * @var list<string>
     */
    private array $matcherPatterns;

    /**
     * @param array<string,string> $fields          The fields to update in the expected output.
     * @param list<string>         $matcherPatterns
     */
    public function __construct(array $fields = [], array $matcherPatterns = JsonFileUpdater::DEFAULT_MATCHER_PATTERNS)
    {
        $this->fields          = $fields;
        $this->matcherPatterns = $matcherPatterns;
    }

    public function executeBeforeFirstTest(): void
    {
        ExpectedOutputFileUpdaterConfigurator::createOutputUpdater($this->fields, $this->matcherPatterns);
        ExpectedOutputFileUpdaterConfigurator::enableOutputUpdater();
    }

    public function executeAfterLastTest(): void
    {
        ExpectedOutputFileUpdaterConfigurator::disableOutputUpdater();
    }
}
