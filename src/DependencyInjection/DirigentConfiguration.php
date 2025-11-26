<?php

namespace CodedMonkey\Dirigent\DependencyInjection;

use Symfony\Component\Config\Definition\Builder\ArrayNodeDefinition;
use Symfony\Component\Config\Definition\Builder\NodeDefinition;
use Symfony\Component\Config\Definition\Builder\TreeBuilder;
use Symfony\Component\Config\Definition\ConfigurationInterface;
use function Symfony\Component\String\u;

class DirigentConfiguration implements ConfigurationInterface
{
    public function getConfigTreeBuilder(): TreeBuilder
    {
        $treeBuilder = new TreeBuilder('dirigent');
        $rootNode = $treeBuilder->getRootNode();

        $rootNode->children()
            ->scalarNode('title')->defaultValue('My Dirigent')->end()
            ->scalarNode('slug')->defaultNull()->end()
            ->arrayNode('security')
                ->addDefaultsIfNotSet()
                ->children()
                    ->booleanNode('public')->defaultFalse()->end()
                    ->booleanNode('registration')->defaultFalse()->end()
                ->end()
            ->end()
            ->arrayNode('encryption')
                ->info('Dirigent uses a X25519 keypair to encrypt sensitive info stored in the database')
                ->addDefaultsIfNotSet()
                ->children()
                    ->scalarNode('private_key')
                        ->defaultNull()
                        ->info('The (private) decryption key, if empty, a file will be used to store the key instead')
                    ->end()
                    ->scalarNode('private_key_path')
                        ->defaultValue('%kernel.project_dir%/config/encryption/private.key')
                        ->info('Path to the (private) decryption key if private_key is empty')
                    ->end()
                    ->scalarNode('public_key')
                        ->defaultNull()
                        ->info('The (public) encryption key, if empty, a file will be used to store the key instead')
                    ->end()
                    ->scalarNode('public_key_path')
                        ->defaultValue('%kernel.project_dir%/config/encryption/public.key')
                        ->info('Path to the (public) encryption key if public_key is empty')
                    ->end()
                    ->arrayNode('rotated_keys')
                        ->info('Previously used (private) decryption keys')
                        ->beforeNormalization()
                            ->ifString()
                            ->then(fn (string $keys): array => u($$keys)->split(','))
                        ->end()
                        ->prototype('scalar')->end()
                    ->end()
                    ->arrayNode('rotated_key_paths')
                        ->info('Paths to previously used (private) decryption keys')
                        ->beforeNormalization()
                            ->ifString()
                            ->then(fn (string $paths): array => u($$paths)->split(','))
                        ->end()
                        ->prototype('scalar')->end()
                    ->end()
                ->end()
            ->end()
            ->arrayNode('storage')
                ->addDefaultsIfNotSet()
                ->children()
                    ->scalarNode('path')->defaultValue('%kernel.project_dir%/storage')->end()
                ->end()
            ->end()
            ->arrayNode('dist_mirroring')
                ->canBeEnabled()
                ->children()
                    ->booleanNode('preferred')->defaultTrue()->end()
                    ->booleanNode('dev_packages')->defaultFalse()->end()
                ->end()
            ->end();

        $this->addMetadataSection($rootNode);
        $this->addPackagesSection($rootNode);

        return $treeBuilder;
    }

    private function addMetadataSection(ArrayNodeDefinition|NodeDefinition $rootNode): void
    {
        $rootNode->children()
            ->arrayNode('metadata')
                ->addDefaultsIfNotSet()
                ->children()
                    ->booleanNode('mirror_vcs_repositories')
                        ->defaultFalse()
                        ->info('Fetch mirrored packages from their VCS repositories by default when possible.')
                    ->end()
                ->end()
            ->end();
    }

    private function addPackagesSection(ArrayNodeDefinition|NodeDefinition $rootNode): void
    {
        $rootNode->children()
            ->arrayNode('packages')
                ->addDefaultsIfNotSet()
                ->children()
                    ->booleanNode('dynamic_updates')
                        ->defaultTrue()
                        ->info('Whether to automatically update packages when using the (Composer) API')
                    ->end()
                    ->stringNode('dynamic_update_delay')
                        ->cannotBeEmpty()
                        ->defaultValue('PT4H')
                        ->info('The delay between package updates when using the API, in ISO 8601 duration format')
                    ->end()
                    ->booleanNode('periodic_updates')
                        ->defaultTrue()
                        ->info('Whether to automatically update packages periodically')
                    ->end()
                    ->stringNode('periodic_update_interval')
                        ->cannotBeEmpty()
                        ->defaultValue('P1W')
                        ->info('The interval between periodic package updates, in ISO 8601 duration format')
                    ->end()
                ->end()
            ->end();
    }
}
