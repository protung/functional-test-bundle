<?php

declare(strict_types=1);

namespace Speicher210\FunctionalTestBundle;

use Psl\Type;
use Speicher210\FunctionalTestBundle\Command\TestStubCreateCommand;
use Speicher210\FunctionalTestBundle\Test\Loader\AbstractLoader;
use Symfony\Component\Config\Definition\Builder\ArrayNodeDefinition;
use Symfony\Component\Config\Definition\Configurator\DefinitionConfigurator;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Loader\Configurator\ContainerConfigurator;
use Symfony\Component\HttpKernel\Bundle\AbstractBundle;

final class Speicher210FunctionalTestBundle extends AbstractBundle
{
    public function configure(DefinitionConfigurator $definition): void
    {
        // On Symfony 6.4 the root node is typed as NodeDefinition, although it is always an ArrayNodeDefinition.
        Type\instance_of(ArrayNodeDefinition::class)->coerce($definition->rootNode())
            ->children()
                ->scalarNode('fixture_loader_extend_class')
                    ->defaultValue(AbstractLoader::class)
                ->end()
            ->end();
    }

    /**
     * @param array<mixed> $config
     */
    public function loadExtension(array $config, ContainerConfigurator $configurator, ContainerBuilder $container): void
    {
        $configurator->services()
            ->set(TestStubCreateCommand::class)
                ->arg('$fixtureLoaderExtendClass', $config['fixture_loader_extend_class'])
                ->tag('console.command');
    }
}
