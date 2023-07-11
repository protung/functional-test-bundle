<?php

declare(strict_types=1);

namespace Speicher210\FunctionalTestBundle\Test;

use PHPUnit\Framework\ExpectationFailedException;
use Psl\File;
use Psl\Filesystem;
use Psl\Json;
use Psl\Type;
use Speicher210\FunctionalTestBundle\Constraint\JsonContentMatches;
use Speicher210\FunctionalTestBundle\SnapshotUpdater;
use Speicher210\FunctionalTestBundle\SnapshotUpdater\DriverConfigurator;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Security\Core\User\InMemoryUser;
use Symfony\Component\Security\Core\User\UserInterface;

use function array_replace_recursive;
use function parse_str;
use function parse_url;

use const JSON_PRESERVE_ZERO_FRACTION;
use const JSON_PRETTY_PRINT;
use const JSON_UNESCAPED_SLASHES;
use const PHP_URL_QUERY;

/**
 * Abstract class for restful controllers.
 */
abstract class RestControllerWebTestCase extends WebTestCase
{
    public const AUTHENTICATION_NONE = null;

    /**
     * The authenticated user for the test.
     */
    protected static UserInterface|null $authentication;

    public static function assertJsonResponseContent(
        Response $response,
        string $expectedContent,
        string $message = '',
    ): void {
        static::assertThat($response->getContent(), new JsonContentMatches($expectedContent), $message);
    }

    protected function setUp(): void
    {
        parent::setUp();

        static::$authentication = self::AUTHENTICATION_NONE;
    }

    protected function tearDown(): void
    {
        parent::tearDown();

        static::$authentication = self::AUTHENTICATION_NONE;
    }

    /**
     * @param array<mixed> $server
     */
    protected static function createClient(array $server = []): KernelBrowser
    {
        $client = parent::createClient($server);

        if (self::$authentication === null) {
            return $client;
        }

        static::authenticateClient($client);

        return $client;
    }

    protected static function authenticateClient(KernelBrowser $client): void
    {
        $user = Type\instance_of(UserInterface::class)->coerce(static::$authentication);
        $client->loginUser($user);
    }

    protected function loginAsAdmin(): void
    {
        $user = new InMemoryUser('admin', null, ['ROLE_ADMIN']);
        $this->loginAs($user);
    }

    protected function loginAs(UserInterface $user): void
    {
        self::$authentication = $user;
    }

    /**
     * Shorthand method for assertRestRequest() with a GET request.
     *
     * @param string       $path               The API path to test.
     * @param array<mixed> $queryParams        The query parameters.
     * @param int          $expectedStatusCode The expected HTTP response code.
     * @param array<mixed> $server             The server parameters.
     */
    protected function assertRestGetPath(
        string $path,
        array $queryParams = [],
        int $expectedStatusCode = Response::HTTP_OK,
        array $server = [],
    ): KernelBrowser {
        $query = Type\string()->coerce(parse_url($path, PHP_URL_QUERY) ?? '');
        parse_str($query, $queryParamsFromPath);

        $request = $this->prepareRequest(
            $path,
            parameters: array_replace_recursive($queryParamsFromPath, $queryParams),
            server: $server,
        );

        return $this->assertRestRequest($request, $expectedStatusCode);
    }

    /**
     * Shorthand method for assertRestRequest() with a POST request.
     *
     * @param string       $path               The API path to test.
     * @param array<mixed> $content            The POST content.
     * @param int          $expectedStatusCode The expected HTTP response code.
     * @param array<mixed> $files              The files to send with the request.
     * @param array<mixed> $server             The server parameters.
     */
    protected function assertRestPostPath(
        string $path,
        array $content = [],
        int $expectedStatusCode = Response::HTTP_OK,
        array $files = [],
        array $server = [],
    ): KernelBrowser {
        $request = $this->prepareRequest(
            $path,
            method: Request::METHOD_POST,
            parameters: $content,
            files: $files,
            server: $server,
        );

        return $this->assertRestRequest($request, $expectedStatusCode);
    }

