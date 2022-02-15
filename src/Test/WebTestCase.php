<?php

declare(strict_types=1);

namespace Speicher210\FunctionalTestBundle\Test;

use org\bovigo\vfs\content\LargeFileContent;
use org\bovigo\vfs\vfsStream;
use Psl\Type;
use Speicher210\FunctionalTestBundle\Constraint\ResponseContentMatchesFile;
use Speicher210\FunctionalTestBundle\Constraint\ResponseHeaderSame;
use Speicher210\FunctionalTestBundle\Constraint\ResponseStatusCodeSame;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\HttpFoundation\Response;

abstract class WebTestCase extends KernelTestCase
{
    protected const IMAGE_TYPE_BMP = 'bmp';
    protected const IMAGE_TYPE_GIF = 'gif';
    protected const IMAGE_TYPE_JPG = 'jpg';
    protected const IMAGE_TYPE_PNG = 'png';
    protected const IMAGE_TYPE_SVG = 'svg';
    private const   IMAGE_TYPES    = [
        self::IMAGE_TYPE_BMP,
        self::IMAGE_TYPE_GIF,
        self::IMAGE_TYPE_JPG,
        self::IMAGE_TYPE_PNG,
        self::IMAGE_TYPE_SVG,
    ];

    /**
     * @param array<mixed> $server
     */
    protected static function createClient(array $server = []): KernelBrowser
    {
        $client = Type\object(KernelBrowser::class)->coerce(static::getContainer()->get('test.client'));
        $client->setServerParameters($server);

        return $client;
    }

    /**
     * Get the expected response content file.
     *
     * @param string $type The file type (txt, yml, etc).
     */
    protected function getExpectedResponseContentFile(string $type): string
    {
        return $this->getExpectedContentFile($type);
    }

    public static function assertResponseStatusCode(Response $response, int $expectedCode, string $message = ''): void
    {
        static::assertThat($response, new ResponseStatusCodeSame($expectedCode), $message);
    }

    public static function assertResponseHeaderSame(
        Response $response,
        string $headerName,
        string $expectedValue,
        string $message = ''
    ) : void {
        static::assertThat($response, new ResponseHeaderSame($headerName, $expectedValue), $message);
    }

    public static function assertResponseContentMatchesFile(
        Response $response,
        string $expectedFile,
        string $message = ''
    ): void {
        static::assertFileExists($expectedFile);
        static::assertThat($response, new ResponseContentMatchesFile($expectedFile), $message);
    }

    /**
     * Get a fake file containing only empty space of a certain size.
     */
    protected function getRequestUploadLargeFile(int $bytes, string $originalName = 'large_file.txt'): UploadedFile
    {
        $root      = vfsStream::setup();
        $largeFile = vfsStream::newFile('large.txt')
            ->withContent(new LargeFileContent($bytes))
            ->at($root);

        return new UploadedFile($largeFile->url(), $originalName, null, null, true);
    }

    /**
     * Get a fake text upload file.
     */
    protected function getRequestUploadPdfFile(string $originalName = 'fake_pdf.pdf'): UploadedFile
    {
        return new UploadedFile(
            __DIR__ . '/Fixtures/Resources/fake_pdf.pdf',
            $originalName,
            null,
            null,
            true
        );
    }

    /**
     * Get a fake text upload file.
     */
    protected function getRequestUploadTextFile(string $originalName = 'fake_text.txt'): UploadedFile
    {
        return new UploadedFile(
            __DIR__ . '/Fixtures/Resources/fake_text.txt',
            $originalName,
            null,
            null,
            true
        );
    }

    /**
     * Get a fake image upload file.
     *
     * @param string      $imageType    The image type to set. Must be one of the IMAGE_TYPE_* constants.
     * @param string|null $originalName The name for the original file should have.
     * @param int[]|null  $imageSize    Example: ['width' => 10, 'height' => 20].
     */
    protected function getRequestUploadImageFile(
        string $imageType = self::IMAGE_TYPE_PNG,
        ?string $originalName = null,
        ?array $imageSize = null
    ): UploadedFile {
        if (! \in_array($imageType, self::IMAGE_TYPES, true)) {
            throw new \InvalidArgumentException(\sprintf('Unknown image type %s', $imageType));
        }

        $originalName  = $originalName ?? 'fake_image';
        $originalName .= '.' . $imageType;

        if ($imageSize === null) {
            $filePath = __DIR__ . '/Fixtures/Resources/fake_image.' . $imageType;
        } else {
            if (! \extension_loaded('imagick')) {
                throw new \RuntimeException('Imagick extension is required to resize the image.');
            }
            if (! isset($imageSize['width'], $imageSize['height'])) {
                throw new \InvalidArgumentException(
                    'The "width" and "height" must be specified for the size of the image.'
                );
            }

            $image = new \Imagick();
            $image->newImage($imageSize['width'], $imageSize['height'], new \ImagickPixel('#ffffff'));
            $image->setImageFormat($imageType);
            $filePath = \tempnam(\sys_get_temp_dir(), $this->getName(false)) . '.' . $imageType;
            \file_put_contents($filePath, $image->getImageBlob());
        }

        return new UploadedFile($filePath, $originalName, null, null, true);
    }

    /**
     * Get a fake image upload file.
     *
     * @param bool   $withTags     Flag if the media file should have tags defined or not.
     * @param string $originalName The name for the original file should have.
     */
    protected function getRequestUploadAudioFile(bool $withTags, string $originalName = 'fake_audio.mp3'): UploadedFile
    {
        $fileName = $withTags ? 'fake_audio_tags.mp3' : 'fake_audio_notags.mp3';

        return new UploadedFile(
            __DIR__ . '/Fixtures/Resources/' . $fileName,
            $originalName,
            null,
            null,
            true
        );
    }

    /**
     * Get a fake video upload file.
     *
     * @param string $originalName The name for the original file should have.
     */
    protected function getRequestUploadVideoFile(string $originalName = 'fake_video.mpeg'): UploadedFile
    {
        return new UploadedFile(
            __DIR__ . '/Fixtures/Resources/fake_video.mpeg',
            $originalName,
            null,
            null,
            true
        );
    }
}
