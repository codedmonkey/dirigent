<?php

namespace CodedMonkey\Conductor\Registry;

use CodedMonkey\Conductor\Doctrine\Entity\Registry;
use Composer\Pcre\Preg;
use Composer\Util\Url;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Contracts\HttpClient\HttpClientInterface;

class RegistryClientManager
{
    private array $clients = [];

    public function __construct(
        private readonly HttpClientInterface $httpClient,
        #[Autowire(param: 'conductor.storage.path')]
        private readonly string $storagePath,
    ) {
    }

    public function getClient(Registry $registry): RegistryClientInterface
    {
        if (!isset($this->clients[$registry->id])) {
            $this->clients[$registry->id] = $this->createClient($registry);
        }

        return $this->clients[$registry->id];
    }

    private function createClient(Registry $registry): RegistryClientInterface
    {
        $sanitizedRepositoryUrl = Preg::replace('{[^a-z0-9.]}i', '-', Url::sanitize($registry->url));
        $sanitizedRepositoryUrl = trim($sanitizedRepositoryUrl, '-');

        $storage = new RegistryCachePool("{$this->storagePath}/registry-cache/{$sanitizedRepositoryUrl}");

        return new ComposerRegistryClient($registry, $storage, $this->httpClient);
    }
}
