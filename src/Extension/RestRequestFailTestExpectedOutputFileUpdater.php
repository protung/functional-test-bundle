<?php

declare(strict_types=1);

namespace Speicher210\FunctionalTestBundle\Extension;

use PHPUnit\Runner\Extension\Extension;
use PHPUnit\Runner\Extension\Facade;
use PHPUnit\Runner\Extension\ParameterCollection;
use PHPUnit\TextUI\Configuration\Configuration;
use Speicher210\FunctionalTestBundle\SnapshotUpdater\Driver\Json;

/**
 * @deprecated since 2.0, use SnapshotUpdaterExtension instead. Will be removed in 3.0.
 */
final class RestRequestFailTestExpectedOutputFileUpdater implements Extension
{
    private readonly SnapshotUpdaterExtension $extension;

    /**
     * @param array<string,string> $fields          Fields that will always be updated with a fixed value in the expected output. Ex: ['createdAt' => '@string@.isDateTime()']
     * @param list<string>         $matcherPatterns Patterns that should be kept when updating.
     */
    public function __construct(array $fields = [], array $matcherPatterns = Json::DEFAULT_MATCHER_PATTERNS)
    {
        $this->extension = new SnapshotUpdaterExtension($fields, $matcherPatterns);
    }

    public function bootstrap(Configuration $configuration, Facade $facade, ParameterCollection $parameters): void
    {
        $this->extension->bootstrap($configuration, $facade, $parameters);
    }
}
