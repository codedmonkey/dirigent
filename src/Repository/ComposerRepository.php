<?php

namespace CodedMonkey\Conductor\Repository;

use CodedMonkey\Conductor\CacheTrait;
use Composer\MetadataMinifier\MetadataMinifier;
use Composer\Package\Loader\ArrayLoader;
use Composer\Package\PackageInterface;
use Composer\Pcre\Preg;
use Composer\Util\Url;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Contracts\HttpClient\HttpClientInterface;

class ComposerRepository implements RepositoryInterface
{
    use CacheTrait;

    protected readonly string $cachePath;
    private readonly string $distributionPath;

    private ?array $rootData = null;
    private ?string $packageProviderUrl = null;
    private readonly string $url;

    public function __construct(
        private readonly array $config,
        private readonly HttpClientInterface $httpClient,
        string $storagePath,
    ) {
        $this->url = $this->config['url'];

        $sanitizedRepositoryUrl = Preg::replace('{[^a-z0-9.]}i', '-', Url::sanitize($this->url));
        $sanitizedRepositoryUrl = trim($sanitizedRepositoryUrl, '-');
        $this->cachePath = "{$storagePath}/repo/{$sanitizedRepositoryUrl}";
        $this->distributionPath = "{$storagePath}/dist";
    }

    public function fetchPackageMetadata(string $name): ?array
    {
        $this->loadRootServerFile();

        $releaseProviderUrl = str_replace('%package%', $name, $this->packageProviderUrl);
        $releaseProviderData = $this->read("p2/{$name}.json", $releaseProviderUrl);

        $releasePackagesData = $releaseProviderData['packages'][$name] ?? [];
        if ('composer/2.0' === ($releaseProviderData['minified'] ?? null)) {
            $releasePackagesData = MetadataMinifier::expand($releasePackagesData);
        }

        $devProviderUrl = str_replace('%package%', "{$name}~dev", $this->packageProviderUrl);
        $devProviderData = $this->read("p2/{$name}~dev.json", $devProviderUrl);

        $devPackagesData = $devProviderData['packages'][$name] ?? [];
        if ('composer/2.0' === ($devProviderData['minified'] ?? null)) {
            $devPackagesData = MetadataMinifier::expand($devPackagesData);
        }

        $packagesData = [...$releasePackagesData, ...$devPackagesData];

        if (!count($packagesData)) {
            return null;
        }

        return (new ArrayLoader())->loadPackages($packagesData);
    }

    public function fetchPackageDistribution(PackageInterface $package): bool
    {
        $path = $this->getDistributionPath($package->getName(), $package->getVersion(), $package->getDistReference(), $package->getDistType());

        if (file_exists($path)) {
            return true;
        }

        $requestHeaders = [];
        $requestOptions = [];

        $this->prepareRequest($requestOptions, $requestHeaders);

        $url = $package->getDistUrl();
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

        return "{$this->url}/packages.json";
    }

    public function getDistributionPath(string $packageName, string $version, string $reference, string $type): string
    {
        return "{$this->distributionPath}/{$packageName}/{$version}-{$reference}.{$type}";
    }

    private function read(string $path, string $url): ?array
    {
        $cachedData = $this->readFromCache($path);
        $cacheMetadata = $cachedData['_metadata'] ?? null;

        $requestedAt = (new \DateTimeImmutable())->setTimezone(new \DateTimeZone('UTC'));
        $lastModified = null;

        if (null !== $cacheMetadata) {
            unset($cachedData['_metadata']);

            $lastRequestedAt = \DateTimeImmutable::createFromFormat(\DateTimeInterface::RFC7231, $cacheMetadata['last-requested'], new \DateTimeZone('UTC'));
            $secondsSinceRequest = $requestedAt->getTimestamp() - $lastRequestedAt->getTimestamp();

            if ($secondsSinceRequest < $this->config['delay']) {
                if (!$cacheMetadata['found']) {
                    return null;
                }

                return $cachedData;
            }

            $lastModified = $cacheMetadata['last-modified'];
        }

        $requestHeaders = [];
        $requestOptions = [];

        if ($lastModified) {
            $requestHeaders['If-Modified-Since'] = [$lastModified];
        }

        $this->prepareRequest($requestOptions, $requestHeaders);

        $response = $this->httpClient->request('GET', $url, $requestOptions);
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
            'last-modified' => $lastModified,
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

        if ($lastModified = $response->getHeaders()['last-modified'][0] ?? null) {
            $cacheMetadata['last-modified'] = $lastModified;
        }

        $this->writeToCache($path, $data, $cacheMetadata);

        return $data;
    }

    private function prepareRequest(array &$options, array &$headers): void
    {
        if ($authConfig = $this->config['auth'] ?? null) {
            $authType = $authConfig['type'] ?? null;

            if ('http_basic' === $authType) {
                $options['auth_basic'] = [$authConfig['username'], $authConfig['password']];
            }
        }

        $options['headers'] = $headers;
    }
}
