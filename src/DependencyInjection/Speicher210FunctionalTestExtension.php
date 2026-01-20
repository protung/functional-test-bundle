<?php

declare(strict_types=1);

namespace Speicher210\FunctionalTestBundle\DependencyInjection;

use Speicher210\FunctionalTestBundle\Command\TestStubCreateCommand;
use Symfony\Component\Config\FileLocator;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Loader;
use Symfony\Component\HttpKernel\DependencyInjection\ConfigurableExtension;

final class Speicher210FunctionalTestExtension extends ConfigurableExtension
{
    /**
     * @param array<mixed> $mergedConfig
     */
    public function loadInternal(array $mergedConfig, ContainerBuilder $container): void
    {
        $loader = new Loader\PhpFileLoader($container, new FileLocator(__DIR__ . '/../Resources/config'));
        $loader->load('services.php');

        $container
            ->getDefinition(TestStubCreateCommand::class)
            ->setArgument('$fixtureLoaderExtendClass', $mergedConfig['fixture_loader_extend_class']);
    }
}
