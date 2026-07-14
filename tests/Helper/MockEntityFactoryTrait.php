<?php

declare(strict_types=1);

namespace CodedMonkey\Dirigent\Tests\Helper;

use CodedMonkey\Dirigent\Doctrine\Entity\Metadata;
use CodedMonkey\Dirigent\Doctrine\Entity\MetadataRequireLink;
use CodedMonkey\Dirigent\Doctrine\Entity\Package;
use CodedMonkey\Dirigent\Doctrine\Entity\User;
use CodedMonkey\Dirigent\Doctrine\Entity\Version;
use Composer\Semver\VersionParser;
use PHPUnit\Framework\MockObject\MockObject;
use Scheb\TwoFactorBundle\Security\TwoFactor\Provider\Totp\TotpAuthenticator;

trait MockEntityFactoryTrait
{
    protected function createMockMetadata(Version $version): Metadata
    {
        $package = $version->getPackage();

        $metadata = new Metadata($version);
        $metadata->setRevision($version->getNextRevision(increment: true));
        $metadata->setPackageName($package->getName());
        $metadata->setVersionName($version->getName());
        $metadata->setNormalizedVersionName($version->getNormalizedName());

        new MetadataRequireLink($metadata, 'vendor/dependency', '^1.0', 0);

        return $metadata;
    }

    protected function createMockPackage(): Package
    {
        return new Package(sprintf('%s/%s', uniqid(), uniqid()));
    }

    /**
     * @return array{Package, Version, Metadata}
     */
    protected function createMockPackageWithMetadata(): array
    {
        $package = $this->createMockPackage();
        $version = $this->createMockVersion($package);
        $metadata = $this->createMockMetadata($version);

        $version->setCurrentMetadata($metadata);

        return [$package, $version, $metadata];
    }

    protected function createMockUser(bool $mfaEnabled = false): User
    {
        $user = new User();

        $user->setUsername(uniqid());
        $user->setPlainPassword('PlainPassword99');

        if ($mfaEnabled) {
            /** @var TotpAuthenticator&MockObject $totpAuthenticator */
            $totpAuthenticator = $this->getMockBuilder(TotpAuthenticator::class)
                ->disableOriginalConstructor()
                ->getMock();

            $user->setTotpSecret($totpAuthenticator->generateSecret());
        }

        return $user;
    }

    protected function createMockVersion(Package $package, string $versionName = '1.0.0', bool $development = false): Version
    {
        $version = new Version($package);
        $version->setName($versionName);
        $version->setNormalizedName(new VersionParser()->normalize($versionName));
        $version->setDevelopment($development);

        return $version;
    }
}
