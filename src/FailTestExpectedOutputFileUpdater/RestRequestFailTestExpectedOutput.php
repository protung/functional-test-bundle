<?php

declare(strict_types=1);

namespace Speicher210\FunctionalTestBundle\FailTestExpectedOutputFileUpdater;

trait RestRequestFailTestExpectedOutput
{
    /**
     * Fields that will always be updated with a fixed value.
     *
     * Ex: ['createdAt' => '@string@.isDateTime()']
     *
     * @var array<string,string>
     */
    private $fields;

    /**
     * Array of patterns that should be kept when updating.
     *
     * @var string[]
     */
    private $matcherPatterns;

    /**
     * @param string[] $fields          The fields to update in the expected output.
     * @param string[] $matcherPatterns
     */
    public function __construct(array $fields = [], array $matcherPatterns = JsonFileUpdater::DEFAULT_MATCHER_PATTERNS)
    {
        $this->fields          = $fields;
        $this->matcherPatterns = $matcherPatterns;
    }
}
