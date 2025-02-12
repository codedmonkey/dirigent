<?php

namespace CodedMonkey\Dirigent\DependencyInjection;

use Symfony\Component\Config\Definition\ConfigurationInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Exception\LogicException;
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

        $container->setParameter('dirigent.dist_mirroring.enabled', $mergedConfig['dist_mirroring']['enabled']);
        $container->setParameter('dirigent.dist_mirroring.preferred', $mergedConfig['dist_mirroring']['preferred']);
        $container->setParameter('dirigent.dist_mirroring.dev_packages', $mergedConfig['dist_mirroring']['dev_packages']);
    }

    public function getConfiguration(array $config, ContainerBuilder $container): ConfigurationInterface
    {
        return new DirigentConfiguration();
    }

    private function registerEncryptionConfiguration(array $config, ContainerBuilder $container): void
    {
        $privateKey = $config['private_key'];
        $publicKey = $config['public_key'];
        $rotatedKeys = $config['rotated_keys'];

        $privateKeyPath = $config['private_key_path'];
        $publicKeyPath = $config['public_key_path'];
        $rotatedKeyPaths = $config['rotated_key_paths'];

        // If the private or public key are not directly defined in the configuration (advisably
        // through environment variables), use the path options instead.
        $useFiles = !$privateKey && !$publicKey;

        if ($useFiles) {
            if ($privateKey || $publicKey || count($rotatedKeys)) {
                throw new LogicException('Unable to load encryption from configuration, missing the private or public key.');
            }

            if (!$privateKeyPath || !$publicKeyPath) {
                throw new LogicException('Unable to load encryption from paths, missing the private or public key path.');
            }
        }

        $container->setParameter('dirigent.encryption.private_key', $privateKey);
        $container->setParameter('dirigent.encryption.public_key', $publicKey);
        $container->setParameter('dirigent.encryption.rotated_keys', $rotatedKeys);

        $container->setParameter('dirigent.encryption.private_key_path', $privateKeyPath);
        $container->setParameter('dirigent.encryption.public_key_path', $publicKeyPath);
        $container->setParameter('dirigent.encryption.rotated_key_paths', $rotatedKeyPaths);
    }
}
