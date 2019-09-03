<?php

declare(strict_types=1);

namespace Speicher210\FunctionalTestBundle\Test;

use org\bovigo\vfs\content\LargeFileContent;
use org\bovigo\vfs\vfsStream;
use PHPUnit\Framework\ExpectationFailedException;
use Speicher210\FunctionalTestBundle\Constraint\JsonResponseContentMatches;
use Speicher210\FunctionalTestBundle\Constraint\ResponseHeaderSame;
use Speicher210\FunctionalTestBundle\Constraint\ResponseStatusCodeSame;
use Speicher210\FunctionalTestBundle\FailTestExpectedOutputFileUpdater\ExpectedOutputFileUpdaterConfigurator;
use Symfony\Bundle\FrameworkBundle\Client;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Security\Core\User\User;
use Symfony\Component\Security\Core\User\UserInterface;

/**
 * Abstract class for restful controllers.
 */
abstract class RestControllerWebTestCase extends WebTestCase
{
    public const AUTHENTICATION_NONE = null;

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
     * The authenticated user for the test.
     *
     * @var UserInterface|null
     */
    protected static $authentication;

    public static function assertResponseStatusCode(Response $response, int $expectedCode, string $message = '') : void
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

    public static function assertJsonResponseContent(
        Response $response,
        string $expectedContent,
        string $message = ''
    ) : void {
        static::assertThat($response, new JsonResponseContentMatches($expectedContent), $message);
    }

    protected function setUp() : void
    {
        parent::setUp();
        static::$authentication = self::AUTHENTICATION_NONE;
    }

    protected function tearDown() : void
    {
        parent::tearDown();
        static::$authentication = self::AUTHENTICATION_NONE;
    }

    /**
     * {@inheritdoc}
     */
    protected static function createClient(array $server = []) : Client
    {
        $client = parent::createClient($server);

        if (self::$authentication === null) {
            return $client;
        }

        static::authenticateClient($client);

        return $client;
    }

    protected static function authenticateClient(Client $client) : void
    {
        if (! \interface_exists('Lexik\Bundle\JWTAuthenticationBundle\Services\JWTTokenManagerInterface')) {
            throw new \RuntimeException(
                \sprintf(
                    'Package "%s" was not found. Please install it or overwrite method "%s"',
                    'lexik/jwt-authentication-bundle',
                    __METHOD__
                )
            );
        }

        /** @var \Lexik\Bundle\JWTAuthenticationBundle\Services\JWTTokenManagerInterface $jwtManager */
        $jwtManager = static::$container->get('lexik_jwt_authentication.jwt_manager');
        $client->setServerParameter(
            'HTTP_Authorization',
            \sprintf('Bearer %s', $jwtManager->create(static::$authentication))
        );
    }

    protected function loginAsAdmin() : void
    {
        $user = new User('admin', null, ['ROLE_ADMIN']);
        $this->loginAs($user);
    }

    protected function loginAs(UserInterface $user) : void
    {
        self::$authentication = $user;
    }

    /**
     * Shorthand method for assertRestRequest() with a GET request.
     *
     * @param string  $path               The API path to test.
     * @param mixed[] $queryParams        The query parameters.
     * @param int     $expectedStatusCode The expected HTTP response code.
     * @param mixed[] $server             The server parameters.
     */
    protected function assertRestGetPath(
        string $path,
        array $queryParams = [],
        int $expectedStatusCode = Response::HTTP_OK,
        array $server = []
    ) : Client {
        \parse_str(\parse_url($path, \PHP_URL_QUERY) ?? '', $queryParamsFromPath);

        $request = Request::create(
            $path,
            Request::METHOD_GET,
            \array_replace_recursive($queryParamsFromPath, $queryParams),
            [],
            [],
            $server
        );

        return $this->assertRestRequest($request, $expectedStatusCode);
    }

    /**
     * Shorthand method for assertRestRequest() with a POST request.
     *
     * @param string  $path               The API path to test.
     * @param mixed[] $content            The POST content.
     * @param int     $expectedStatusCode The expected HTTP response code.
     * @param mixed[] $files              The files to send with the request.
     * @param mixed[] $server             The server parameters.
     */
    protected function assertRestPostPath(
        string $path,
        array $content = [],
        int $expectedStatusCode = Response::HTTP_OK,
        array $files = [],
        array $server = []
    ) : Client {
        $request = Request::create(
            $path,
            Request::METHOD_POST,
            $content,
            [],
            $files,
            $server
        );

        return $this->assertRestRequest($request, $expectedStatusCode);
    }

    /**
     * Shorthand method for assertRestRequest() with a PATCH request.
     *
     * @param string  $path               The API path to test.
     * @param mixed[] $content            The PATCH content.
     * @param int     $expectedStatusCode The expected HTTP response code.
     * @param mixed[] $files              The files to send with the request.
     * @param mixed[] $server             The server parameters.
     */
    protected function assertRestPatchPath(
        string $path,
        array $content = [],
        int $expectedStatusCode = Response::HTTP_NO_CONTENT,
        array $files = [],
        array $server = []
    ) : Client {
        $request = Request::create(
            $path,
            Request::METHOD_PATCH,
            $content,
            [],
            $files,
            $server
        );

        return $this->assertRestRequest($request, $expectedStatusCode);
    }

