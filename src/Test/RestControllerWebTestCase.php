<?php

declare(strict_types = 1);

namespace Speicher210\FunctionalTestBundle\Test;

use Coduo\PHPMatcher\Factory\SimpleFactory;
use Coduo\PHPMatcher\Matcher;
use Symfony\Bundle\FrameworkBundle\Client;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Abstract class for restful controllers.
 */
abstract class RestControllerWebTestCase extends WebTestCase
{
    public const AUTHENTICATION_NONE = null;

    /**
     * @var Matcher
     */
    private static $matcher;

    /**
     * The authentication to use.
     *
     * @var string|null
     */
    protected static $authentication;

    /**
     * Tokens from authorization.
     *
     * @var array
     */
    protected static $authTokens = [];

    /**
     * {@inheritdoc}
     */
    public function setUp()
    {
        parent::setUp();
        static::$authentication = self::AUTHENTICATION_NONE;
    }

    /**
     * {@inheritdoc}
     */
    protected function tearDown()
    {
        parent::tearDown();
        static::$authentication = self::AUTHENTICATION_NONE;
    }

    /**
     * Shorthand method for assertRestRequest() with a GET request.
     *
     * @param string $path The API path to test.
     * @param integer $expectedStatusCode The expected HTTP response code.
     * @param array $server The server parameters.
     *
     * @return Client
     */
    protected function assertRestGetPath(string $path, int $expectedStatusCode = Response::HTTP_OK, array $server = [])
    {
        $request = Request::create(
            $path,
            Request::METHOD_GET,
            [],
            [],
            [],
            $server
        );

        return $this->assertRestRequest($request, $expectedStatusCode);
    }

