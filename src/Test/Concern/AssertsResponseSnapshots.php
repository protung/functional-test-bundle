<?php

declare(strict_types=1);

namespace Speicher210\FunctionalTestBundle\Test\Concern;

use PHPUnit\Framework\TestCase;
use Speicher210\FunctionalTestBundle\Constraint\JsonContentMatches;
use Speicher210\FunctionalTestBundle\Constraint\ResponseContentMatchesFile;
use Symfony\Component\HttpFoundation\Response;

/**
 * Compares the content of a response with an expected file or JSON content.
 *
 * @phpstan-require-extends TestCase
 */
trait AssertsResponseSnapshots
{
    /**
     * @param non-empty-string $type
     *
     * @return non-empty-string
     */
    abstract protected function getExpectedContentFile(string $type): string;

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

    public static function assertResponseContentMatchesFile(
        Response $response,
        string $expectedFile,
        string $message = '',
    ): void {
        static::assertFileExists($expectedFile);
        static::assertThat($response, new ResponseContentMatchesFile($expectedFile), $message);
    }

    public static function assertJsonResponseContent(
        Response $response,
        string $expectedContent,
        string $message = '',
    ): void {
        static::assertThat($response->getContent(), new JsonContentMatches($expectedContent), $message);
    }
}
