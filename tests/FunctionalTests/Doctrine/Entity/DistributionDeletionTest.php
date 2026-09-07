<?php

declare(strict_types=1);

namespace CodedMonkey\Dirigent\Tests\FunctionalTests\Doctrine\Entity;

use CodedMonkey\Dirigent\Doctrine\Entity\Distribution;
use CodedMonkey\Dirigent\Doctrine\Entity\Metadata;
use CodedMonkey\Dirigent\Package\PackageDistributionResolver;
use CodedMonkey\Dirigent\Tests\Helper\EntityManagerTestTrait;
use CodedMonkey\Dirigent\Tests\Helper\KernelTestCaseTrait;
use CodedMonkey\Dirigent\Tests\Helper\MockEntityFactoryTrait;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\Filesystem\Filesystem;

class DistributionDeletionTest extends KernelTestCase
{
    use EntityManagerTestTrait;
    use KernelTestCaseTrait;
    use MockEntityFactoryTrait;

    public function testDeletingDistributionDeletesDistributionFile(): void
    {
        [$package, $version, $metadata] = $this->createMockPackageWithMetadata();
        [$distribution, $path] = $this->createDistributionFile($metadata);
        $this->persistEntities($package, $version, $metadata, $distribution);

        $this->removeEntities($distribution);

        self::assertFileDoesNotExist($path);
    }

    public function testDeletingMetadataDeletesDistributionFile(): void
    {
        [$package, $version, $metadata] = $this->createMockPackageWithMetadata();
        [$distribution, $path] = $this->createDistributionFile($metadata);

        // Metadata can't be deleted if it's the current metadata of a version, so we replace it
        $currentMetadata = $this->createMockMetadata($version);
        $version->setCurrentMetadata($currentMetadata);

        $this->persistEntities($package, $version, $metadata, $currentMetadata, $distribution);

        $this->removeEntities($metadata);

        self::assertFileDoesNotExist($path);
    }

    public function testDeletingPackageDeletesDistributionFile(): void
    {
        [$package, $version, $metadata] = $this->createMockPackageWithMetadata();
        [$distribution, $path] = $this->createDistributionFile($metadata);
        $this->persistEntities($package, $version, $metadata, $distribution);

        $this->removeEntities($package);

        self::assertFileDoesNotExist($path);
    }

    public function testDeletingVersionDeletesDistributionFile(): void
    {
        [$package, $version, $metadata] = $this->createMockPackageWithMetadata();
        [$distribution, $path] = $this->createDistributionFile($metadata);
        $this->persistEntities($package, $version, $metadata, $distribution);

        $this->removeEntities($version);

        self::assertFileDoesNotExist($path);
    }

    /**
     * @return array{Distribution, string}
     */
    private function createDistributionFile(Metadata $metadata): array
    {
        $distribution = new Distribution($metadata, 'reference', 'zip');
        $distribution->setResolvedAt();

        $path = $this->getService(PackageDistributionResolver::class)->path($metadata, $distribution->getReference(), $distribution->getType());
        new Filesystem()->dumpFile($path, 'distribution');

        return [$distribution, $path];
    }
}
