<?php

namespace CodedMonkey\Conductor\Registry;

use CodedMonkey\Conductor\Doctrine\Entity\CredentialsType;
use CodedMonkey\Conductor\Doctrine\Entity\Registry;
use Composer\MetadataMinifier\MetadataMinifier;
use Composer\Package\Loader\ArrayLoader;
use Composer\Package\PackageInterface;
use Composer\Package\PackageInterface as ComposerPackage;
use Composer\Pcre\Preg;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Contracts\HttpClient\HttpClientInterface;

class ComposerRegistryClient implements RegistryClientInterface
{
    private readonly array $rootData;
    private readonly string $packageProviderUrl;
    private readonly string $url;

    public function __construct(
        private readonly Registry $registry,
        private readonly RegistryCachePool $cache,
        private readonly HttpClientInterface $httpClient,
    ) {
        $this->url = $this->registry->url;
    }

    public function packageExists(string $packageName): bool
    {
        return RegistryResolveStatus::NotFound !== $this->resolvePackageMetadata($packageName);
    }

    public function resolvePackageMetadata(string $packageName): RegistryResolveStatus
    {
        $this->loadRootConfiguration();

        $releaseProviderUrl = str_replace('%package%', $packageName, $this->packageProviderUrl);
        $releaseProviderCacheItem = $this->read("p2/{$packageName}", $releaseProviderUrl);

        $devProviderUrl = str_replace('%package%', "{$packageName}~dev", $this->packageProviderUrl);
        $devProviderCacheItem = $this->read("p2/{$packageName}~dev", $devProviderUrl);

        if (!$releaseProviderCacheItem->isFound() && !$devProviderCacheItem->isFound()) {
            return RegistryResolveStatus::NotFound;
        }

        if ($releaseProviderCacheItem->isDegraded() || $devProviderCacheItem->isDegraded()) {
            return RegistryResolveStatus::Degraded;
        }

        if (!$releaseProviderCacheItem->isFresh() || !$devProviderCacheItem->isFresh()) {
            return RegistryResolveStatus::Modified;
        }

        return RegistryResolveStatus::Fresh;
    }

    public function resolvePackageDistribution(ComposerPackage $composerPackage, string $path): RegistryResolveStatus
    {
        if ($this->download($composerPackage->getDistUrl(), $path)) {
            return RegistryResolveStatus::Modified;
        }

        return RegistryResolveStatus::NotFound;
    }

    public function getComposerPackages(string $packageName): ?array
    {
        $this->resolvePackageMetadata($packageName);

        $releaseProviderData = $this->cache->read("p2/{$packageName}")->content;

        $releasePackagesData = $releaseProviderData['packages'][$packageName] ?? [];
        if ('composer/2.0' === ($releaseProviderData['minified'] ?? null)) {
            $releasePackagesData = MetadataMinifier::expand($releasePackagesData);
        }

        $devProviderData = $this->cache->read("p2/{$packageName}~dev")->content;

        $devPackagesData = $devProviderData['packages'][$packageName] ?? [];
        if ('composer/2.0' === ($devProviderData['minified'] ?? null)) {
            $devPackagesData = MetadataMinifier::expand($devPackagesData);
        }

        $packagesData = [...$releasePackagesData, ...$devPackagesData];

        if (!count($packagesData)) {
            return null;
        }

        return (new ArrayLoader())->loadPackages($packagesData);
    }

    private function loadRootConfiguration(): void
    {
        if (isset($this->rootData)) {
            return;
        }

        $rootMetadata = $this->read('packages', $this->getRootConfigurationUrl());

        $this->rootData = $rootMetadata->content;
        $this->packageProviderUrl = $this->canonicalizeUrl($this->rootData['metadata-url']);
    }

    private function getRootConfigurationUrl(): string
    {
        $urlParts = parse_url(strtr($this->url, '\\', '/'));

        if (isset($urlParts['path']) && str_ends_with($urlParts['path'], '.json')) {
            return $this->url;
        }

        return "{$this->url}/packages.json";
    }

    /**
     * @param non-empty-string $url
     * @return non-empty-string
     */
    private function canonicalizeUrl(string $url): string
    {
        if ('/' === $url[0]) {
            if (Preg::isMatch('{^[^:]++://[^/]*+}', $this->url, $matches)) {
                return $matches[0] . $url;
            }

            return $this->url;
        }

        return $url;
    }

    private function read(string $key, string $url): RegistryCacheItem
    {
        $cacheItem = $this->cache->read($key);

        $resolvedAt = (new \DateTimeImmutable())->setTimezone(new \DateTimeZone('UTC'));

        if (null !== $lastResolvedAt = $cacheItem->lastResolvedAt()) {
            $interval = $resolvedAt->getTimestamp() - $lastResolvedAt->getTimestamp();
            $delay = 3600;

            if ($interval < $delay) {
                return $cacheItem;
            }
        }

        $requestHeaders = [];
        $requestOptions = [];

        if (null !== $cacheItem->lastModified) {
            $requestHeaders['If-Modified-Since'] = [$cacheItem->lastModified];
        }

        $this->prepareRequestOptions($requestOptions, $requestHeaders);

        try {
            $degraded = false;
            $found = $cacheItem->found;
            $fresh = false;

            $response = $this->httpClient->request('GET', $url, $requestOptions);
            $statusCode = $response->getStatusCode();

            if ($statusCode === 304) {
                $found = true;
                $fresh = true;
            } else if ($statusCode === 404) {
                $found = false;
            } else if ($statusCode === 200) {
                $found = true;
            } else {
                $degraded = true;
            }
        } catch (\Exception $exception) {
            dump($exception);

            $degraded = true;
        }

        $cacheItem->degraded = $degraded;
        $cacheItem->found = $found;
        $cacheItem->fresh = $fresh;
        $cacheItem->lastResolved = $resolvedAt->format(\DateTimeInterface::RFC7231);

        if (($degraded && !$cacheItem->isResolved()) || !$found) {
            $cacheItem->found = false;
        } else if (!$degraded && !$fresh) {
            $cacheItem->content = json_decode($response->getContent(), true);

            if ($lastModified = $response->getHeaders()['last-modified'][0] ?? null) {
                $cacheItem->lastModified = $lastModified;
            }
        }

        $this->cache->write($cacheItem);

        return $cacheItem;
    }

    private function download(string $url, string $path): bool
    {
        $requestHeaders = [];
        $requestOptions = [];

        $this->prepareRequestOptions($requestOptions, $requestHeaders);

        $response = $this->httpClient->request('GET', $url, $requestOptions);

        if ($response->getStatusCode() > 200) {
            return false;
        }

        (new Filesystem())->mkdir(dirname($path));

        $fileHandler = fopen($path, 'w');
        foreach ($this->httpClient->stream($response) as $chunk) {
            fwrite($fileHandler, $chunk->getContent());
        }

        return true;
    }

    private function prepareRequestOptions(array &$options, array &$headers): void
    {
        if ($credentials = $this->registry->credentials) {
            if ($credentials->type === CredentialsType::HttpBasic) {
                $options['auth_basic'] = [$credentials->username, $credentials->password];
            }
        }

        $options['headers'] = $headers;
    }
}
