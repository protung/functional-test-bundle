<?php

declare(strict_types=1);

namespace Speicher210\FunctionalTestBundle\Test\Assert;

use PHPUnit\Framework\ExpectationFailedException;
use Psl\File;
use Psl\Filesystem;
use Spatie\PdfToImage\Pdf as PdfToImage;
use Spatie\PdfToText\Pdf as PdfToText;
use Speicher210\FunctionalTestBundle\SnapshotUpdater;
use Speicher210\FunctionalTestBundle\SnapshotUpdater\DriverConfigurator;

trait Pdf
{
    use Image;

    /**
     * @param non-empty-string $expectedFile
     */
    public static function assertPdfTextEqualsFile(string $expectedFile, string $actualPdfContent, string $message = ''): void
    {
        $tempFile = Filesystem\create_temporary_file();
        File\write($tempFile, $actualPdfContent, File\WriteMode::Truncate);

        $actual = PdfToText::getText($tempFile, null, ['layout']);

        try {
            self::assertStringEqualsFile($expectedFile, $actual, $message);
        } catch (ExpectationFailedException $e) {
            $comparisonFailure = $e->getComparisonFailure();
            if ($comparisonFailure !== null && DriverConfigurator::isOutputUpdaterEnabled()) {
                SnapshotUpdater::updateText(
                    $comparisonFailure,
                    $expectedFile,
                );
            }

            throw $e;
        }
    }

    /**
     * @param non-empty-string $expectedFile
     * @param non-empty-string $actualFile
     */
    public static function assertPdfFileTextEqualsFile(string $expectedFile, string $actualFile, string $message = ''): void
    {
        self::assertPdfTextEqualsFile($expectedFile, File\read($actualFile), $message);
    }

    /**
     * @param non-empty-string $expectedDirectory
     */
    public static function assertPdfPagesImagesEqualsFiles(
        string $expectedDirectory,
        string $actualPdfContent,
        float $delta = 0.0,
        Pdf\PdfToImageConfiguration|null $pdfToImageConfiguration = null,
        string $message = '',
    ): void {
        $tempFile = Filesystem\create_temporary_file();
        File\write($tempFile, $actualPdfContent);

        self::assertPdfFilePagesImagesEqualsFiles($expectedDirectory, $tempFile, $delta, $pdfToImageConfiguration, $message);
    }

    /**
     * @param non-empty-string $expectedDirectory
     * @param non-empty-string $actualFile
     */
    public static function assertPdfFilePagesImagesEqualsFiles(
        string $expectedDirectory,
        string $actualFile,
        float $delta = 0.0,
        Pdf\PdfToImageConfiguration|null $pdfToImageConfiguration = null,
        string $message = '',
    ): void {
        $pdfToImageConfiguration ??= Pdf\PdfToImageConfiguration::default();
        $pdf                       = new PdfToImage($actualFile);
        $pdf
            ->format($pdfToImageConfiguration->outputFormat)
            ->quality($pdfToImageConfiguration->compressionQuality)
            ->resolution($pdfToImageConfiguration->resolution);

        for ($i = 1; $i <= $pdf->pageCount(); $i++) {
            // PDF to image will put the file extension when saving to file, so we do the same.
            $tempActualImage = Filesystem\create_temporary_file() . '.' . $pdfToImageConfiguration->outputFormat->value;
            $pdf->selectPage($i)->save($tempActualImage);

            $expectedFile = $expectedDirectory . '/page-' . $i . '.jpg';

            try {
                self::assertImageSimilarity(
                    File\read($expectedFile),
                    File\read($tempActualImage),
                    $delta,
                    $message,
                );
            } catch (ExpectationFailedException $e) {
                $comparisonFailure = $e->getComparisonFailure();
                if ($comparisonFailure !== null && DriverConfigurator::isOutputUpdaterEnabled()) {
                    SnapshotUpdater::updateBinary(
                        $comparisonFailure,
                        $expectedFile,
                    );
                }

                throw $e;
            }
        }
    }
}
