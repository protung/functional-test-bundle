<?php

declare(strict_types=1);

namespace Speicher210\FunctionalTestBundle\DependencyInjection;

use Speicher210\FunctionalTestBundle\Test\Loader\AbstractLoader;
use Symfony\Component\Config\Definition\Builder\TreeBuilder;
use Symfony\Component\Config\Definition\ConfigurationInterface;

final class Configuration implements ConfigurationInterface
{
    public function getConfigTreeBuilder() : TreeBuilder
    {
        $treeBuilder = new TreeBuilder('speicher210_functional_test');

        if (\method_exists($treeBuilder, 'getRootNode')) {
            $rootNode = $treeBuilder->getRootNode();
        } else {
            // BC layer for symfony/config 4.1 and older
            $rootNode = $treeBuilder->root('speicher210_functional_test', 'array');
        }

        $rootNode
            ->children()
                ->scalarNode('fixture_loader_extend_class')
                    ->defaultValue(AbstractLoader::class)
                ->end()
            ->end();

        return $treeBuilder;
    }
}
