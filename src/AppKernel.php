<?php

namespace CodedMonkey\Conductor;

use CodedMonkey\Conductor\DependencyInjection\AppExtension;
use Symfony\Bundle\FrameworkBundle\Kernel\MicroKernelTrait;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Loader\Configurator\ContainerConfigurator;
use Symfony\Component\HttpKernel\Kernel as BaseKernel;

class AppKernel extends BaseKernel
{
    use MicroKernelTrait;

    protected function configureContainer(ContainerConfigurator $container): void
    {
        $configDir = $this->getConfigDir();

        $container->import($configDir . '/conductor.{json,php,yaml}');
        $container->import($configDir . '/packages/*.yaml');
        $container->import($configDir . '/services.yaml');

        if (isset($_SERVER['CONDUCTOR_IMAGE'])) {
            $container->import('/srv/config/*.{json,php,yaml}');
        }
    }

    public function boot(): void
    {
        parent::boot();

        $_SERVER['COMPOSER_CACHE_DIR'] = $this->container->getParameter('conductor.storage.path') . '/composer-cache';
        $_SERVER['COMPOSER_HOME'] = $this->container->getParameter('conductor.storage.path') . '/composer';
    }

    protected function build(ContainerBuilder $container): void
    {
        $container->registerExtension(new AppExtension());
    }
}
