<?php

declare(strict_types=1);

namespace Speicher210\FunctionalTestBundle\Tests\Test;

use PHPUnit\Framework\TestCase;
use Speicher210\FunctionalTestBundle\Test\RestControllerWebTestCase;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

use function urldecode;

final class RestControllerWebTestCaseTest extends TestCase
{
    /**
     * @return array<mixed>
     */
    public static function dataProviderTestAssertRestGetPathWithCustomQueryParams(): array
    {
        return [
            ['/test/path', [], '/test/path', null],
            ['/test/path?param=1', [], '/test/path', 'param=1'],
            ['/test/path', ['param' => '1'], '/test/path', 'param=1'],
            ['/test/path?param=1', ['param' => 'override'], '/test/path', 'param=override'],
            ['/test/path?param=1', ['param2' => '2'], '/test/path', 'param=1&param2=2'],
            [
                '/test/path?a[b][c]=1&a[b][d]=2',
                [
                    'a' => [
                        'b' => ['d' => '3'],
                    ],
                ],
                '/test/path',
                'a[b][c]=1&a[b][d]=3',
            ],
            [
                '/test/path?a[b][c]=1&a[b][d]=2',
                [
                    'a' => [
                        'b' => ['d' => '3'],
                        'e' => '5',
                    ],
                    'f' => '4',
                ],
                '/test/path',
                'a[b][c]=1&a[b][d]=3&a[e]=5&f=4',
            ],
            [
                '/test/path?a[b][c]=1&a[b][d]=2&a[e]=5&f=4',
                [
                    'a' => [
                        'b' => ['d' => '3'],
                    ],
                ],
                '/test/path',
                'a[b][c]=1&a[b][d]=3&a[e]=5&f=4',
            ],
        ];
    }

    /**
     * @param array<mixed> $queryParams
     *
     * @dataProvider dataProviderTestAssertRestGetPathWithCustomQueryParams
     */
    public function testAssertRestGetPathWithCustomQueryParams(
        string $path,
        array $queryParams,
        string $expectedPathInfo,
        string|null $expectedQueryString,
    ): void {
        $testClass = new class ('test') extends RestControllerWebTestCase
        {
            /**
             * @param array<mixed> $queryParams
             */
            public function testAssertRestGetPath(string $path, array $queryParams = []): KernelBrowser
            {
                return $this->assertRestGetPath($path, $queryParams);
            }

            protected function assertRestRequest(
                Request $request,
                int $expectedStatusCode = Response::HTTP_OK,
            ): KernelBrowser {
                $client = $this->createMock(KernelBrowser::class);
                $client->method('getRequest')->willReturn($request);

                return $client;
            }
        };

        $client = $testClass->testAssertRestGetPath($path, $queryParams);
        self::assertSame($expectedPathInfo, $client->getRequest()->getPathInfo());
        $requestQueryString = $client->getRequest()->getQueryString();
        self::assertSame(
            $expectedQueryString,
            $requestQueryString !== null ? urldecode($requestQueryString) : null,
        );
    }
}
