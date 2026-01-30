<?php

namespace CodedMonkey\Dirigent;

use CodedMonkey\Dirigent\DependencyInjection\Compiler\EncryptionPass;
use CodedMonkey\Dirigent\DependencyInjection\Compiler\ParametersPass;
use CodedMonkey\Dirigent\DependencyInjection\DirigentExtension;
use Symfony\Bundle\FrameworkBundle\Kernel\MicroKernelTrait;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Loader\Configurator\ContainerConfigurator;
use Symfony\Component\HttpKernel\Kernel as BaseKernel;

class Kernel extends BaseKernel
{
    use MicroKernelTrait;

    public const VERSION = '0.6.x-dev';

    protected function configureContainer(ContainerConfigurator $container): void
    {
        $configDir = $this->getConfigDir();

        $container->import($configDir . '/packages/*.yaml');
        $container->import($configDir . '/services.yaml');
        $container->import($configDir . '/dirigent.{json,php,yml,yaml}');

        if (isset($_SERVER['DIRIGENT_IMAGE'])) {
            $container->import('/srv/config/*.{json,php,yml,yaml}');
        }
    }

    protected function build(ContainerBuilder $container): void
    {
        $container->registerExtension(new DirigentExtension());

        $container->addCompilerPass(new EncryptionPass(), priority: 2048); // The encryption pass has to be the first pass to run as it removes sensitive data from the container
        $container->addCompilerPass(new ParametersPass());
    }

    public function boot(): void
    {
        parent::boot();

        // Set Composer env vars
        $_SERVER['COMPOSER_CACHE_DIR'] = $this->container->getParameter('dirigent.storage.path') . '/composer-cache';
        $_SERVER['COMPOSER_HOME'] = $this->container->getParameter('dirigent.storage.path') . '/composer';
    }

    #[\Override]
    public function getCacheDir(): string
    {
        return sprintf('%s/var/cache/symfony/%s', $this->getProjectDir(), $this->environment);
    }
}
