<?php

namespace CodedMonkey\Dirigent\Tests\Helper;

use CodedMonkey\Dirigent\Doctrine\Entity\Package;
use CodedMonkey\Dirigent\Doctrine\Entity\Version;
use Composer\Semver\VersionParser;

trait TestEntityFactoryTrait
{
    protected function createPackageEntity(): Package
    {
        $package = new Package();
        $package->setName(sprintf('%s/%s', uniqid(), uniqid()));

        return $package;
    }

    protected function createVersionEntity(Package $package, string $versionName = '1.0.0'): Version
    {
        $version = new Version();

        $version->setName($package->getName());
        $version->setVersion($versionName);
        $version->setNormalizedVersion((new VersionParser())->normalize($versionName));
        $version->setPackage($package);

        $package->getVersions()->add($version);

        return $version;
    }
}
