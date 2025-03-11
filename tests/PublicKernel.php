<?php

namespace CodedMonkey\Dirigent\Tests;

use CodedMonkey\Dirigent\Kernel;
use Symfony\Component\DependencyInjection\Loader\Configurator\ContainerConfigurator;

class PublicKernel extends Kernel
{
    protected function configureContainer(ContainerConfigurator $container): void
    {
        parent::configureContainer($container);

        $container->extension('dirigent', [
            'security' => [
                'public' => true,
                'registration' => true,
            ],
        ]);
    }
}
