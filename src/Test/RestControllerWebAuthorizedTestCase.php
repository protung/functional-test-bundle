<?php

declare(strict_types = 1);

namespace Speicher210\FunctionalTestBundle\Test;

use GuzzleHttp\Psr7\ServerRequest;
use Symfony\Component\HttpFoundation\Response;

/**
 * Abstract class for restful controllers that need authentication.
 */
abstract class RestControllerWebAuthorizedTestCase extends RestControllerWebTestCase
{
    const AUTHENTICATION_NONE = null;

    /**
     * The authentication to use.
     *
     * @var string
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
     * Assert that a request to an URL returns 401 if the user is not authenticated.
     *
     * @param string $url The URL to call.
     * @param string $method The HTTP verb.
     */
    protected function assertRestRequestReturns401IfUserIsNotAuthenticated(string $url, string $method)
    {
        static::$authentication = self::AUTHENTICATION_NONE;

        $request = new ServerRequest($method, $url);

        $expected = [
            'error' => [
                'code' => 401,
                'message' => 'Unauthorized'
            ]
        ];

        $this->assertRequest($request, Response::HTTP_UNAUTHORIZED, \json_encode($expected));
    }
}
