<?php

declare(strict_types=1);

namespace Speicher210\FunctionalTestBundle\Test\Assert\Pdf;

final class PdfToImageConfiguration
{
    private const DEFAULT_OUTPUT_FORMAT       = OutputFormat::jpg;
    private const DEFAULT_RESOLUTION          = 144;
    private const DEFAULT_COMPRESSION_QUALITY = 50;

    /**
     * @param positive-int $resolution
     * @param int<1,100>   $compressionQuality
     */
    private function __construct(
        public readonly OutputFormat $outputFormat,
        public readonly int $resolution,
        public readonly int $compressionQuality,
    ) {
    }

    public static function default(): self
    {
        return new self(
            self::DEFAULT_OUTPUT_FORMAT,
            self::DEFAULT_RESOLUTION,
            self::DEFAULT_COMPRESSION_QUALITY,
        );
    }

    public function withOutputFormat(OutputFormat $outputFormat): self
    {
        return new self(
            $outputFormat,
            $this->resolution,
            $this->compressionQuality,
        );
    }

    /**
     * @param positive-int $resolution
     */
    public function withResolution(int $resolution): self
    {
        return new self(
            $this->outputFormat,
            $resolution,
            $this->compressionQuality,
        );
    }

    /**
     * @param int<1,100> $compressionQuality
     */
    public function withCompressionQuality(int $compressionQuality): self
    {
        return new self(
            $this->outputFormat,
            $this->resolution,
            $compressionQuality,
        );
    }
}
