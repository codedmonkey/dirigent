<?php

namespace CodedMonkey\Dirigent\Tests\Helper;

use CodedMonkey\Dirigent\Doctrine\Entity\Package;
use CodedMonkey\Dirigent\Doctrine\Entity\Version;
use Composer\Semver\VersionParser;
use Doctrine\ORM\EntityManagerInterface;

trait MockEntityFactoryTrait
{
    protected function createMockPackage(): Package
    {
        $package = new Package();
        $package->setName(sprintf('%s/%s', uniqid(), uniqid()));

        return $package;
    }

    protected function createMockVersion(Package $package, string $versionName = '1.0.0'): Version
    {
        $version = new Version();

        $version->setName($package->getName());
        $version->setVersion($versionName);
        $version->setNormalizedVersion((new VersionParser())->normalize($versionName));
        $version->setPackage($package);

        $package->getVersions()->add($version);

        return $version;
    }

    /**
     * Persist and flush all given entities.
     *
     * @param object ...$entities
     */
    protected function persistEntities(...$entities): void
    {
        $entityManager = $this->getService(EntityManagerInterface::class);

        foreach ($entities as $entity) {
            $entityManager->persist($entity);
        }

        $entityManager->flush();
    }
}
