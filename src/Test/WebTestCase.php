<?php

declare(strict_types=1);

namespace Speicher210\FunctionalTestBundle\Test;

use Speicher210\FunctionalTestBundle\Constraint\ResponseContentMatchesFile;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Component\HttpFoundation\Response;

abstract class WebTestCase extends KernelTestCase
{
    /**
     * Array with the number of assertions against expected files per test.
     *
     * @var array<string,int>
     */
    private $assertionExpectedFiles = [];

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
        $reflection = new \ReflectionObject($this);
        $testName   = $this->getName(false);
        if (isset($this->assertionExpectedFiles[$testName])) {
            $this->assertionExpectedFiles[$testName]++;
        } else {
            $this->assertionExpectedFiles[$testName] = 1;
        }

        $expectedFile = $testName . '-' . $this->assertionExpectedFiles[$testName] . '.' . $type;

        return \dirname($reflection->getFileName()) . '/Expected/' . $expectedFile;
    }

    public function getCurrentExpectedResponseContentFile(string $type) : string
    {
        $reflection       = new \ReflectionObject($this);
        $testName         = $this->getName(false);
        $expectedFileName = $testName . '-' . ($this->assertionExpectedFiles[$testName] ?? 1);

        return \dirname($reflection->getFileName()) . '/Expected/' . $expectedFileName . '.' . $type;
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