    /**
     * Shorthand method for assertRestRequest() with a POST request.
     *
     * @param string $path The API path to test.
     * @param array $content The POST content.
     * @param int $expectedStatusCode The expected HTTP response code.
     * @param array $files The files to send with the request.
     * @param array $server The server parameters.
     *
     * @return Client
     */
    protected function assertRestPostPath(
        string $path,
        array $content = [],
        int $expectedStatusCode = Response::HTTP_OK,
        array $files = [],
        array $server = []
    ) {
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
     * @param string $path The API path to test.
     * @param array $content The PATCH content.
     * @param integer $expectedStatusCode The expected HTTP response code.
     * @param array $files The files to send with the request.
     * @param array $server The server parameters.
     *
     * @return Client
     */
    protected function assertRestPatchPath(
        string $path,
        array $content = [],
        int $expectedStatusCode = Response::HTTP_NO_CONTENT,
        array $files = [],
        array $server = []
    ) {
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
     * @param string $path The API path to test.
     * @param array $content The PUT content.
     * @param integer $expectedStatusCode The expected HTTP response code.
     * @param array $files The files to send with the request.
     * @param array $server The server parameters.
     *
     * @return Client
     */
    protected function assertRestPutPath(
        string $path,
        array $content = [],
        int $expectedStatusCode = Response::HTTP_NO_CONTENT,
        array $files = [],
        array $server = []
    ) {
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
     * @param string $path The API path to test.
     * @param integer $expectedStatusCode The expected HTTP response code.
     *
     * @return Client
     */
    protected function assertRestDeletePath(string $path, int $expectedStatusCode = Response::HTTP_OK)
    {
        $request = Request::create(
            $path,
            Request::METHOD_DELETE
        );

        return $this->assertRestRequest($request, $expectedStatusCode);
    }

    /**
     * Assert if a request returns the expected REST result.
     *
     * @param Request $request The request to simulate.
     * @param integer $expectedStatusCode The expected HTTP response code.
     *
     * @return Client
     */
    protected function assertRestRequest(Request $request, int $expectedStatusCode = Response::HTTP_OK)
    {
        $expectedFile = null;
        $expected = null;
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
            static::assertTrue($response->headers->contains('Content-Type', 'application/json'));
        }

        return $client;
    }

    /**
     * Assert if a request returns the expected result.
     *
     * @param Request $request The request to simulate.
     * @param integer $expectedStatusCode The expected HTTP response code.
     * @param string $expectedOutputContent The expected output content.
     * @param string|null $expectedOutputContentType
     *
     * @return Client
     */
    protected function assertRequest(
        Request $request,
        int $expectedStatusCode = Response::HTTP_OK,
        string $expectedOutputContent = null,
        string $expectedOutputContentType = null
    ) {
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

    /**
     * Assert a request response.
     *
     * @param Response $response The response.
     * @param integer $expectedStatusCode The expected HTTP response code.
     * @param string $expectedOutputContent The expected output.
     * @param null|string $expectedOutputContentType
     */
    protected function assertRequestResponse(
        Response $response,
        int $expectedStatusCode,
        ?string $expectedOutputContent,
        ?string $expectedOutputContentType
    ): void {
        static::assertSame(
            $expectedStatusCode,
            $response->getStatusCode(),
            \sprintf(
                'Failed asserting response code "%s" matches expected "%s". Response body was: %s',
                $response->getStatusCode(),
                $expectedStatusCode,
                $response->getContent()
            )
        );

        if ($expectedOutputContent !== null) {
            $actualContentType = $response->headers->get('Content-Type');
            static::assertSame(
                $expectedOutputContentType,
                $actualContentType,
                \sprintf('Failed asserting response content type matches "%s"', $expectedOutputContentType)
            );
            switch ($actualContentType) {
                case 'image/png';
                case 'image/jpeg';
                case 'image/jpg';
                    $this->assertImagesSimilarity($expectedOutputContent, $response->getContent());
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

    /**
     * @param Response $response
     * @param string $expectedOutputContent
     */
    private function assertJsonContentOutput(Response $response, string $expectedOutputContent): void
    {
        $matcher = static::getMatcher();

        $actual = $response->getContent();
        $result = $matcher->match($actual, $expectedOutputContent);
        if ($result !== true) {
            $difference = $matcher->getError();

            // Quick check if actual is valid JSON and if it is prettify it.
            if (\json_decode($actual) !== null) {
                $actual = $this->prettifyJson($actual);
            }

            static::assertJsonStringEqualsJsonString($expectedOutputContent, $actual, $difference);
        }
    }

    /**
     * Assert that a request to an URL returns 403.
     *
     * @param string $path The API path to test.
     * @param string $method The HTTP verb.
     * @param array $content The POST content.
     */
    protected function assertRestRequestReturns403(string $path, string $method, array $content = []): void
    {
        $request = Request::create(
            $path,
            $method,
            $content
        );

        $expected = [
            'code' => 403,
            'message' => 'Forbidden'
        ];

        $this->assertRequest($request, Response::HTTP_FORBIDDEN, \json_encode($expected), 'application/json');
    }

    /**
     * Assert that a request to an URL returns 401 if the user is not authenticated.
     *
     * @param string $url The URL to call.
     * @param string $method The HTTP verb.
     */
    protected function assertRestRequestReturns401IfUserIsNotAuthenticated(string $url, string $method): void
    {
        static::$authentication = self::AUTHENTICATION_NONE;

        $request = Request::create(
            $url,
            $method
        );

        $expected = [
            'code' => 401,
            'message' => 'Unauthorized'
        ];

        $this->assertRequest($request, Response::HTTP_UNAUTHORIZED, \json_encode($expected), 'application/json');
    }

    /**
     * Assert that a request to an URL returns 404.
     *
     * @param string $path The API path to test.
     * @param string $method The HTTP verb.
     * @param array $content The POST content.
     */
    protected function assertRestRequestReturns404(string $path, string $method, array $content = []): void
    {
        $request = Request::create(
            $path,
            $method,
            $content
        );

        $expected = [
            'code' => 404,
            'message' => 'Not Found'
        ];

        $this->assertRequest($request, Response::HTTP_NOT_FOUND, \json_encode($expected), 'application/json');
    }

    /**
     * @return Matcher
     */
    public static function getMatcher(): Matcher
    {
        if (self::$matcher === null) {
            $factory = new SimpleFactory();
            self::$matcher = $factory->createMatcher();
        }

        return self::$matcher;
    }

    /**
     * @param string $content The content to prettify.
     *
     * @return string|null
     */
    protected function prettifyJson(string $content): ?string
    {
        return \json_encode(\json_decode($content), \JSON_PRETTY_PRINT);
    }

    /**
     * Get a fake text upload file.
     *
     * @param string $originalName The name for the original file should have.
     * @return UploadedFile
     */
    protected function getRequestUploadPdfFile(string $originalName = 'fake_pdf.pdf'): UploadedFile
    {
        return new UploadedFile(
            __DIR__ . '/Fixtures/Resources/fake_pdf.pdf',
            $originalName
        );
    }

    /**
     * Get a fake text upload file.
     *
     * @param string $originalName The name for the original file should have.
     * @return UploadedFile
     */
    protected function getRequestUploadTextFile(string $originalName = 'fake_text.txt'): UploadedFile
    {
        return new UploadedFile(
            __DIR__ . '/Fixtures/Resources/fake_text.txt',
            $originalName
        );
    }

    /**
     * Get a fake image upload file.
     *
     * @param string $originalName The name for the original file should have.
     * @return UploadedFile
     */
    protected function getRequestUploadImageFile(string $originalName = 'fake_image.png'): UploadedFile
    {
        return new UploadedFile(
            __DIR__ . '/Fixtures/Resources/fake_image.png',
            $originalName
        );
    }

    /**
     * Get a fake image upload file.
     *
     * @param boolean $withTags Flag if the media file should have tags defined or not.
     * @param string $originalName The name for the original file should have.
     *
     * @return UploadedFile
     */
    protected function getRequestUploadAudioFile(bool $withTags, string $originalName = 'fake_audio.mp3'): UploadedFile
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
     *
     * @return UploadedFile
     */
    protected function getRequestUploadVideoFile(string $originalName = 'fake_video.mpeg'): UploadedFile
    {
        return new UploadedFile(
            __DIR__ . '/Fixtures/Resources/fake_video.mpeg',
            $originalName
        );
    }
}
