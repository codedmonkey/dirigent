<?php

declare(strict_types=1);

namespace CodedMonkey\Dirigent\Tests\FunctionalTests;

use Symfony\Component\DependencyInjection\Loader\Configurator\ContainerConfigurator;

class PublicKernel extends TestKernel
{
    #[\Override]
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
