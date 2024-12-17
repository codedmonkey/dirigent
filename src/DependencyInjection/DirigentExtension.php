<?php

namespace CodedMonkey\Dirigent\DependencyInjection;

use Symfony\Component\Config\Definition\ConfigurationInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\HttpKernel\DependencyInjection\ConfigurableExtension;
use Symfony\Component\String\Slugger\AsciiSlugger;

class DirigentExtension extends ConfigurableExtension
{
    protected function loadInternal(array $mergedConfig, ContainerBuilder $container): void
    {
        if (null === $slug = $mergedConfig['slug']) {
            $slug = (new AsciiSlugger())->slug($mergedConfig['title']);
            $slug = strtolower($slug);
        }

        $container->setParameter('dirigent.title', $mergedConfig['title']);
        $container->setParameter('dirigent.slug', $slug);

        $container->setParameter('dirigent.security.public_access', $mergedConfig['security']['public']);
        $container->setParameter('dirigent.security.registration_enabled', $mergedConfig['security']['registration']);

        $container->setParameter('dirigent.storage.path', $mergedConfig['storage']['path']);

        $container->setParameter('dirigent.packages.dynamic_updates', $mergedConfig['packages']['dynamic_updates']);
        $container->setParameter('dirigent.packages.dynamic_update_delay', $mergedConfig['packages']['dynamic_update_delay']);
        $container->setParameter('dirigent.packages.periodic_updates', $mergedConfig['packages']['periodic_updates']);
        $container->setParameter('dirigent.packages.periodic_update_interval', $mergedConfig['packages']['periodic_update_interval']);

        $container->setParameter('dirigent.dist_mirroring.enabled', $mergedConfig['dist_mirroring']['enabled']);
        $container->setParameter('dirigent.dist_mirroring.preferred', $mergedConfig['dist_mirroring']['preferred']);
        $container->setParameter('dirigent.dist_mirroring.dev_packages', $mergedConfig['dist_mirroring']['dev_packages']);
    }

    public function getConfiguration(array $config, ContainerBuilder $container): ConfigurationInterface
    {
        return new DirigentConfiguration();
    }
}
