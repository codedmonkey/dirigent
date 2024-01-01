<?php

namespace CodedMonkey\Conductor\Repository;

use Symfony\Contracts\HttpClient\HttpClientInterface;

class RepositoryFactory
{
    private static array $repositoryClasses = [
        'composer' => ComposerRepository::class,
    ];

    public static function create(array $config, HttpClientInterface $httpClient, string $storagePath): RepositoryInterface
    {
        $type = $config['type'];

        if ('composer' === $type) {
            return new ComposerRepository($config, $httpClient, $storagePath);
        }

        throw new \RuntimeException("Invalid repository type \"$type\".");
    }
}
