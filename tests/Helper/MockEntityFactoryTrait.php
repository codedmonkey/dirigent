<?php

namespace CodedMonkey\Dirigent\Tests\Helper;

use CodedMonkey\Dirigent\Doctrine\Entity\Package;
use CodedMonkey\Dirigent\Doctrine\Entity\User;
use CodedMonkey\Dirigent\Doctrine\Entity\Version;
use Composer\Semver\VersionParser;
use Doctrine\ORM\EntityManagerInterface;
use Scheb\TwoFactorBundle\Security\TwoFactor\Provider\Totp\TotpAuthenticator;

trait MockEntityFactoryTrait
{
    protected function createMockPackage(): Package
    {
        $package = new Package();
        $package->setName(sprintf('%s/%s', uniqid(), uniqid()));

        return $package;
    }

    protected function createMockUser(bool $mfaEnabled = false): User
    {
        $user = new User();

        $user->setUsername(uniqid());
        $user->setPlainPassword('PlainPassword99');

        if ($mfaEnabled) {
            $totpAuthenticator = $this->getService(TotpAuthenticator::class);

            $user->setTotpSecret($totpAuthenticator->generateSecret());
        }

        return $user;
    }

    protected function createMockVersion(Package $package, string $versionName = '1.0.0'): Version
    {
        $version = new Version($package);

        $version->setPackageName($package->getName());
        $version->setName($versionName);
        $version->setNormalizedName((new VersionParser())->normalize($versionName));

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

    protected function clearEntities(): void
    {
        $entityManager = $this->getService(EntityManagerInterface::class);

        $entityManager->clear();
    }
}
