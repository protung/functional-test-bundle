<?php

declare(strict_types=1);

namespace Speicher210\FunctionalTestBundle\Test\Twig;

use Psl\Str;
use Psl\Type;
use Speicher210\FunctionalTestBundle\Test\KernelTestCase;
use Twig\Environment;
use Twig\Extension\AbstractExtension;
use Twig\TwigFunction;

abstract class TemplateTestCase extends KernelTestCase
{
    private bool $disableTwigCache = false;

    protected function mockTwigFunction(string $functionName, callable $callable): void
    {
        $twig = $this->getTwigEnvironment();

        $twig->addFunction(new TwigFunction($functionName, $callable));

        // when mocking a twig function, if the twig was already compiled and cached, we can not overwrite the function.
        // we need to temporarily disable cache so that the twig file is recompiled with the mock as a reference to the function.
        $this->disableTwigCache = true;
    }

    /**
     * @param array<mixed> $actualTwigTemplateContext
     */
    protected function assertTwigTemplateEqualsHtmlFile(string $actualTwigTemplate, array $actualTwigTemplateContext): void
    {
        $twig = $this->getTwigEnvironment();

        // just disabling cache by calling `$twig->setCache(false)` is not enough if the compiled version was already loaded.
        // we need to change the extension set signature and we do this by adding a new extension
        if ($this->disableTwigCache) {
            $twig->addExtension(
                new class extends AbstractExtension {
                }
            );
        }

        $actual = $twig->render($actualTwigTemplate, $actualTwigTemplateContext);
        $actual = Str\trim($actual);

        $expectedFile = $this->getExpectedContentFile('html');

//        file_put_contents($expectedFile, $actual);self::fail('Expected updated.');
        self::assertXmlStringEqualsXmlFile($expectedFile, $actual);
    }

    private function getTwigEnvironment(): Environment
    {
        return Type\instance_of(Environment::class)->coerce($this->getContainerService('twig'));
    }
}
