<?php

declare(strict_types=1);

namespace Speicher210\FunctionalTestBundle\Test\Twig;

use PHPUnit\Framework\ExpectationFailedException;
use Psl\Str;
use SebastianBergmann\Comparator\ComparisonFailure;
use Speicher210\FunctionalTestBundle\SnapshotUpdater;
use Speicher210\FunctionalTestBundle\SnapshotUpdater\DriverConfigurator;
use Speicher210\FunctionalTestBundle\Test\KernelTestCase;
use Twig\Environment;
use Twig\Extension\AbstractExtension;
use Twig\TwigFunction;

abstract class TemplateTestCase extends KernelTestCase
{
    /**
     * When mocking a twig function, if the twig was already compiled and cached, we can not overwrite the function.
     * We need to temporarily disable cache so that the twig file is recompiled with the mock as a reference to the function.
     *
     * Just disabling cache by calling `$twig->setCache(false)` is not enough if the compiled version was already loaded.
     * We need to change the extension set signature, and we do this by adding a new extension.
     */
    private bool $cacheBusterExtensionAdded = false;

    protected function tearDown(): void
    {
        parent::tearDown();

        $this->cacheBusterExtensionAdded = false;
    }

    protected function mockTwigFunction(string $functionName, callable $callable): void
    {
        $twig = $this->getTwigEnvironment();

        $twig->addFunction(new TwigFunction($functionName, $callable));

        if ($this->cacheBusterExtensionAdded) {
            return;
        }

        $twig->addExtension(
            new class extends AbstractExtension {
            },
        );
        $this->cacheBusterExtensionAdded = true;
    }

    /**
     * @param array<mixed> $actualTwigTemplateContext
     */
    protected function assertTwigTemplateEqualsHtmlFile(string $actualTwigTemplate, array $actualTwigTemplateContext): void
    {
        $twig = $this->getTwigEnvironment();

        $actual = $twig->render($actualTwigTemplate, $actualTwigTemplateContext);
        $actual = Str\trim($actual);

        $expectedFile = $this->getExpectedContentFile('html');

        try {
            self::assertXmlStringEqualsXmlFile($expectedFile, $actual);
        } catch (ExpectationFailedException $e) {
            $comparisonFailure = $e->getComparisonFailure();
            if ($comparisonFailure !== null && DriverConfigurator::isOutputUpdaterEnabled()) {
                // The XML assertion compares DOM documents, so the expected file is updated with the rendered HTML instead.
                SnapshotUpdater::updateText(
                    new ComparisonFailure($comparisonFailure->getExpected(), $actual, $comparisonFailure->getExpectedAsString(), $actual),
                    $expectedFile,
                );
            }

            throw $e;
        }
    }

    protected function getTwigEnvironment(): Environment
    {
        return $this->getContainerService(Environment::class, 'twig');
    }
}