    /**
     * Shorthand method for assertRestRequest() with a PUT request.
     *
     * @param string  $path               The API path to test.
     * @param mixed[] $content            The PUT content.
     * @param int     $expectedStatusCode The expected HTTP response code.
     * @param mixed[] $files              The files to send with the request.
     * @param mixed[] $server             The server parameters.
     */
    protected function assertRestPutPath(
        string $path,
        array $content = [],
        int $expectedStatusCode = Response::HTTP_NO_CONTENT,
        array $files = [],
        array $server = []
    ) : Client {
        $request = Request::create(
            $path,
            Request::METHOD_PUT,
            $content,
            [],
            $files,
            $server
        );

        return $this->assertRestRequest($request, $expectedStatusCode);
    }

    /**
     * Shorthand method for assertRestRequest() with a DELETE request.
     *
     * @param string  $path               The API path to test.
     * @param int     $expectedStatusCode The expected HTTP response code.
     * @param mixed[] $server             The server parameters.
     */
    protected function assertRestDeletePath(
        string $path,
        int $expectedStatusCode = Response::HTTP_NO_CONTENT,
        array $server = []
    ) : Client {
        $request = Request::create(
            $path,
            Request::METHOD_DELETE,
            [],
            [],
            [],
            $server
        );

        return $this->assertRestRequest($request, $expectedStatusCode);
    }

    /**
     * Assert if a request returns the expected REST result.
     *
     * @param Request $request            The request to simulate.
     * @param int     $expectedStatusCode The expected HTTP response code.
     */
    protected function assertRestRequest(Request $request, int $expectedStatusCode = Response::HTTP_OK) : Client
    {
        $expectedFile = null;
        $expected     = null;
        if ($expectedStatusCode !== Response::HTTP_NO_CONTENT) {
            $expectedFile = $this->getExpectedResponseContentFile('json');
            if (\file_exists($expectedFile)) {
                $expected = $this->prettifyJson(\file_get_contents($expectedFile));
            }
        }

        $client = $this->assertRequest($request, $expectedStatusCode, $expected, 'application/json');
        $this->getObjectManager()->clear();

        if ($expectedStatusCode !== Response::HTTP_NO_CONTENT) {
            $response = $client->getResponse();
            static::assertResponseHeaderSame($response, 'Content-Type', 'application/json');
        }

        return $client;
    }

    /**
     * Assert if a request returns the expected result.
     *
     * @param Request $request               The request to simulate.
     * @param int     $expectedStatusCode    The expected HTTP response code.
     * @param string  $expectedOutputContent The expected output content.
     */
    protected function assertRequest(
        Request $request,
        int $expectedStatusCode = Response::HTTP_OK,
        ?string $expectedOutputContent = null,
        ?string $expectedOutputContentType = null
    ) : Client {
        $client = static::createClient();

        $client->request(
            $request->getMethod(),
            $request->getUri(),
            $request->request->all(),
            $request->files->all(),
            $request->server->all(),
            $request->getContent()
        );

        $response = $client->getResponse();
        $this->assertRequestResponse(
            $response,
            $expectedStatusCode,
            $expectedOutputContent,
            $expectedOutputContentType
        );

        return $client;
    }

    protected function assertRequestResponse(
        Response $response,
        int $expectedStatusCode,
        ?string $expectedOutputContent,
        ?string $expectedOutputContentType
    ) : void {
        static::assertResponseStatusCode($response, $expectedStatusCode);

        if ($expectedOutputContent !== null) {
            static::assertResponseHeaderSame($response, 'Content-Type', $expectedOutputContentType);

            switch ($response->headers->get('Content-Type')) {
                case 'image/png':
                case 'image/jpeg':
                case 'image/jpg':
                    static::assertImageSimilarity($expectedOutputContent, $response->getContent());
                    break;
                case 'application/json':
                default:
                    $this->assertJsonContentOutput($response, $expectedOutputContent);
                    break;
            }
        } else {
            static::assertEmpty($response->getContent());
        }
    }

    private function assertJsonContentOutput(Response $response, string $expectedOutputContent) : void
    {
        try {
            static::assertJsonResponseContent($response, $expectedOutputContent);
        } catch (ExpectationFailedException $e) {
            $comparisonFailure = $e->getComparisonFailure();
            if ($comparisonFailure !== null && ExpectedOutputFileUpdaterConfigurator::isOutputUpdaterEnabled()) {
                ExpectedOutputFileUpdaterConfigurator::getOutputUpdater()->updateExpectedFile(
                    $this->getCurrentExpectedResponseContentFile('json'),
                    $comparisonFailure
                );
            }
            throw $e;
        }
    }

