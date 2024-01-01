<?php

namespace CodedMonkey\Conductor\DependencyInjection;

use CodedMonkey\Conductor\Conductor;
use CodedMonkey\Conductor\Repository\RepositoryFactory;
use CodedMonkey\Conductor\Repository\RepositoryInterface;
use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Reference;
use Symfony\Contracts\HttpClient\HttpClientInterface;

class RepositoryPass implements CompilerPassInterface
{
    public function process(ContainerBuilder $container): void
    {
        $repositoryConfigs = $container->getParameter('conductor.repositories');
        $repositoryCollection = [];

        foreach ($repositoryConfigs as $repositoryName => $repositoryConfig) {
            $repository = $container->register("conductor.repository.$repositoryName", RepositoryInterface::class)
                ->setFactory([RepositoryFactory::class, 'create'])
                ->setArguments([
                    $repositoryConfig,
                    new Reference(HttpClientInterface::class),
                    '%conductor.storage.path%',
                ]);

            $repositoryCollection[$repositoryName] = $repository;
        }

        $container->getDefinition(Conductor::class)
            ->setArgument(0, $repositoryCollection);
    }
}
