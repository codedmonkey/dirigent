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

        $this->registerDistributionsConfiguration($mergedConfig['distributions'], $container);
        $this->registerEncryptionConfiguration($mergedConfig['encryption'], $container);

        $container->setParameter('dirigent.security.public_access', $mergedConfig['security']['public']);
        $container->setParameter('dirigent.security.registration_enabled', $mergedConfig['security']['registration']);

        if (isset($_SERVER['DIRIGENT_IMAGE'])) {
            $container->setParameter('dirigent.storage.path', '/srv/data');
        } else {
            $container->setParameter('dirigent.storage.path', $mergedConfig['storage']['path']);
        }

        $container->setParameter('dirigent.packages.dynamic_updates', $mergedConfig['packages']['dynamic_updates']);
        $container->setParameter('dirigent.packages.dynamic_update_delay', $mergedConfig['packages']['dynamic_update_delay']);
        $container->setParameter('dirigent.packages.periodic_updates', $mergedConfig['packages']['periodic_updates']);
        $container->setParameter('dirigent.packages.periodic_update_interval', $mergedConfig['packages']['periodic_update_interval']);
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
}
