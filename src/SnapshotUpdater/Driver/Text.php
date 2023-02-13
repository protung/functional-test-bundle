<?php

declare(strict_types=1);

namespace Speicher210\FunctionalTestBundle\SnapshotUpdater\Driver;

use Psl\Type;
use SebastianBergmann\Comparator\ComparisonFailure;
use Speicher210\FunctionalTestBundle\SnapshotUpdater\Driver;
use Speicher210\FunctionalTestBundle\SnapshotUpdater\Exception\ActualNotSerializable;

final class Text implements Driver
{
    public function serialize(ComparisonFailure $comparisonFailure): string
    {
        try {
            return Type\string()->coerce($comparisonFailure->getActual());
        } catch (Type\Exception\CoercionException $coercionException) {
            throw new ActualNotSerializable(previous: $coercionException);
        }
    }
}
