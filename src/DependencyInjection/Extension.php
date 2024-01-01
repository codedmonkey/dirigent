<?php

namespace CodedMonkey\Conductor\DependencyInjection;

use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\HttpKernel\DependencyInjection\ConfigurableExtension;

class Extension extends ConfigurableExtension
{
    public function getAlias(): string
    {
        return 'conductor';
    }

    protected function loadInternal(array $mergedConfig, ContainerBuilder $container): void
    {
        $container->setParameter('conductor.repositories', $mergedConfig['repositories']);
        $container->setParameter('conductor.storage.path', $mergedConfig['storage']['path']);
        $container->setParameter('conductor.title', $mergedConfig['title']);
    }
}
