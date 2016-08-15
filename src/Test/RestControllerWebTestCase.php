<?php

namespace Speicher210\FunctionalTestBundle\Test;

use Coduo\PHPMatcher\Factory\SimpleFactory;
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
     * Array with the number of assertions against expected files per test.
     *
     * @var array
     */
    private $assertionExpectedFiles = array();

    /**
     * Shorthand method for assertRestRequest() with a GET request.
     *
     * @param string $path The API path to test.
     * @param integer $expectedStatusCode The expected HTTP response code.
     * @param array $server The server parameters.
     *
     * @return \Symfony\Bundle\FrameworkBundle\Client
     */
    protected function assertRestGetPath($path, $expectedStatusCode = Response::HTTP_OK, array $server = array())
    {
        $request = new ServerRequest('GET', $path, array(), null, '1.1', $server);

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
        $path,
        array $content = array(),
        $expectedStatusCode = Response::HTTP_OK,
        array $files = array(),
        array $server = array()
    ) {
        $request = (new ServerRequest('POST', $path, array(), null, '1.1', $server))
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
        $path,
        $content = array(),
        $expectedStatusCode = Response::HTTP_NO_CONTENT,
        array $files = array(),
        array $server = array()
    ) {
        $request = (new ServerRequest('PATCH', $path, array(), null, '1.1', $server))
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
    protected function assertRestDeletePath($path, $expectedStatusCode = Response::HTTP_OK)
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
     * @return \Symfony\Bundle\FrameworkBundle\Client
     */
    protected function assertRestRequest(ServerRequestInterface $request, $expectedStatusCode = Response::HTTP_OK)
    {
        $expectedFile = null;
        $expected = null;
        if ($expectedStatusCode !== Response::HTTP_NO_CONTENT) {
            $expectedFile = $this->getExpectedResponseContentFile('json');
            $expected = $this->prettifyJson(file_get_contents($expectedFile));
        }

        try {
            $client = $this->assertRequest($request, $expectedStatusCode, $expected);
        } catch (\PHPUnit_Framework_ExpectationFailedException $e) {
            if ($expectedFile && isset($_ENV['UPDATE_EXPECTED_OUTPUT']) && $_ENV['UPDATE_EXPECTED_OUTPUT']) {
                $actual = $e->getComparisonFailure()->getActual();
                file_put_contents($expectedFile, json_encode($actual));
            }

            throw $e;
        }

        if ($expectedStatusCode !== Response::HTTP_NO_CONTENT) {
            $response = $client->getResponse();
            $this->assertTrue($response->headers->contains('Content-Type', 'application/json'));
        }
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
        $expectedStatusCode = Response::HTTP_OK,
        $expectedOutput = null
    ) {
        $client = static::createClient();

        $serverParams = $request->getServerParams();
        foreach ($request->getHeaders() as $name => $value) {
            $serverParams['HTTP_' . $name] = $value;
        }

        $client->request(
            $request->getMethod(),
            (string)$request->getUri(),
            $request->getParsedBody() ?: array(),
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
    protected function assertRequestResponse(Response $response, $expectedStatusCode, $expectedOutput)
    {
        $this->assertSame(
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
            $factory = new SimpleFactory();
            $matcher = $factory->createMatcher();
            $actual = $response->getContent();
            $result = $matcher->match($actual, $expectedOutput);
            if ($result !== true) {
                $difference = $matcher->getError();

                $this->assertJsonStringEqualsJsonString($expectedOutput, $actual, $difference);
            }
        } else {
            $this->assertEmpty($response->getContent());
        }
    }

    /**
     * Assert that a request to an URL returns 404.
     *
     * @param string $path The API path to test.
     * @param string $method The HTTP verb.
     * @param array $content The POST content.
     */
    protected function assertRestRequestReturns404($path, $method, array $content = array())
    {
        $request = (new ServerRequest($method, $path))->withParsedBody($content);

        $expected = array(
            'code' => 404,
            'message' => 'Not Found',
            'errors' => null
        );

        $this->assertRequest($request, Response::HTTP_NOT_FOUND, json_encode($expected));
    }

    /**
     * Get the expected response content file.
     *
     * @param string $type The file type (txt, yml, etc).
     *
     * @return string
     */
    protected function getExpectedResponseContentFile($type)
    {
        $reflection = new \ReflectionObject($this);
        $testName = $this->getName(false);
        if (!isset($this->assertionExpectedFiles[$testName])) {
            $this->assertionExpectedFiles[$testName] = 1;
        } else {
            $this->assertionExpectedFiles[$testName]++;
        }

        $expectedFile = $testName . '-' . $this->assertionExpectedFiles[$testName] . '.' . $type;

        return dirname($reflection->getFileName()) . '/Expected/' . $expectedFile;
    }

    /**
     * @param string $content The content to prettify.
     *
     * @return string|null
     */
    protected function prettifyJson($content)
    {
        return json_encode(json_decode($content), JSON_PRETTY_PRINT);
    }

    /**
     * Get a fake text upload file.
     *
     * @return UploadedFile
     */
    protected function getRequestUploadTextFile()
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
    protected function getRequestUploadImageFile()
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
    protected function getRequestUploadAudioFile($withTags, $originalName = 'fake_audio.mp3')
    {
        if ($withTags === true) {
            $fileName = 'fake_audio_tags.mp3';
        } else {
            $fileName = 'fake_audio_notags.mp3';
        }

        return new UploadedFile(
            __DIR__ . '/Fixtures/Resources/' . $fileName,
            $originalName
        );
    }
}
