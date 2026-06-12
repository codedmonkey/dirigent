<?php

declare(strict_types=1);

namespace CodedMonkey\Dirigent\Tests\FunctionalTests\Doctrine\Repository;

use CodedMonkey\Dirigent\Doctrine\Entity\MetadataDevRequireLink;
use CodedMonkey\Dirigent\Doctrine\Entity\MetadataProvideLink;
use CodedMonkey\Dirigent\Doctrine\Entity\MetadataRequireLink;
use CodedMonkey\Dirigent\Doctrine\Entity\MetadataSuggestLink;
use CodedMonkey\Dirigent\Doctrine\Entity\PackageProvideLink;
use CodedMonkey\Dirigent\Doctrine\Entity\PackageRequireLink;
use CodedMonkey\Dirigent\Doctrine\Entity\PackageSuggestLink;
use CodedMonkey\Dirigent\Doctrine\Repository\PackageRepository;
use CodedMonkey\Dirigent\Tests\Helper\EntityManagerTestTrait;
use CodedMonkey\Dirigent\Tests\Helper\KernelTestCaseTrait;
use CodedMonkey\Dirigent\Tests\Helper\MockEntityFactoryTrait;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

class PackageRepositoryTest extends KernelTestCase
{
    use EntityManagerTestTrait;
    use KernelTestCaseTrait;
    use MockEntityFactoryTrait;

    public function testUpdatePackageLinks(): void
    {
        [$package, $version, $metadata] = $this->createMockPackageWithMetadata();

        new MetadataDevRequireLink($metadata, 'vendor/dev-dependency', '^2.0', 0);
        new MetadataProvideLink($metadata, 'vendor/provided', 'self.version', 0);
        new MetadataProvideLink($metadata, 'vendor/contract-implementation', '1.0', 1);
        new MetadataSuggestLink($metadata, 'vendor/suggestion', 'For more features', 0);

        $this->persistEntities($package, $version, $metadata);

        $this->getService(PackageRepository::class)->updatePackageLinks($package, $version);

        $entityManager = $this->getService(EntityManagerInterface::class);

        /** @var PackageRequireLink[] $requireLinks */
        $requireLinks = $entityManager->getRepository(PackageRequireLink::class)->findBy(['package' => $package], ['linkedPackageName' => 'ASC']);
        $this->assertCount(2, $requireLinks);
        $this->assertSame('vendor/dependency', $requireLinks[0]->getLinkedPackageName());
        $this->assertFalse($requireLinks[0]->isDevDependency());
        $this->assertSame('vendor/dev-dependency', $requireLinks[1]->getLinkedPackageName());
        $this->assertTrue($requireLinks[1]->isDevDependency());

        /** @var PackageProvideLink[] $provideLinks */
        $provideLinks = $entityManager->getRepository(PackageProvideLink::class)->findBy(['package' => $package], ['linkedPackageName' => 'ASC']);
        $this->assertCount(2, $provideLinks);
        $this->assertSame('vendor/contract', $provideLinks[0]->getLinkedPackageName());
        $this->assertTrue($provideLinks[0]->isImplementation());
        $this->assertSame('vendor/provided', $provideLinks[1]->getLinkedPackageName());
        $this->assertFalse($provideLinks[1]->isImplementation());

        /** @var PackageSuggestLink[] $suggestLinks */
        $suggestLinks = $entityManager->getRepository(PackageSuggestLink::class)->findBy(['package' => $package]);
        $this->assertCount(1, $suggestLinks);
        $this->assertSame('vendor/suggestion', $suggestLinks[0]->getLinkedPackageName());
    }

    public function testUpdatePackageLinksReplacesExistingLinks(): void
    {
        [$package, $version, $metadata] = $this->createMockPackageWithMetadata();

        new MetadataSuggestLink($metadata, 'vendor/suggestion', 'For more features', 0);

        $this->persistEntities($package, $version, $metadata);

        $packageRepository = $this->getService(PackageRepository::class);
        $packageRepository->updatePackageLinks($package, $version);

        $newMetadata = $this->createMockMetadata($version);
        $newMetadata->setRevision($metadata->getRevision() + 1);
        new MetadataRequireLink($newMetadata, 'vendor/new-dependency', '^2.0', 1);
        $version->setCurrentMetadata($newMetadata);

        $this->persistEntities($version, $newMetadata);

        $packageRepository->updatePackageLinks($package, $version);
        $this->clearEntities();

        $entityManager = $this->getService(EntityManagerInterface::class);

        /** @var PackageRequireLink[] $requireLinks */
        $requireLinks = $entityManager->getRepository(PackageRequireLink::class)->findBy(['package' => $package], ['linkedPackageName' => 'ASC']);
        $this->assertCount(2, $requireLinks);
        $this->assertSame('vendor/dependency', $requireLinks[0]->getLinkedPackageName());
        $this->assertSame('vendor/new-dependency', $requireLinks[1]->getLinkedPackageName());

        // The suggest link of the previous metadata revision is no longer linked to the package
        $suggestLinks = $entityManager->getRepository(PackageSuggestLink::class)->findBy(['package' => $package]);
        $this->assertCount(0, $suggestLinks);
    }

    public function testDeletePackageLinks(): void
    {
        [$package, $version, $metadata] = $this->createMockPackageWithMetadata();

        new MetadataProvideLink($metadata, 'vendor/provided', 'self.version', 0);
        new MetadataSuggestLink($metadata, 'vendor/suggestion', 'For more features', 0);

        $this->persistEntities($package, $version, $metadata);

        $packageRepository = $this->getService(PackageRepository::class);
        $packageRepository->updatePackageLinks($package, $version);
        $packageRepository->deletePackageLinks($package);

        $entityManager = $this->getService(EntityManagerInterface::class);

        $this->assertCount(0, $entityManager->getRepository(PackageRequireLink::class)->findBy(['package' => $package]));
        $this->assertCount(0, $entityManager->getRepository(PackageProvideLink::class)->findBy(['package' => $package]));
        $this->assertCount(0, $entityManager->getRepository(PackageSuggestLink::class)->findBy(['package' => $package]));
    }
}
