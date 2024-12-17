<?php

namespace CodedMonkey\Dirigent\Twig;

use CodedMonkey\Dirigent\Doctrine\Entity\Package;
use CodedMonkey\Dirigent\Doctrine\Repository\PackageRepository;
use Composer\Pcre\Preg;
use Twig\Extension\AbstractExtension;
use Twig\TwigTest;

class PackageExtension extends AbstractExtension
{
    public function __construct(
        private readonly PackageRepository $packageRepository,
    ) {
    }

    public function getTests(): array
    {
        return [
            new TwigTest('existing_package', [$this, 'packageExistsTest']),
        ];
    }

    public function packageExistsTest(mixed $package): bool
    {
        if ($package instanceof Package) {
            return null !== $package->getId();
        }

        if (is_string($package)) {
            if (!Preg::isMatch('/^[A-Za-z0-9_.-]+\/[A-Za-z0-9_.-]+$/', $package)) {
                return false;
            }

            return null !== $this->packageRepository->findOneBy(['name' => $package]);
        }

        return false;
    }
}
