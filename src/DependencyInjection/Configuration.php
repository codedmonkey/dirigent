<?php

namespace CodedMonkey\Conductor\DependencyInjection;

use Symfony\Component\Config\Definition\Builder\TreeBuilder;
use Symfony\Component\Config\Definition\ConfigurationInterface;

class Configuration implements ConfigurationInterface
{
    public function getConfigTreeBuilder(): TreeBuilder
    {
        $treeBuilder = new TreeBuilder('conductor');
        $rootNode = $treeBuilder->getRootNode();

        $rootNode->children()
            ->scalarNode('title')->defaultValue('My Conductor')->end()
            ->arrayNode('storage')
                ->addDefaultsIfNotSet()
                ->children()
                    ->scalarNode('path')->defaultValue('%kernel.project_dir%/storage')->end()
                ->end()
            ->end()
            ->arrayNode('repositories')
                ->setDeprecated('conductor', 'dev', 'Use database instead')
                ->ignoreExtraKeys(false)
            ->end();

        return $treeBuilder;
    }
}
