<?php

declare(strict_types = 1);

namespace Speicher210\FunctionalTestBundle\Test;

use Coduo\PHPMatcher\Factory\SimpleFactory;
use Coduo\PHPMatcher\Matcher;
use GuzzleHttp\Psr7\ServerRequest;
use Psr\Http\Message\ServerRequestInterface;
use Symfony\Bundle\FrameworkBundle\Client;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\HttpFoundation\Response;

/**
 * Abstract class for restful controllers.
 */
abstract class RestControllerWebTestCase extends WebTestCase
{

    /**
     * @var Matcher
     */
    private static $matcher;

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
        $request = new ServerRequest('GET', $path, [], null, '1.1', $server);

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
        $request = (new ServerRequest('POST', $path, [], null, '1.1', $server))
            ->withParsedBody($content)
            ->withUploadedFiles($files);

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
        $request = (new ServerRequest('PATCH', $path, [], null, '1.1', $server))
            ->withParsedBody($content)
            ->withUploadedFiles($files);

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
        $request = (new ServerRequest('PUT', $path, [], null, '1.1', $server))
            ->withParsedBody($content)
            ->withUploadedFiles($files);

        return $this->assertRestRequest($request, $expectedStatusCode);
    }

    /**
     * Shorthand method for assertRestRequest() with a DELETE request.
     *
     * @param string $path The API path to test.
     * @param integer $expectedStatusCode The expected HTTP response code.
     *
     * @return \Symfony\Bundle\FrameworkBundle\Client
     */
    protected function assertRestDeletePath(string $path, int $expectedStatusCode = Response::HTTP_OK)
    {
        $request = new ServerRequest('DELETE', $path);

        return $this->assertRestRequest($request, $expectedStatusCode);
    }

    /**
     * Assert if a request returns the expected REST result.
     *
     * @param ServerRequestInterface $request The request to simulate.
     * @param integer $expectedStatusCode The expected HTTP response code.
     *
     * @return Client
     */
    protected function assertRestRequest(ServerRequestInterface $request, int $expectedStatusCode = Response::HTTP_OK)
    {
        $expectedFile = null;
        $expected = null;
        if ($expectedStatusCode !== Response::HTTP_NO_CONTENT) {
            $expectedFile = $this->getExpectedResponseContentFile('json');
            $expected = $this->prettifyJson(file_get_contents($expectedFile));
        }

        $client = $this->assertRequest($request, $expectedStatusCode, $expected);
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
     * @param ServerRequestInterface $request The request to simulate.
     * @param integer $expectedStatusCode The expected HTTP response code.
     * @param string $expectedOutput The expected output.
     *
     * @return \Symfony\Bundle\FrameworkBundle\Client
     */
    protected function assertRequest(
        ServerRequestInterface $request,
        int $expectedStatusCode = Response::HTTP_OK,
        ?string $expectedOutput = null
    ) {
        $client = static::createClient();

        $serverParams = $request->getServerParams();
        foreach ($request->getHeaders() as $name => $value) {
            $serverParams['HTTP_' . $name] = $value;
        }

        $client->request(
            $request->getMethod(),
            (string)$request->getUri(),
            $request->getParsedBody() ?: [],
            $request->getUploadedFiles(),
            $serverParams,
            $request->getBody()->getContents()
        );

        $response = $client->getResponse();
        $this->assertRequestResponse($response, $expectedStatusCode, $expectedOutput);

        return $client;
    }

    /**
     * Assert a request response.
     *
     * @param Response $response The response.
     * @param integer $expectedStatusCode The expected HTTP response code.
     * @param string $expectedOutput The expected output.
     */
    protected function assertRequestResponse(Response $response, int $expectedStatusCode, ?string $expectedOutput)
    {
        static::assertSame(
            $expectedStatusCode,
            $response->getStatusCode(),
            sprintf(
                'Failed asserting response code "%s" matches expected "%s". Response body was: %s',
                $response->getStatusCode(),
                $expectedStatusCode,
                $response->getContent()
            )
        );

        if ($expectedOutput !== null) {
            $matcher = static::getMatcher();

            $actual = $response->getContent();
            $result = $matcher->match($actual, $expectedOutput);
            if ($result !== true) {
                $difference = $matcher->getError();

                static::assertJsonStringEqualsJsonString($expectedOutput, $actual, $difference);
            }
        } else {
            static::assertEmpty($response->getContent());
        }
    }

    /**
     * Assert that a request to an URL returns 404.
     *
     * @param string $path The API path to test.
     * @param string $method The HTTP verb.
     * @param array $content The POST content.
     */
    protected function assertRestRequestReturns404(string $path, string $method, array $content = [])
    {
        $request = (new ServerRequest($method, $path))->withParsedBody($content);

        $expected = [
            'code' => 404,
            'message' => 'Not Found',
            'errors' => null
        ];

        $this->assertRequest($request, Response::HTTP_NOT_FOUND, json_encode($expected));
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
        return json_encode(json_decode($content), JSON_PRETTY_PRINT);
    }

    /**
     * Get a fake text upload file.
     *
     * @return UploadedFile
     */
    protected function getRequestUploadTextFile(): UploadedFile
    {
        return new UploadedFile(
            __DIR__ . '/Fixtures/Resources/fake_text.txt',
            'fake_text.png'
        );
    }

    /**
     * Get a fake image upload file.
     *
     * @return UploadedFile
     */
    protected function getRequestUploadImageFile(): UploadedFile
    {
        return new UploadedFile(
            __DIR__ . '/Fixtures/Resources/fake_image.png',
            'fake_image.png'
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
}
