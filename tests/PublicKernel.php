<?php

namespace CodedMonkey\Conductor\Tests;

use CodedMonkey\Conductor\Kernel;
use Symfony\Component\DependencyInjection\Loader\Configurator\ContainerConfigurator;

class PublicKernel extends Kernel
{
    protected function configureContainer(ContainerConfigurator $container): void
    {
        parent::configureContainer($container);

        $container->import(__DIR__ . '/config/public/*.yaml');
    }
}
