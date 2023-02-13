<?php

declare(strict_types=1);

namespace Speicher210\FunctionalTestBundle\SnapshotUpdater;

use SebastianBergmann\Comparator\ComparisonFailure;
use Speicher210\FunctionalTestBundle\SnapshotUpdater\Exception\ActualNotSerializable;

interface Driver
{
    /**
     * @throws ActualNotSerializable
     */
    public function serialize(ComparisonFailure $comparisonFailure): string;
}
