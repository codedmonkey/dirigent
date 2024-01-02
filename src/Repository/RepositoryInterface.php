<?php

namespace CodedMonkey\Conductor\Repository;

use Composer\Package\PackageInterface;

interface RepositoryInterface
{
    /**
     * @return PackageInterface[]|null
     */
    public function fetchPackageMetadata(string $name): ?array;

    public function fetchPackageDistribution(PackageInterface $package): bool;
}
