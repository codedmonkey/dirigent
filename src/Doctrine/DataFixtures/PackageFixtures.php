<?php

namespace CodedMonkey\Dirigent\Doctrine\DataFixtures;

use CodedMonkey\Dirigent\Doctrine\Entity\Package;
use CodedMonkey\Dirigent\Doctrine\Entity\PackageFetchStrategy;
use CodedMonkey\Dirigent\Package\PackageMetadataResolver;
use Composer\MetadataMinifier\MetadataMinifier;
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
            $package->setFetchStrategy(PackageFetchStrategy::Vcs);

            $manager->persist($package);
            $manager->flush();

            // The source files can (and should) be minimized with the Composer metadata minifier, so expand the definitions before resolving them
            $composerPackages = json_decode(file_get_contents($packageData['jsonFile']), true);
            $composerPackages = MetadataMinifier::expand($composerPackages);

            $this->packageMetadataResolver->resolveManualPackage($package, $composerPackages);

            $versions = $package->getVersions();

            $date = new \DateTimeImmutable('-50 days');
            $today = new \DateTimeImmutable();

            while ($date->getTimestamp() <= $today->getTimestamp()) {
                foreach ($versions as $version) {
                    for ($number = rand(0, 100); $number > 0; --$number) {
                        $version->getInstallations()->increase($date);
                        $package->getInstallations()->increase($date);
                    }
                }

                $date = $date->modify('+1 day');
            }

            foreach ($versions as $version) {
                $version->getInstallations()->mergeData();
            }

            $package->getInstallations()->mergeData();

            $manager->flush();
        }
    }

    private function getPackages(): \Generator
    {
        yield [
            'name' => 'psr/container',
            'repositoryUrl' => 'https://github.com/php-fig/container.git',
            'jsonFile' => __DIR__ . '/packages/psr-container.json',
        ];

        yield [
            'name' => 'psr/log',
            'repositoryUrl' => 'https://github.com/php-fig/log.git',
            'jsonFile' => __DIR__ . '/packages/psr-log.json',
        ];
    }
}
