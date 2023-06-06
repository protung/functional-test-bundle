<?php

declare(strict_types=1);

namespace Speicher210\FunctionalTestBundle\SnapshotUpdater\Driver;

use DOMDocument;
use Psl\Type;
use SebastianBergmann\Comparator\ComparisonFailure;
use Speicher210\FunctionalTestBundle\SnapshotUpdater\Driver;
use Speicher210\FunctionalTestBundle\SnapshotUpdater\Exception\ActualNotSerializable;

final class Xml implements Driver
{
    public function serialize(ComparisonFailure $comparisonFailure): string
    {
        try {
            $actualXml = Type\instance_of(DOMDocument::class)->coerce($comparisonFailure->getActual());
        } catch (Type\Exception\CoercionException $coercionException) {
            throw new ActualNotSerializable(previous: $coercionException);
        }

        $actualXml->preserveWhiteSpace = false;
        $actualXml->formatOutput       = true;

        return Type\string()->coerce($actualXml->saveXML());
    }
}
