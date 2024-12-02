<?php

namespace CodedMonkey\Conductor\Doctrine\DataFixtures;

use CodedMonkey\Conductor\Doctrine\Entity\Package;
use CodedMonkey\Conductor\Package\PackageMetadataResolver;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;

class PackageFixtures extends Fixture
{
    public function __construct(
        private readonly PackageMetadataResolver $packageMetadataResolver,
    ) {
    }

    public function load(ObjectManager $manager): void
    {
        foreach ($this->getPackages() as $packageData) {
            $package = new Package();

            $package->setName($packageData['name']);
            $package->setRepositoryUrl($packageData['repositoryUrl']);

            $manager->persist($package);
            $manager->flush();

            // TODO resolve package metadata without fetching from remote
            $this->packageMetadataResolver->resolve($package);

            $manager->flush();
        }
    }

    private function getPackages(): \Generator
    {
        yield [
            'name' => 'psr/log',
            'repositoryUrl' => 'https://github.com/php-fig/log.git',
        ];
    }
}
