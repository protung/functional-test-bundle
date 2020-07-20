<?php

declare(strict_types=1);

namespace Speicher210\FunctionalTestBundle\Test;

use Speicher210\FunctionalTestBundle\Constraint\ResponseContentMatchesFile;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Component\HttpFoundation\Response;

abstract class WebTestCase extends KernelTestCase
{
    /**
     * {@inheritdoc}
     */
    protected static function createClient(array $server = []) : KernelBrowser
    {
        /** @var KernelBrowser $client */
        $client = static::$container->get('test.client');
        $client->setServerParameters($server);

        return $client;
    }

    /**
     * Get the expected response content file.
     *
     * @param string $type The file type (txt, yml, etc).
     */
    protected function getExpectedResponseContentFile(string $type) : string
    {
        return $this->getExpectedContentFile($type);
    }

    public static function assertResponseContentMatchesFile(
        Response $response,
        string $expectedFile,
        string $message = ''
    ) : void {
        static::assertFileExists($expectedFile);
        static::assertThat($response->getContent(), new ResponseContentMatchesFile($expectedFile), $message);
    }
}
