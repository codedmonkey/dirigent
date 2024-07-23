<?php

namespace CodedMonkey\Conductor\DependencyInjection;

use Symfony\Component\Config\Definition\Builder\TreeBuilder;
use Symfony\Component\Config\Definition\ConfigurationInterface;

class ConductorConfiguration implements ConfigurationInterface
{
    public function getConfigTreeBuilder(): TreeBuilder
    {
        $treeBuilder = new TreeBuilder('conductor');
        $rootNode = $treeBuilder->getRootNode();

        $rootNode->children()
            ->scalarNode('title')->defaultValue('My Conductor')->end()
            ->scalarNode('slug')->defaultNull()->end()
            ->arrayNode('security')
                ->addDefaultsIfNotSet()
                ->children()
                    ->booleanNode('public')->defaultFalse()->end()
                    ->booleanNode('registration')->defaultFalse()->end()
                ->end()
            ->end()
            ->arrayNode('storage')
                ->addDefaultsIfNotSet()
                ->children()
                    ->scalarNode('path')->defaultValue('%kernel.project_dir%/storage')->end()
                ->end()
            ->end()
            ->arrayNode('packages')
                ->addDefaultsIfNotSet()
                ->children()
                    ->booleanNode('dynamic_updates')->defaultTrue()->end()
                    ->scalarNode('dynamic_update_delay')->defaultValue('PT4H')->end()
                    ->booleanNode('periodic_updates')->defaultTrue()->end()
                    ->scalarNode('periodic_update_interval')->defaultValue('P1W')->end()
                ->end()
            ->end()
            ->arrayNode('dist_mirroring')
                ->canBeEnabled()
                ->children()
                    ->booleanNode('preferred')->defaultTrue()->end()
                    ->booleanNode('dev_packages')->defaultFalse()->end()
                ->end()
            ->end();

        return $treeBuilder;
    }
}
