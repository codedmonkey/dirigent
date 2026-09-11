<?php

declare(strict_types=1);

namespace CodedMonkey\Dirigent\Tests\UnitTests\Distribution;

use CodedMonkey\Dirigent\Distribution\DistributionFilesystem;
use CodedMonkey\Dirigent\Doctrine\Entity\Distribution;
use CodedMonkey\Dirigent\Tests\Helper\MockEntityFactoryTrait;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\Filesystem\Path;
use Symfony\Component\Lock\LockFactory;
use Symfony\Component\Lock\SharedLockInterface;

#[CoversClass(DistributionFilesystem::class)]
class DistributionFilesystemTest extends TestCase
{
    use MockEntityFactoryTrait;
    private string $storagePath;

    #[\Override]
    protected function setUp(): void
    {
        $this->storagePath = sys_get_temp_dir() . '/' . uniqid('dirigent-distribution-resolver-');
    }

    #[\Override]
    protected function tearDown(): void
    {
        new Filesystem()->remove($this->storagePath);
    }

    public function testPathEncodesMetadataAndKeepsTraversalReferenceInsideStorage(): void
    {
        [$package, , $metadata] = $this->createMockPackageWithMetadata();
        $package->setName('../outside');
        $metadata->setNormalizedVersionName('../../version');
        $metadata->setDistributionReference('../../../archive');

        $filesystem = new DistributionFilesystem(
            $this->createStub(LockFactory::class),
            $this->storagePath,
        );

        $path = $filesystem->path($metadata, '../zip');

        self::assertTrue(Path::isBasePath("$this->storagePath/distribution", $path), 'The distribution path must be inside the storage path.');
        self::assertStringNotContainsString('../', $path, 'The distribution path must not contain any raw traversal references.');
        self::assertStringContainsString('..%2F..%2F..%2Farchive', $path, 'The distribution path must contain the encoded traversal references.');
    }

    public function testRemoveFileDeletesDistributionFile(): void
    {
        [, , $metadata] = $this->createMockPackageWithMetadata();
        $metadata->setDistributionReference('reference');
        $distribution = new Distribution($metadata, 'zip');

        $lock = $this->createMock(SharedLockInterface::class);
        $lock->expects(self::once())->method('acquire')->with(true);
        $lock->expects(self::once())->method('release');

        $lockFactory = $this->createMock(LockFactory::class);
        $lockFactory->expects(self::once())->method('createLock')->willReturn($lock);

        $filesystem = new DistributionFilesystem(
            $lockFactory,
            $this->storagePath,
        );

        $path = $this->dumpStubDistribution($filesystem, $distribution);

        $filesystem->removeFile($filesystem->relativePath($distribution));

        self::assertFileDoesNotExist($path, 'The distribution file must be deleted.');
        self::assertDirectoryDoesNotExist(dirname($path), 'The distribution\'s directory must be deleted when empty.');
    }

    public function testRemoveFileKeepsNonEmptyPackageDirectory(): void
    {
        [, , $metadata] = $this->createMockPackageWithMetadata();
        $metadata->setDistributionReference('reference');
        $distribution = new Distribution($metadata, 'zip');
        $alternativeDistribution = new Distribution($metadata, 'tar');

        $filesystem = new DistributionFilesystem(
            $this->createStub(LockFactory::class),
            $this->storagePath,
        );

        $path = $this->dumpStubDistribution($filesystem, $distribution);
        $alternativePath = $this->dumpStubDistribution($filesystem, $alternativeDistribution);

        $filesystem->removeFile($filesystem->relativePath($distribution));

        self::assertFileDoesNotExist($path, 'The distribution file must be deleted.');
        self::assertFileExists($alternativePath, 'Other distribution files must not be deleted.');
        self::assertDirectoryExists(dirname($path), 'The distribution\'s directory must not be deleted when not empty.');
    }

    public function testRemoveFileWithTraversalReferenceDoesNotDeleteOutsideDistributionStorage(): void
    {
        [, , $metadata] = $this->createMockPackageWithMetadata();
        $metadata->setDistributionReference('../../../../outside');
        $distribution = new Distribution($metadata, 'zip');

        $lock = $this->createStub(SharedLockInterface::class);
        $lockFactory = $this->createStub(LockFactory::class);
        $lockFactory->method('createLock')->willReturn($lock);

        $outsidePath = "$this->storagePath/outside.zip";
        new Filesystem()->dumpFile($outsidePath, 'outside');

        $filesystem = new DistributionFilesystem(
            $this->createStub(LockFactory::class),
            $this->storagePath,
        );

        $distributionPath = $this->dumpStubDistribution($filesystem, $distribution);

        $filesystem->removeFile($filesystem->relativePath($distribution));

        self::assertFileDoesNotExist($distributionPath, 'The distribution file must be deleted.');
        self::assertFileExists($outsidePath, 'The outside file must not be deleted.');
    }

    private function dumpStubDistribution(DistributionFilesystem $filesystem, Distribution $distribution): string
    {
        $path = $filesystem->path($distribution->getMetadata(), $distribution->getType());

        new Filesystem()->dumpFile($path, 'distribution');

        return $path;
    }
}
