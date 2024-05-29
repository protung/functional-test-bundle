<?php

declare(strict_types=1);

namespace Speicher210\FunctionalTestBundle\Test\Twig;

use PHPUnit\Framework\Attributes\DataProvider;

abstract class IntegrationTestCase extends \Twig\Test\IntegrationTestCase
{
    /** @param non-empty-string $name */
    final public function __construct(string $name)
    {
        parent::__construct($name);
    }

    /**
     * We want to completely disable this test as we will not want to support any legacy twig extensions.
     */
    final public function testLegacyIntegration(
        mixed $file = null,
        mixed $message = null,
        mixed $condition = null,
        mixed $templates = null,
        mixed $exception = null,
        mixed $outputs = null,
        mixed $deprecation = null,
    ): void {
        $this->expectNotToPerformAssertions();
    }

    /**
     * This is a workaround to fix PHPUnit 10 deprecations until Twig adds support for it.
     * See https://github.com/twigphp/Twig/pull/3813
     *
     * @return iterable<mixed>
     */
    public static function dataProviderTestIntegration(): iterable
    {
        return (new static('test'))->getTests('test');
    }

    #[DataProvider('dataProviderTestIntegration')]
    public function testIntegration(mixed $file, mixed $message, mixed $condition, mixed $templates, mixed $exception, mixed $outputs, mixed $deprecation = ''): void
    {
        parent::testIntegration($file, $message, $condition, $templates, $exception, $outputs, $deprecation);
    }
}
