<?php

namespace CodedMonkey\Dirigent\DependencyInjection;

use CodedMonkey\Dirigent\Doctrine\Entity\PackageFetchStrategy;
use Symfony\Component\Config\Definition\ConfigurationInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\HttpKernel\DependencyInjection\ConfigurableExtension;
use Symfony\Component\String\Slugger\AsciiSlugger;

class DirigentExtension extends ConfigurableExtension
{
    protected function loadInternal(array $mergedConfig, ContainerBuilder $container): void
    {
        $slug = $mergedConfig['slug'];
        $slug ??= (new AsciiSlugger())->slug($mergedConfig['title'])->lower()->toString();

        $container->setParameter('dirigent.title', $mergedConfig['title']);
        $container->setParameter('dirigent.slug', $slug);

        $this->registerDistributionsConfiguration($mergedConfig['distributions'], $container);
        $this->registerEncryptionConfiguration($mergedConfig['encryption'], $container);
        $this->registerMetadataConfiguration($mergedConfig['metadata'], $container);
        $this->registerPackagesConfiguration($mergedConfig['packages'], $container);

        $container->setParameter('dirigent.security.public_access', $mergedConfig['security']['public']);
        $container->setParameter('dirigent.security.registration_enabled', $mergedConfig['security']['registration']);

        if (isset($_SERVER['DIRIGENT_IMAGE'])) {
            $container->setParameter('dirigent.storage.path', '/srv/data');
        } else {
            $container->setParameter('dirigent.storage.path', $mergedConfig['storage']['path']);
        }
    }

    public function getConfiguration(array $config, ContainerBuilder $container): ConfigurationInterface
    {
        return new DirigentConfiguration();
    }

    private function registerDistributionsConfiguration(array $config, ContainerBuilder $container): void
    {
        $distributionsEnabled = $config['enabled'];

        $container->setParameter('dirigent.distributions.enabled', $distributionsEnabled);
        $container->setParameter('dirigent.distributions.build', $distributionsEnabled && $config['build']);
        $container->setParameter('dirigent.distributions.mirror', $distributionsEnabled && $config['mirror']);

        $container->setParameter('dirigent.distributions.async_api_requests', $config['async_api_requests']);
        $container->setParameter('dirigent.distributions.dev_versions', $config['dev_versions']);
        $container->setParameter('dirigent.distributions.preferred_mirror', $config['preferred_mirror']);
    }

    /**
     * @param array{private_key: ?string, private_key_path: ?string, public_key: ?string, public_key_path: ?string, rotated_keys: array<string>, rotated_key_paths: array<string>} $config
     */
    private function registerEncryptionConfiguration(array $config, ContainerBuilder $container): void
    {
        $container->setParameter('dirigent.encryption.private_key', $config['private_key']);
        $container->setParameter('dirigent.encryption.public_key', $config['public_key']);
        $container->setParameter('dirigent.encryption.rotated_keys', $config['rotated_keys']);

        $container->setParameter('dirigent.encryption.private_key_path', $config['private_key_path']);
        $container->setParameter('dirigent.encryption.public_key_path', $config['public_key_path']);
        $container->setParameter('dirigent.encryption.rotated_key_paths', $config['rotated_key_paths']);
    }

    /**
     * @param array{default_fetch_strategy: PackageFetchStrategy} $config
     */
    private function registerMetadataConfiguration(array $config, ContainerBuilder $container): void
    {
        $container->setParameter('dirigent.metadata.default_fetch_strategy', $config['default_fetch_strategy']);
    }

    /**
     * @param array{periodic_update_interval: string, periodic_updates: bool, dynamic_update_delay: string, dynamic_updates: bool} $config
     */
    private function registerPackagesConfiguration(array $config, ContainerBuilder $container): void
    {
        $dynamicUpdatesEnabled = $config['dynamic_updates'];
        $dynamicUpdateDelay = $dynamicUpdatesEnabled ? $config['dynamic_update_delay'] : null;
        $periodicUpdatesEnabled = $config['periodic_updates'];
        $periodicUpdateInterval = $periodicUpdatesEnabled ? $config['periodic_update_interval'] : null;

        if (null !== $dynamicUpdateDelay) {
            try {
                new \DateInterval($dynamicUpdateDelay);
            } catch (\DateMalformedIntervalStringException) {
                throw new \LogicException("Invalid dynamic update delay: '$dynamicUpdateDelay' is not a valid ISO 8601 duration.");
            }
        }

        if (null !== $periodicUpdateInterval) {
            try {
                new \DateInterval($periodicUpdateInterval);
            } catch (\DateMalformedIntervalStringException) {
                throw new \LogicException("Invalid periodic update interval: '$periodicUpdateInterval' is not a valid ISO 8601 duration.");
            }
        }

        $container->setParameter('dirigent.packages.dynamic_updates', $dynamicUpdatesEnabled);
        $container->setParameter('dirigent.packages.dynamic_update_delay', $dynamicUpdateDelay);
        $container->setParameter('dirigent.packages.periodic_updates', $periodicUpdatesEnabled);
        $container->setParameter('dirigent.packages.periodic_update_interval', $periodicUpdateInterval);
    }
}