    /**
     * Shorthand method for assertRestRequest() with a PATCH request.
     *
     * @param string       $path               The API path to test.
     * @param array<mixed> $content            The PATCH content.
     * @param int          $expectedStatusCode The expected HTTP response code.
     * @param array<mixed> $files              The files to send with the request.
     * @param array<mixed> $server             The server parameters.
     */
    protected function assertRestPatchPath(
        string $path,
        array $content = [],
        int $expectedStatusCode = Response::HTTP_NO_CONTENT,
        array $files = [],
        array $server = [],
    ): KernelBrowser {
        $request = $this->prepareRequest(
            $path,
            method: Request::METHOD_PATCH,
            parameters: $content,
            files: $files,
            server: $server,
        );

        return $this->assertRestRequest($request, $expectedStatusCode);
    }

    /**
     * Shorthand method for assertRestRequest() with a PUT request.
     *
     * @param string       $path               The API path to test.
     * @param array<mixed> $content            The PUT content.
     * @param int          $expectedStatusCode The expected HTTP response code.
     * @param array<mixed> $files              The files to send with the request.
     * @param array<mixed> $server             The server parameters.
     */
    protected function assertRestPutPath(
        string $path,
        array $content = [],
        int $expectedStatusCode = Response::HTTP_NO_CONTENT,
        array $files = [],
        array $server = [],
    ): KernelBrowser {
        $request = $this->prepareRequest(
            $path,
            method: Request::METHOD_PUT,
            parameters: $content,
            files: $files,
            server: $server,
        );

        return $this->assertRestRequest($request, $expectedStatusCode);
    }

    /**
     * Shorthand method for assertRestRequest() with a DELETE request.
     *
     * @param string       $path               The API path to test.
     * @param int          $expectedStatusCode The expected HTTP response code.
     * @param array<mixed> $server             The server parameters.
     */
    protected function assertRestDeletePath(
        string $path,
        int $expectedStatusCode = Response::HTTP_NO_CONTENT,
        array $server = [],
    ): KernelBrowser {
        $request = $this->prepareRequest(
            $path,
            method: Request::METHOD_DELETE,
            server: $server,
        );

        return $this->assertRestRequest($request, $expectedStatusCode);
    }

    /**
     * Assert if a request returns the expected REST result.
     *
     * @param Request $request            The request to simulate.
     * @param int     $expectedStatusCode The expected HTTP response code.
     */
    protected function assertRestRequest(Request $request, int $expectedStatusCode = Response::HTTP_OK): KernelBrowser
    {
        $expected = null;
        if ($expectedStatusCode !== Response::HTTP_NO_CONTENT) {
            $expectedFile = $this->getExpectedResponseContentFile('json');
            if (Filesystem\exists($expectedFile)) {
                $expected = $this->prettifyJson(File\read($expectedFile));
            }
        }

        $expectedOutputContentType = match (true) {
            $expectedStatusCode >= Response::HTTP_BAD_REQUEST => $this->getExpectedErrorResponseContentType(),
            $expectedStatusCode === Response::HTTP_NO_CONTENT => null,
            default => 'application/json'
        };

        // If request contains files we cannot make a JSON request, so we perform a generic one.
        if ($request->files->count() > 0) {
            $client = $this->makeRequest($request);
        } else {
            $client = $this->makeJsonRequest($request);
        }

        $this->assertRestRequestResponse(
            $client->getResponse(),
            $expectedStatusCode,
            $expected,
            $expectedOutputContentType,
        );

        $this->clearObjectManager();

        return $client;
    }

    protected function makeRequest(Request $request): KernelBrowser
    {
        $client = static::createClient();

        $client->request(
            $request->getMethod(),
            $request->getUri(),
            $request->request->all(),
            $request->files->all(),
            $request->server->all(),
            $request->getContent(),
        );

        return $client;
    }

    protected function makeJsonRequest(Request $request): KernelBrowser
    {
        $client = static::createClient();

        $server                 = $request->server->all();
        $server['CONTENT_TYPE'] = 'application/json';
        $server['HTTP_ACCEPT']  = 'application/json';

        $client->jsonRequest(
            $request->getMethod(),
            $request->getUri(),
            $request->request->all(),
            $server,
        );

        return $client;
    }

