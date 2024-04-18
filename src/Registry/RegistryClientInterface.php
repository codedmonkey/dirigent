<?php

namespace CodedMonkey\Conductor\Registry;

use Composer\Package\PackageInterface as ComposerPackage;

interface RegistryClientInterface
{
    public function packageExists(string $packageName): bool;

    public function resolvePackageMetadata(string $packageName): RegistryResolveStatus;

    public function resolvePackageDistribution(ComposerPackage $composerPackage, string $path): RegistryResolveStatus;

    /**
     * @return ComposerPackage[]|null
     */
    public function getComposerPackages(string $packageName): ?array;
}
