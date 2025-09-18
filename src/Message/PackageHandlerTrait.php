<?php

namespace CodedMonkey\Dirigent\Message;

use CodedMonkey\Dirigent\Doctrine\Entity\Package;
use CodedMonkey\Dirigent\Doctrine\Entity\Version;
use CodedMonkey\Dirigent\Doctrine\Repository\PackageRepository;
use CodedMonkey\Dirigent\Doctrine\Repository\VersionRepository;

trait PackageHandlerTrait
{
    private function getPackage(PackageRepository $repository, int $id): Package
    {
        if (null === $package = $repository->find($id)) {
            throw new \InvalidArgumentException("Package (id: $id) not found.");
        }

        return $package;
    }

    private function getVersion(VersionRepository $repository, int $id): Version
    {
        if (null === $version = $repository->find($id)) {
            throw new \InvalidArgumentException("Version (id: $id) not found.");
        }

        return $version;
    }
}
