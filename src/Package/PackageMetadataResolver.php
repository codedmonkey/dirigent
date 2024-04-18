<?php

namespace CodedMonkey\Conductor\Package;

use CodedMonkey\Conductor\Doctrine\Entity\Package;
use CodedMonkey\Conductor\Registry\RegistryClientManager;
use Composer\MetadataMinifier\MetadataMinifier;
use Composer\Package\AliasPackage;
use Composer\Package\Dumper\ArrayDumper;
use Composer\Package\Loader\ArrayLoader;

class PackageMetadataResolver
{
    public function __construct(
        private readonly RegistryClientManager $registryClientManager,
        private readonly PackageMetadataPool $metadataPool,
        private readonly PackageProviderPool $providerPool,
    ) {
    }

    public function metadataIsFresh(Package $package, ?PackageMetadataItem $metadata = null, ?\DateTimeImmutable $resolvedAt = null): bool
    {
        $metadata ??= $this->metadataPool->read($package->name);
        $resolvedAt ??= (new \DateTimeImmutable())->setTimezone(new \DateTimeZone('UTC'));

        if (null !== $lastResolvedAt = $metadata->lastResolvedAt()) {
            $interval = $resolvedAt->getTimestamp() - $lastResolvedAt->getTimestamp();
            $delay = 3600;

            if ($interval < $delay) {
                return true;
            }
        }

        return false;
    }

    public function resolve(Package $package): array
    {
        $metadata = $this->metadataPool->read($package->name);
        $resolvedAt = (new \DateTimeImmutable())->setTimezone(new \DateTimeZone('UTC'));

        if ($this->metadataIsFresh($package, $metadata, $resolvedAt)) {
            return $metadata->content;
        }

        if (null !== $package->mirrorRegistry) {
            $this->resolveFromRegistry($package, $metadata);
        } else {
            // todo resolve from other sources
            throw new \LogicException();
        }

        $metadata->lastResolved = $resolvedAt->format(\DateTimeInterface::RFC7231);
        $this->metadataPool->write($metadata);

        $this->dumpProviders($package, $metadata);

        return $metadata->content;
    }

    private function resolveFromRegistry(Package $package, PackageMetadataItem $metadata): void
    {
        $registryClient = $this->registryClientManager->getClient($package->mirrorRegistry);

        if (!$registryClient->packageExists($package->name)) {
            $metadata->found = false;

            return;
        }

        $composerPackages = $registryClient->getComposerPackages($package->name);

        $data = [
            'aliases' => [],
            'versions' => [],
        ];
        $dumper = new ArrayDumper();

        foreach ($composerPackages as $composerPackage) {
            $distPackage = $composerPackage instanceof AliasPackage ? $composerPackage->getAliasOf() : $composerPackage;

            $data['versions'][$distPackage->getVersion()] = $dumper->dump($distPackage);

            if ($composerPackage !== $distPackage) {
                $data['aliases'][$composerPackage->getVersion()] = [
                    'version' => $distPackage->getVersion(),
                ];
            }
        }

        $metadata->content = $data;

        $mainComposerPackage = $composerPackages[0];
        $package->description = $mainComposerPackage->getDescription();
    }

    private function dumpProviders(Package $package, PackageMetadataItem $metadata): void
    {
        $composerPackages = (new ArrayLoader())->loadPackages(array_values($metadata->content['versions']));

        $releasePackages = [];
        $devPackages = [];

        foreach ($composerPackages as $composerPackage) {
            if (!$composerPackage->isDev()) {
                $releasePackages[] = $composerPackage;
            } else {
                $devPackages[] = $composerPackage;
            }
        }

        $this->providerPool->write($package->name, $this->compileProvider($package->name, $releasePackages));
        $this->providerPool->write("{$package->name}~dev", $this->compileProvider($package->name, $devPackages));
    }

    private function compileProvider(string $packageName, array $composerPackages): array
    {
        $data = array_map([new ArrayDumper(), 'dump'], $composerPackages);

        return [
            'minified' => 'composer/2.0',
            'packages' => [
                $packageName => MetadataMinifier::minify($data),
            ],
        ];
    }
}
