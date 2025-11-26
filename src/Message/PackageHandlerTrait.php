<?php

namespace CodedMonkey\Dirigent\Message;

use CodedMonkey\Dirigent\Doctrine\Entity\Package;
use CodedMonkey\Dirigent\Doctrine\Repository\PackageRepository;

trait PackageHandlerTrait
{
    private function getPackage(PackageRepository $repository, int $id): Package
    {
        if (null === $package = $repository->find($id)) {
            throw new \InvalidArgumentException("Package (id: $id) not found.");
        }

        return $package;
    }
}
