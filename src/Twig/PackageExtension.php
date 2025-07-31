<?php

namespace CodedMonkey\Dirigent\Twig;

use CodedMonkey\Dirigent\Doctrine\Entity\Package;
use CodedMonkey\Dirigent\Doctrine\Repository\PackageRepository;
use Composer\Pcre\Preg;
use Twig\Attribute\AsTwigTest;

class PackageExtension
{
    public function __construct(
        private readonly PackageRepository $packageRepository,
    ) {
    }

    #[AsTwigTest('existing_package')]
    public function packageExistsTest(mixed $package): bool
    {
        if ($package instanceof Package) {
            return null !== $package->getId();
        }

        if (is_string($package)) {
            if (!Preg::isMatch('/^[A-Za-z0-9_.-]+\/[A-Za-z0-9_.-]+$/', $package)) {
                return false;
            }

            return null !== $this->packageRepository->findOneByName($package);
        }

        return false;
    }
}