    /**
     * Assert that a request to an URL returns 403.
     *
     * @param string  $path    The API path to test.
     * @param string  $method  The HTTP verb.
     * @param mixed[] $content The POST content.
     * @param mixed[] $server  The server parameters.
     */
    protected function assertRestRequestReturns403(
        string $path,
        string $method,
        array $content = [],
        array $server = []
    ) : void {
        $request = Request::create(
            $path,
            $method,
            $content,
            [],
            [],
            $server
        );

        $expected = [
            'code' => 403,
            'message' => 'Forbidden',
        ];

        $this->assertRequest($request, Response::HTTP_FORBIDDEN, \json_encode($expected), 'application/json');
    }

    /**
     * Assert that a request to an URL returns 401 if the user is not authenticated.
     *
     * @param string  $url    The URL to call.
     * @param string  $method The HTTP verb.
     * @param mixed[] $server The server parameters.
     */
    protected function assertRestRequestReturns401IfUserIsNotAuthenticated(
        string $url,
        string $method,
        array $server = []
    ) : void {
        static::$authentication = self::AUTHENTICATION_NONE;

        $request = Request::create(
            $url,
            $method,
            [],
            [],
            [],
            $server
        );

        $expected = [
            'code' => 401,
            'message' => 'Unauthorized',
        ];

        $this->assertRequest($request, Response::HTTP_UNAUTHORIZED, \json_encode($expected), 'application/json');
    }

    /**
     * Assert that a request to an URL returns 404.
     *
     * @param string  $path    The API path to test.
     * @param string  $method  The HTTP verb.
     * @param mixed[] $content The POST content.
     * @param mixed[] $server  The server parameters.
     */
    protected function assertRestRequestReturns404(
        string $path,
        string $method,
        array $content = [],
        array $server = []
    ) : void {
        $request = Request::create(
            $path,
            $method,
            $content,
            [],
            [],
            $server
        );

        $expected = [
            'code' => 404,
            'message' => 'Not Found',
        ];

        $this->assertRequest($request, Response::HTTP_NOT_FOUND, \json_encode($expected), 'application/json');
    }

    protected function prettifyJson(string $content) : ?string
    {
        return \json_encode(\json_decode($content), \JSON_PRETTY_PRINT);
    }

    /**
     * Get a fake file containing only empty space of a certain size.
     */
    protected function getRequestUploadLargeFile(int $bytes, string $originalName = 'large_file.txt') : UploadedFile
    {
        $root      = vfsStream::setup();
        $largeFile = vfsStream::newFile('large.txt')
            ->withContent(new LargeFileContent($bytes))
            ->at($root);

        return new UploadedFile($largeFile->url(), $originalName);
    }

    /**
     * Get a fake text upload file.
     */
    protected function getRequestUploadPdfFile(string $originalName = 'fake_pdf.pdf') : UploadedFile
    {
        return new UploadedFile(
            __DIR__ . '/Fixtures/Resources/fake_pdf.pdf',
            $originalName
        );
    }

    /**
     * Get a fake text upload file.
     */
    protected function getRequestUploadTextFile(string $originalName = 'fake_text.txt') : UploadedFile
    {
        return new UploadedFile(
            __DIR__ . '/Fixtures/Resources/fake_text.txt',
            $originalName
        );
    }

    /**
     * Get a fake image upload file.
     *
     * @param string     $imageType    The image type to set. Must be one of the IMAGE_TYPE_* constants.
     * @param string     $originalName The name for the original file should have.
     * @param int[]|null $imageSize    Example: ['width' => 10, 'height' => 20].
     */
    protected function getRequestUploadImageFile(
        string $imageType = self::IMAGE_TYPE_PNG,
        ?string $originalName = null,
        ?array $imageSize = null
    ) : UploadedFile {
        if (! \in_array($imageType, self::IMAGE_TYPES, true)) {
            throw new \InvalidArgumentException(\sprintf('Unknown image type %s', $imageType));
        }

        $originalName  = $originalName ?: 'fake_image';
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

        return new UploadedFile($filePath, $originalName);
    }

    /**
     * Get a fake image upload file.
     *
     * @param bool   $withTags     Flag if the media file should have tags defined or not.
     * @param string $originalName The name for the original file should have.
     */
    protected function getRequestUploadAudioFile(bool $withTags, string $originalName = 'fake_audio.mp3') : UploadedFile
    {
        $fileName = $withTags ? 'fake_audio_tags.mp3' : 'fake_audio_notags.mp3';

        return new UploadedFile(
            __DIR__ . '/Fixtures/Resources/' . $fileName,
            $originalName
        );
    }

    /**
     * Get a fake video upload file.
     *
     * @param string $originalName The name for the original file should have.
     */
    protected function getRequestUploadVideoFile(string $originalName = 'fake_video.mpeg') : UploadedFile
    {
        return new UploadedFile(
            __DIR__ . '/Fixtures/Resources/fake_video.mpeg',
            $originalName
        );
    }
}
