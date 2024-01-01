<?php

namespace CodedMonkey\Conductor\Repository;

use CodedMonkey\Conductor\CacheTrait;
use Composer\MetadataMinifier\MetadataMinifier;
use Composer\Package\Loader\ArrayLoader;
use Composer\Pcre\Preg;
use Composer\Util\Url;
use Symfony\Contracts\HttpClient\HttpClientInterface;

class ComposerRepository implements RepositoryInterface
{
    use CacheTrait;

    protected readonly string $cachePath;
    private readonly string $url;

    private ?array $rootData = null;
    private ?string $packageProviderUrl = null;

    public function __construct(
        private readonly array $config,
        private readonly HttpClientInterface $httpClient,
        string $storagePath,
    ) {
        $this->url = $this->config['url'];

        $sanitizedRepositoryUrl = Preg::replace('{[^a-z0-9.]}i', '-', Url::sanitize($this->url));
        $sanitizedRepositoryUrl = trim($sanitizedRepositoryUrl, '-');
        $this->cachePath = "{$storagePath}/repo/{$sanitizedRepositoryUrl}";
    }

    public function fetchPackageMetadata(string $name): ?array
    {
        $this->loadRootServerFile();

        $releaseProviderUrl = str_replace('%package%', $name, $this->packageProviderUrl);
        $releaseProviderData = $this->read("p2/{$name}.json", $releaseProviderUrl);

        $releasePackagesData = $releaseProviderData['packages'][$name] ?? [];
        if ('composer/2.0' === $releaseProviderData['minified'] ?? null) {
            $releasePackagesData = MetadataMinifier::expand($releasePackagesData);
        }

        $devProviderUrl = str_replace('%package%', "{$name}~dev", $this->packageProviderUrl);
        $devProviderData = $this->read("p2/{$name}~dev.json", $devProviderUrl);

        $devPackagesData = $devProviderData['packages'][$name] ?? [];
        if ('composer/2.0' === $devProviderData['minified'] ?? null) {
            $devPackagesData = MetadataMinifier::expand($devPackagesData);
        }

        $packagesData = [...$releasePackagesData, ...$devPackagesData];

        if (!count($packagesData)) {
            return null;
        }

        return (new ArrayLoader())->loadPackages($packagesData);
    }

    private function loadRootServerFile(): void
    {
        if (null !== $this->rootData) {
            return;
        }

        $data = $this->read('packages.json', $this->getPackagesJsonUrl());

        $this->rootData = $data;
        $this->packageProviderUrl = $this->canonicalizeUrl($data['metadata-url']);
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

    private function getPackagesJsonUrl(): string
    {
        $jsonUrlParts = parse_url(strtr($this->url, '\\', '/'));

        if (isset($jsonUrlParts['path']) && str_ends_with($jsonUrlParts['path'], '.json')) {
            return $this->url;
        }

        return $this->url . '/packages.json';
    }

    private function read(string $path, string $url): ?array
    {
        $cachedData = $this->readFromCache($path);
        $cacheMetadata = $cachedData['_metadata'] ?? null;

        $requestedAt = (new \DateTimeImmutable())->setTimezone(new \DateTimeZone('UTC'));
        $lastModifiedAt = null;

        if (null !== $cacheMetadata) {
            unset($cachedData['_metadata']);

            $lastRequestedAt = \DateTimeImmutable::createFromFormat(\DateTimeInterface::RFC7231, $cacheMetadata['last-requested'], new \DateTimeZone('UTC'));
            $secondsSinceRequest = $requestedAt->getTimestamp() - $lastRequestedAt->getTimestamp();

            if ($secondsSinceRequest < 300) {
                if (!$cacheMetadata['found']) {
                    return null;
                }

                return $cachedData;
            }

            $lastModifiedAt = $cacheMetadata['last-modified'];
        }

        $requestHeaders = [];

        if ($lastModifiedAt) {
            $requestHeaders['If-Modified-Since'] = [$lastModifiedAt];
        }

        $response = $this->httpClient->request('GET', $url, ['headers' => $requestHeaders]);
        $statusCode = $response->getStatusCode();

        $degraded = false;
        $found = true;
        $isFresh = false;

        if ($statusCode === 304) {
            $isFresh = true;
        } else if ($statusCode === 404) {
            $found = false;
        } else if ($statusCode !== 200) {
            $degraded = true;
        }

        $cacheMetadata = [
            'degraded' => $degraded,
            'found' => $found,
            'last-modified' => $lastModifiedAt,
            'last-requested' => $requestedAt->format(\DateTimeInterface::RFC7231),
        ];

        if (!$found || ($degraded && !$cachedData)) {
            $data = [];

            $cacheMetadata['found'] = false;

            $this->writeToCache($path, $data, $cacheMetadata);

            return null;
        } else if ($degraded || $isFresh) {
            $this->writeToCache($path, $cachedData, $cacheMetadata);

            return $cachedData;
        }

        $data = json_decode($response->getContent(), true);

        $cacheMetadata['last-modified'] = $response->getHeaders()['last-modified'][0];

        $this->writeToCache($path, $data, $cacheMetadata);

        return $data;
    }
}
