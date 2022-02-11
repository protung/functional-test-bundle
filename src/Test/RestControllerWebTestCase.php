<?php

declare(strict_types=1);

namespace Speicher210\FunctionalTestBundle\Test;

use Lexik\Bundle\JWTAuthenticationBundle\Services\JWTTokenManagerInterface;
use PHPUnit\Framework\ExpectationFailedException;
use Psl\Filesystem;
use Psl\Json;
use Psl\Type;
use Speicher210\FunctionalTestBundle\Constraint\JsonResponseContentMatches;
use Speicher210\FunctionalTestBundle\FailTestExpectedOutputFileUpdater\ExpectedOutputFileUpdaterConfigurator;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Security\Core\User\InMemoryUser;
use Symfony\Component\Security\Core\User\UserInterface;

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
        string $message = ''
    ): void {
        static::assertThat($response, new JsonResponseContentMatches($expectedContent), $message);
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
        if (! \interface_exists('Lexik\Bundle\JWTAuthenticationBundle\Services\JWTTokenManagerInterface')) {
            throw new \RuntimeException(
                \sprintf(
                    'Package "%s" was not found. Please install it or overwrite method "%s"',
                    'lexik/jwt-authentication-bundle',
                    __METHOD__
                )
            );
        }

        $jwtManager = Type\object(JWTTokenManagerInterface::class)->coerce(
            static::getContainer()->get('lexik_jwt_authentication.jwt_manager')
        );

        $user = Type\object(UserInterface::class)->coerce(static::$authentication);

        $client->setServerParameter(
            'HTTP_Authorization',
            \sprintf('Bearer %s', $jwtManager->create($user))
        );
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
    ): KernelBrowser {
        $query = Type\string()->coerce(\parse_url($path, \PHP_URL_QUERY) ?? '');
        \parse_str($query, $queryParamsFromPath);

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
    ): KernelBrowser {
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
    ): KernelBrowser {
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
    ): KernelBrowser {
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
    ): KernelBrowser {
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
    protected function assertRestRequest(
        Request $request,
        int $expectedStatusCode = Response::HTTP_OK
    ): KernelBrowser {
        $expected = null;
        if ($expectedStatusCode !== Response::HTTP_NO_CONTENT) {
            $expectedFile = $this->getExpectedResponseContentFile('json');
            if (Filesystem\exists($expectedFile)) {
                $expected = $this->prettifyJson(Filesystem\read_file($expectedFile));
            }
        }

        if ($expectedStatusCode >= 400 && $expectedStatusCode <= 599) {
            $expectedOutputContentType = $this->getExpectedErrorResponseContentType();
        } else {
            $expectedOutputContentType = 'application/json';
        }

        $client = $this->assertRequest($request, $expectedStatusCode, $expected, $expectedOutputContentType);
        $this->clearObjectManager();

        if ($expectedStatusCode !== Response::HTTP_NO_CONTENT) {
            $response = $client->getResponse();
            static::assertResponseHeaderSame($response, 'Content-Type', $expectedOutputContentType);
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
    ): KernelBrowser {
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
    ): void {
        static::assertResponseStatusCode($response, $expectedStatusCode);

        if ($expectedOutputContent !== null) {
            static::assertResponseHeaderSame(
                $response,
                'Content-Type',
                Type\string()->coerce($expectedOutputContentType)
            );

            switch ($response->headers->get('Content-Type')) {
                case 'image/png':
                case 'image/jpeg':
                case 'image/jpg':
                    static::assertImageSimilarity(
                        $expectedOutputContent,
                        Type\string()->coerce($response->getContent())
                    );
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

    private function assertJsonContentOutput(Response $response, string $expectedOutputContent): void
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
    ): void {
        $request = Request::create(
            $path,
            $method,
            $content,
            [],
            [],
            $server
        );

        $this->assertRequest(
            $request,
            Response::HTTP_FORBIDDEN,
            $this->getExpected403Response(),
            $this->getExpectedErrorResponseContentType()
        );
    }

    protected function getExpected403Response(): string
    {
        return Filesystem\read_file(__DIR__ . '/Expected/403.json');
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
    ): void {
        static::$authentication = self::AUTHENTICATION_NONE;

        $request = Request::create(
            $url,
            $method,
            [],
            [],
            [],
            $server
        );

        $this->assertRequest(
            $request,
            Response::HTTP_UNAUTHORIZED,
            $this->getExpected401Response(),
            $this->getExpectedErrorResponseContentType()
        );
    }

    protected function getExpected401Response(): string
    {
        return Filesystem\read_file(__DIR__ . '/Expected/401.json');
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
    ): void {
        $request = Request::create(
            $path,
            $method,
            $content,
            [],
            [],
            $server
        );

        $this->assertRequest(
            $request,
            Response::HTTP_NOT_FOUND,
            $this->getExpected404Response(),
            $this->getExpectedErrorResponseContentType()
        );
    }

    protected function getExpected404Response(): string
    {
        return Filesystem\read_file(__DIR__ . '/Expected/404.json');
    }

    protected function getExpectedErrorResponseContentType(): string
    {
        return 'application/json';
    }

    protected function prettifyJson(string $content): string
    {
        return Json\encode(
            Json\decode($content, false),
            true,
            \JSON_PRETTY_PRINT | \JSON_UNESCAPED_SLASHES | \JSON_PRESERVE_ZERO_FRACTION
        );
    }
}
