<?php

declare(strict_types = 1);

namespace Speicher210\FunctionalTestBundle\DependencyInjection;

use Speicher210\FunctionalTestBundle\Test\Loader\AbstractLoader;
use Symfony\Component\Config\Definition\Builder\TreeBuilder;
use Symfony\Component\Config\Definition\ConfigurationInterface;

/**
 * Bundle configuration.
 */
class Configuration implements ConfigurationInterface
{
    /**
     * {@inheritdoc}
     */
    public function getConfigTreeBuilder()
    {
        $treeBuilder = new TreeBuilder();
        $rootNode = $treeBuilder->root('speicher210_functional_test');

        $rootNode
            ->children()
                ->scalarNode('fixture_loader_extend_class')
                    ->cannotBeEmpty()
                    ->defaultValue(AbstractLoader::class)
                ->end()
            ->end();

        return $treeBuilder;
    }
}
