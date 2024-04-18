<?php

namespace CodedMonkey\Conductor\Package;

use CodedMonkey\Conductor\Doctrine\Entity\Package;
use CodedMonkey\Conductor\Registry\RegistryClientManager;
use CodedMonkey\Conductor\Registry\RegistryResolveStatus;
use Composer\Package\AliasPackage;
use Composer\Package\Loader\ArrayLoader;
use Composer\Package\Version\VersionParser;
use Symfony\Component\DependencyInjection\Attribute\Autowire;

class PackageDistributionResolver
{
    private readonly string $storagePath;

    public function __construct(
        private readonly RegistryClientManager $registryClientManager,
        private readonly PackageMetadataResolver $metadataResolver,
        #[Autowire(param: 'conductor.storage.path')]
        string $storagePath,
    ) {
        $this->storagePath = "$storagePath/distribution";
    }

    public function exists(string $packageName, string $version, string $reference, string $type): bool
    {
        return file_exists($this->path($packageName, $version, $reference, $type));
    }

    public function path(string $packageName, string $version, string $reference, string $type): string
    {
        return "{$this->storagePath}/{$packageName}/{$version}-{$reference}.{$type}";
    }

    public function resolve(Package $package, string $version, string $reference, string $type): bool
    {
        if ($this->exists($package->name, $version, $reference, $type)) {
            return true;
        }

        if (null !== $package->mirrorRegistry) {
            return $this->resolveFromRegistry($package, $version, $reference, $type);
        } else {
            // todo resolve from other sources
            throw new \LogicException();
        }
    }

    private function resolveFromRegistry(Package $package, string $version, string $reference, string $type): bool
    {
        $registryClient = $this->registryClientManager->getClient($package->mirrorRegistry);

        if (!$registryClient->packageExists($package->name)) {
            return false;
        }

        $metadata = $this->metadataResolver->resolve($package);

        $normalizedVersion = (new VersionParser())->normalize($version);
        if ($alias = $metadata['aliases'][$normalizedVersion] ?? null) {
            $normalizedVersion = $alias['version'];
        }

        if (!$composerPackageData = $metadata['versions'][$normalizedVersion] ?? null) {
            return false;
        }

        $composerPackage = (new ArrayLoader())->load($composerPackageData);
        if ($composerPackage instanceof AliasPackage) {
            $composerPackage = $composerPackage->getAliasOf();
        }

        if ($reference !== $composerPackage->getDistReference() || $type !== $composerPackage->getDistType()) {
            return false;
        }

        $path = $this->path($composerPackage->getName(), $composerPackage->getVersion(), $composerPackage->getDistReference(), $composerPackage->getDistType());
        $resolved = $registryClient->resolvePackageDistribution($composerPackage, $path);

        return $resolved === RegistryResolveStatus::Modified;
    }
}
