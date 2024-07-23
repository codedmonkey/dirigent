<?php

namespace CodedMonkey\Conductor\DependencyInjection;

use Symfony\Component\Config\Definition\ConfigurationInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\HttpKernel\DependencyInjection\ConfigurableExtension;

class ConductorExtension extends ConfigurableExtension
{
    protected function loadInternal(array $mergedConfig, ContainerBuilder $container): void
    {
        $container->setParameter('conductor.title', $mergedConfig['title']);

        $container->setParameter('conductor.security.public_access', $mergedConfig['security']['public']);
        $container->setParameter('conductor.security.registration_enabled', $mergedConfig['security']['registration']);

        $container->setParameter('conductor.storage.path', $mergedConfig['storage']['path']);

        $container->setParameter('conductor.packages.dynamic_updates', $mergedConfig['packages']['dynamic_updates']);
        $container->setParameter('conductor.packages.dynamic_update_delay', $mergedConfig['packages']['dynamic_update_delay']);
        $container->setParameter('conductor.packages.periodic_updates', $mergedConfig['packages']['periodic_updates']);
        $container->setParameter('conductor.packages.periodic_update_interval', $mergedConfig['packages']['periodic_update_interval']);

        $container->setParameter('conductor.dist_mirroring.enabled', $mergedConfig['dist_mirroring']['enabled']);
        $container->setParameter('conductor.dist_mirroring.preferred', $mergedConfig['dist_mirroring']['preferred']);
        $container->setParameter('conductor.dist_mirroring.dev_packages', $mergedConfig['dist_mirroring']['dev_packages']);
    }

    public function getConfiguration(array $config, ContainerBuilder $container): ConfigurationInterface
    {
        return new ConductorConfiguration();
    }
}
