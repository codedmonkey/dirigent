<?php

namespace CodedMonkey\Conductor;

use CodedMonkey\Conductor\Repository\RepositoryInterface;
use Composer\MetadataMinifier\MetadataMinifier;
use Composer\Package\AliasPackage;
use Composer\Package\Dumper\ArrayDumper;
use Composer\Package\Loader\ArrayLoader;
use Composer\Package\PackageInterface;
use Composer\Package\Version\VersionParser;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Contracts\HttpClient\HttpClientInterface;

class Conductor
{
    use CacheTrait;

    protected readonly string $cachePath;
    private readonly string $distributionPath;
    private readonly string $providerPath;

    public function __construct(
        /** @var RepositoryInterface[] */
        private readonly array $repositories,
        private readonly HttpClientInterface $httpClient,
        private readonly UrlGeneratorInterface $urlGenerator,
        #[Autowire(param: 'conductor.storage.path')]
        string $storagePath,
    ) {
        $this->cachePath = "{$storagePath}/metadata";
        $this->distributionPath = "{$storagePath}/dist";
        $this->providerPath = "{$storagePath}/provider";
    }

    public function resolvePackageMetadata(string $packageName): void
    {
        $path = "{$packageName}.json";

        $data = $this->readFromCache($path);
        $cacheMetadata = $data['_metadata'] ?? null;

        $requestedAt = (new \DateTimeImmutable())->setTimezone(new \DateTimeZone('UTC'));

        if (null !== $cacheMetadata) {
            $lastRequestedAt = \DateTimeImmutable::createFromFormat(\DateTimeInterface::RFC7231, $cacheMetadata['last-requested'], new \DateTimeZone('UTC'));
            $secondsSinceRequest = $requestedAt->getTimestamp() - $lastRequestedAt->getTimestamp();

            if ($secondsSinceRequest < 300) {
                return;
            }

            unset($data['_metadata']);
        } else {
            $data = [
                'aliases' => [],
                'versions' => [],
            ];
        }

        $dumper = new ArrayDumper();

        foreach ($this->repositories as $repositoryName => $repository) {
            if (!$packages = $repository->fetchPackageMetadata($packageName)) {
                continue;
            }

            $data['aliases'] = [];
            $data['versions'] = [];

            foreach ($packages as $package) {
                $distPackage = $package instanceof AliasPackage ? $package->getAliasOf() : $package;

                $packageDump = $dumper->dump($distPackage);
                $packageDump['_provider'] = $repositoryName;

                $data['versions'][$distPackage->getVersion()] = $packageDump;

                if ($package !== $distPackage) {
                    $data['aliases'][$package->getVersion()] = [
                        'version' => $distPackage->getVersion(),
                        '_provider' => $repositoryName,
                    ];
                }
            }

            break;
        }

        $found = true;

        if (!$packages ?? false) {
            $found = false;
        }

        $cacheMetadata = [
            'found' => $found,
            'last-requested' => $requestedAt->format(\DateTimeInterface::RFC7231),
        ];

        $this->writeToCache($path, $data, $cacheMetadata);

        $packages = (new ArrayLoader())->loadPackages(array_values($data['versions']));

        $releasePackages = [];
        $devPackages = [];

        foreach ($packages as $package) {
            if (!$package->isDev()) {
                $releasePackages[] = $package;
            } else {
                $devPackages[] = $package;
            }
        }

        $this->writeProvider($packageName, $releasePackages);
        $this->writeProvider($packageName, $devPackages, true);
    }

    public function getProviderPath(string $packageName): string
    {
        return "{$this->providerPath}/{$packageName}.json";
    }

    public function resolvePackageDistribution(string $packageName, string $version): void
    {
        $this->resolvePackageMetadata($packageName);

        if (!$data = $this->readFromCache("{$packageName}.json")) {
            return;
        }

        $normalizedVersion = (new VersionParser())->normalize($version);
        if ($alias = $data['aliases'][$normalizedVersion] ?? null) {
            $normalizedVersion = $alias['version'];
        }

        if (!$packageData = $data['versions'][$normalizedVersion] ?? null) {
            return;
        }

        $package = (new ArrayLoader())->load($packageData);
        if ($package instanceof AliasPackage) {
            $package = $package->getAliasOf();
        }

        $repository = $this->repositories[$packageData['_provider']];
        $repository->fetchPackageDistribution($package);
    }

    public function getDistributionPath(string $packageName, string $version, string $reference, string $type): string
    {
        return "{$this->distributionPath}/{$packageName}/{$version}-{$reference}.{$type}";
    }

    /**
     * @param PackageInterface[] $packages
     */
    private function writeProvider(string $packageName, array $packages, bool $dev = false): void
    {
        if (!count($packages)) {
            return;
        }

        $packagesData = array_map([new ArrayDumper(), 'dump'], $packages);

        $path = !$dev ? $this->getProviderPath($packageName) : $this->getProviderPath("{$packageName}~dev");
        $data = [
            'minified' => 'composer/2.0',
            'packages' => [
                $packageName => MetadataMinifier::minify($packagesData),
            ],
        ];

        (new Filesystem())->mkdir(dirname($path));

        file_put_contents($path, json_encode($data, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE));
    }
}
