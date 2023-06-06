<?php

declare(strict_types=1);

namespace Speicher210\FunctionalTestBundle;

use Psl\File;
use SebastianBergmann\Comparator\ComparisonFailure;
use Speicher210\FunctionalTestBundle\SnapshotUpdater\DriverConfigurator;

final class SnapshotUpdater
{
    /**
     * @param non-empty-string $snapshotFile
     */
    public static function updateJson(ComparisonFailure $comparisonFailure, string $snapshotFile): void
    {
        self::updateSnapshotFile(
            $comparisonFailure,
            $snapshotFile,
            DriverConfigurator::getJsonDriver(),
        );
    }

    /**
     * @param non-empty-string $snapshotFile
     */
    public static function updateText(ComparisonFailure $comparisonFailure, string $snapshotFile): void
    {
        self::updateSnapshotFile(
            $comparisonFailure,
            $snapshotFile,
            DriverConfigurator::getTextDriver(),
        );
    }

    /**
     * @param non-empty-string $snapshotFile
     */
    public static function updateXML(ComparisonFailure $comparisonFailure, string $snapshotFile): void
    {
        self::updateSnapshotFile(
            $comparisonFailure,
            $snapshotFile,
            DriverConfigurator::getXmlDriver(),
        );
    }

    /**
     * @param non-empty-string $snapshotFile
     */
    private static function updateSnapshotFile(ComparisonFailure $comparisonFailure, string $snapshotFile, SnapshotUpdater\Driver $driver): void
    {
        $data = $driver->serialize($comparisonFailure);

        File\write($snapshotFile, $data, File\WriteMode::TRUNCATE);
    }
}
