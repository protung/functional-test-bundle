<?php

declare(strict_types=1);

namespace Speicher210\FunctionalTestBundle\Test;

use Imagick;
use ImagickPixel;
use InvalidArgumentException;
use org\bovigo\vfs\content\LargeFileContent;
use org\bovigo\vfs\vfsStream;
use Psl\Type;
use RuntimeException;
use Speicher210\FunctionalTestBundle\Constraint\ResponseContentMatchesFile;
use Speicher210\FunctionalTestBundle\Constraint\ResponseHeaderSame;
use Speicher210\FunctionalTestBundle\Constraint\ResponseStatusCodeSame;
use Speicher210\FunctionalTestBundle\VfsStreamSetup;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Component\BrowserKit\Cookie;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Session\Storage\MockFileSessionStorage;
use Symfony\Component\HttpFoundation\Session\Storage\MockFileSessionStorageFactory;

use function extension_loaded;
use function file_put_contents;
use function in_array;
use function sprintf;
use function sys_get_temp_dir;
use function tempnam;

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
        $client = Type\instance_of(KernelBrowser::class)->coerce(static::getContainer()->get('test.client'));
        $client->setServerParameters($server);

        return $client;
    }

    /**
     * Set up a session before making a request.
     *
     * @param array<string, mixed> $sessionAttributes
     */
    public function prepareSession(KernelBrowser $client, array $sessionAttributes): void
    {
        /** @var MockFileSessionStorageFactory $sessionStorageFactory */
        $sessionStorageFactory = $this->getContainerService('session.storage.factory.mock_file');
        /** @var MockFileSessionStorage $sessionStorage */
        $sessionStorage = $sessionStorageFactory->createStorage(null);
        $sessionStorage->start();
        $sessionStorage->setSessionData(['_sf2_attributes' => $sessionAttributes]);
        $sessionStorage->save();

        $cookie = new Cookie($sessionStorage->getName(), $sessionStorage->getId());
        $client->getCookieJar()->set($cookie);
    }

    /**
     * Get the expected response content file.
     *
     * @param non-empty-string $type The file type (txt, yml, etc).
     *
     * @return non-empty-string
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
        string $message = '',
    ): void {
        static::assertThat($response, new ResponseHeaderSame($headerName, $expectedValue), $message);
    }

    public static function assertResponseContentMatchesFile(
        Response $response,
        string $expectedFile,
        string $message = '',
    ): void {
        static::assertFileExists($expectedFile);
        static::assertThat($response, new ResponseContentMatchesFile($expectedFile), $message);
    }

    /**
     * Get a fake file containing only empty space of a certain size.
     */
    protected function getRequestUploadLargeFile(int $bytes, string $originalName = 'large_file.txt'): UploadedFile
    {
        $root      = VfsStreamSetup::getRoot();
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
            DummyFile::Pdf->path(),
            $originalName,
            null,
            null,
            true,
        );
    }

    /**
     * Get a fake text upload file.
     */
    protected function getRequestUploadTextFile(string $originalName = 'fake_text.txt'): UploadedFile
    {
        return new UploadedFile(
            DummyFile::Text->path(),
            $originalName,
            null,
            null,
            true,
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
        string|null $originalName = null,
        array|null $imageSize = null,
    ): UploadedFile {
        if (! in_array($imageType, self::IMAGE_TYPES, true)) {
            throw new InvalidArgumentException(sprintf('Unknown image type %s', $imageType));
        }

        $originalName ??= 'dummy_image';
        $originalName  .= '.' . $imageType;

        if ($imageSize === null) {
            $filePath = DummyFile::from('dummy_image.' . $imageType)->path();
        } else {
            if (! extension_loaded('imagick')) {
                throw new RuntimeException('Imagick extension is required to resize the image.');
            }

            if (! isset($imageSize['width'], $imageSize['height'])) {
                throw new InvalidArgumentException(
                    'The "width" and "height" must be specified for the size of the image.',
                );
            }

            $image = new Imagick();
            $image->newImage($imageSize['width'], $imageSize['height'], new ImagickPixel('#ffffff'));
            $image->setImageFormat($imageType);
            $filePath = tempnam(sys_get_temp_dir(), $this->getName(false)) . '.' . $imageType;
            file_put_contents($filePath, $image->getImageBlob());
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
        $dummyFile = $withTags ? DummyFile::AudioMp3 : DummyFile::AudioMp3NoTags;

        return new UploadedFile(
            $dummyFile->path(),
            $originalName,
            null,
            null,
            true,
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
            DummyFile::VideoMpeg->path(),
            $originalName,
            null,
            null,
            true,
        );
    }
}
