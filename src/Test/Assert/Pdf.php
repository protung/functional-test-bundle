<?php

declare(strict_types=1);

namespace Speicher210\FunctionalTestBundle\Test\Assert;

use Psl\File;
use Psl\Filesystem;
use Spatie\PdfToImage\Pdf as PdfToImage;
use Spatie\PdfToText\Pdf as PdfToText;

trait Pdf
{
    use Image;

    /**
     * @param non-empty-string $expectedFile
     * @param non-empty-string $actualPdfContent
     */
    public static function assertPdfTextEqualsFile(string $expectedFile, string $actualPdfContent, string $message = ''): void
    {
        $tempFile = Filesystem\create_temporary_file();
        File\write($tempFile, $actualPdfContent, File\WriteMode::TRUNCATE);

        $actual = PdfToText::getText($tempFile, null, ['layout']);
//        File\write($expectedFile, $actual, File\WriteMode::TRUNCATE);self::fail('Expected updated');

        self::assertStringEqualsFile($expectedFile, $actual, $message);
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
     * @param non-empty-string $actualPdfContent
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
            ->setOutputFormat($pdfToImageConfiguration->outputFormat->value)
            ->setCompressionQuality($pdfToImageConfiguration->compressionQuality)
            ->setResolution($pdfToImageConfiguration->resolution);

//        $pdf->saveAllPagesAsImages($expectedDirectory, 'page-');self::fail('Expected updated.');

        for ($i = 1; $i <= $pdf->getNumberOfPages(); $i++) {
            $tempActualImage = Filesystem\create_temporary_file();
            $pdf->setPage($i)->saveImage($tempActualImage);

            self::assertImageSimilarity(
                File\read($expectedDirectory . '/page-' . $i . '.jpg'),
                File\read($tempActualImage),
                $delta,
                $message,
            );
        }
    }
}
