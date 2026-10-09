<?php

declare(strict_types=1);

namespace CodedMonkey\Dirigent\Tests\FunctionalTests;

use Symfony\Component\DependencyInjection\Loader\Configurator\ContainerConfigurator;

class DistributionMirroringKernel extends TestKernel
{
    #[\Override]
    protected function configureContainer(ContainerConfigurator $container): void
    {
        parent::configureContainer($container);

        $container->extension('dirigent', [
            'distributions' => [
                'mirror' => true,
            ],
        ]);
    }
}