    protected function assertRestRequestResponse(
        Response $response,
        int $expectedStatusCode,
        string|null $expectedOutputContent,
        string|null $expectedOutputContentType,
    ): void {
        static::assertResponseStatusCode($response, $expectedStatusCode);

        if ($expectedOutputContent !== null) {
            static::assertResponseHeaderSame(
                $response,
                'Content-Type',
                Type\string()->coerce($expectedOutputContentType),
            );

            switch ($response->headers->get('Content-Type')) {
                case 'image/png':
                case 'image/jpeg':
                case 'image/jpg':
                    $this->assertImageContentOutput($response, $expectedOutputContent);
                    break;
                case 'application/json':
                case 'application/problem+json':
                default:
                    $this->assertJsonContentOutput($response, $expectedOutputContent);
                    break;
            }
        } else {
            static::assertEmpty($response->getContent());
        }
    }

    private function assertImageContentOutput(Response $response, string $expectedOutputContent): void
    {
        static::assertImageSimilarity(
            $expectedOutputContent,
            Type\string()->coerce($response->getContent()),
        );
    }

    private function assertJsonContentOutput(Response $response, string $expectedOutputContent): void
    {
        try {
            static::assertJsonResponseContent($response, $expectedOutputContent);
        } catch (ExpectationFailedException $e) {
            $comparisonFailure = $e->getComparisonFailure();
            if ($comparisonFailure !== null && DriverConfigurator::isOutputUpdaterEnabled()) {
                SnapshotUpdater::updateJson(
                    $comparisonFailure,
                    $this->getCurrentExpectedResponseContentFile('json'),
                );
            }

            throw $e;
        }
    }

    /**
     * Assert that a request to an URL returns 403.
     *
     * @param string       $path    The API path to test.
     * @param string       $method  The HTTP verb.
     * @param array<mixed> $content The POST content.
     * @param array<mixed> $server  The server parameters.
     */
    protected function assertRestRequestReturns403(
        string $path,
        string $method,
        array $content = [],
        array $server = [],
    ): void {
        $request = $this->prepareRequest(
            $path,
            method: $method,
            parameters: $content,
            server: $server,
        );

        $client = $this->makeJsonRequest($request);

        $this->assertRestRequestResponse(
            $client->getResponse(),
            Response::HTTP_FORBIDDEN,
            $this->getExpected403Response(),
            $this->getExpectedErrorResponseContentType(),
        );
    }

    protected function getExpected403Response(): string
    {
        return File\read(__DIR__ . '/Expected/403.json');
    }

    /**
     * Assert that a request to an URL returns 401 if the user is not authenticated.
     *
     * @param string       $path   The URL to call.
     * @param string       $method The HTTP verb.
     * @param array<mixed> $server The server parameters.
     */
    protected function assertRestRequestReturns401IfUserIsNotAuthenticated(
        string $path,
        string $method,
        array $server = [],
    ): void {
        static::$authentication = self::AUTHENTICATION_NONE;

        $request = $this->prepareRequest(
            $path,
            method: $method,
            server: $server,
        );

        $client = $this->makeJsonRequest($request);

        $this->assertRestRequestResponse(
            $client->getResponse(),
            Response::HTTP_UNAUTHORIZED,
            $this->getExpected401Response(),
            $this->getExpectedErrorResponseContentType(),
        );
    }

    protected function getExpected401Response(): string
    {
        return File\read(__DIR__ . '/Expected/401.json');
    }

    /**
     * Assert that a request to an URL returns 404.
     *
     * @param string       $path    The API path to test.
     * @param string       $method  The HTTP verb.
     * @param array<mixed> $content The POST content.
     * @param array<mixed> $server  The server parameters.
     */
    protected function assertRestRequestReturns404(
        string $path,
        string $method,
        array $content = [],
        array $server = [],
    ): void {
        $request = $this->prepareRequest(
            $path,
            method: $method,
            parameters: $content,
            server: $server,
        );

        $client = $this->makeJsonRequest($request);

        $this->assertRestRequestResponse(
            $client->getResponse(),
            Response::HTTP_NOT_FOUND,
            $this->getExpected404Response(),
            $this->getExpectedErrorResponseContentType(),
        );
    }

    protected function getExpected404Response(): string
    {
        return File\read(__DIR__ . '/Expected/404.json');
    }

    protected function getExpectedErrorResponseContentType(): string
    {
        return 'application/problem+json';
    }

    protected function prettifyJson(string $content): string
    {
        return Json\encode(
            Json\decode($content, false),
            true,
            JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_PRESERVE_ZERO_FRACTION,
        );
    }
}
