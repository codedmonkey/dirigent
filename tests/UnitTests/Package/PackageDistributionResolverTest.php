<?php

declare(strict_types=1);

namespace CodedMonkey\Dirigent\Tests\UnitTests\Package;

use CodedMonkey\Dirigent\Composer\ComposerClient;
use CodedMonkey\Dirigent\Doctrine\Entity\Distribution;
use CodedMonkey\Dirigent\Doctrine\Repository\DistributionRepository;
use CodedMonkey\Dirigent\Package\PackageDistributionResolver;
use CodedMonkey\Dirigent\Tests\Helper\MockEntityFactoryTrait;
use Composer\Util\HttpDownloader;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\Filesystem\Path;
use Symfony\Component\Lock\LockFactory;
use Symfony\Component\Lock\SharedLockInterface;
use Symfony\Component\Messenger\MessageBusInterface;

#[CoversClass(PackageDistributionResolver::class)]
class PackageDistributionResolverTest extends TestCase
{
    use MockEntityFactoryTrait;

    private string $storagePath;

    #[\Override]
    protected function setUp(): void
    {
        $this->storagePath = sys_get_temp_dir() . '/dirigent-distribution-resolver-' . uniqid();
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

        $resolver = new PackageDistributionResolver(
            $this->createStub(MessageBusInterface::class),
            $this->createStub(ComposerClient::class),
            $this->createStub(DistributionRepository::class),
            $this->createStub(LockFactory::class),
            true,
            true,
            $this->storagePath,
        );

        $path = $resolver->path($metadata, '../zip');

        self::assertTrue(Path::isBasePath($this->storagePath . '/distribution', $path));
        self::assertStringNotContainsString('../', $path);
        self::assertStringNotContainsString('../../../archive', $path);
        self::assertStringContainsString('..%2F..%2F..%2Farchive', $path);
    }

    public function testRemoveDeletesDistributionFile(): void
    {
        [, , $metadata] = $this->createMockPackageWithMetadata();
        $metadata->setDistributionReference('reference');
        $distribution = new Distribution($metadata, 'zip');

        $resolver = new PackageDistributionResolver(
            $this->createStub(MessageBusInterface::class),
            $this->createStub(ComposerClient::class),
            $this->createStub(DistributionRepository::class),
            $lockFactory = $this->createMock(LockFactory::class),
            true,
            true,
            $this->storagePath,
        );

        $path = $this->dumpStubDistribution($resolver, $distribution);

        $lock = $this->createMock(SharedLockInterface::class);
        $lock->expects(self::once())->method('acquire')->with(true);
        $lock->expects(self::once())->method('release');

        $lockFactory->expects(self::once())
            ->method('createLock')
            ->willReturn($lock);

        $resolver->remove($distribution);

        self::assertFileDoesNotExist($path);
        self::assertDirectoryDoesNotExist(dirname($path));
    }

    public function testRemoveKeepsNonEmptyPackageDirectory(): void
    {
        [, , $metadata] = $this->createMockPackageWithMetadata();
        $metadata->setDistributionReference('reference');
        $distribution = new Distribution($metadata, 'zip');
        $alternativeDistribution = new Distribution($metadata, 'tar');

        $resolver = new PackageDistributionResolver(
            $this->createStub(MessageBusInterface::class),
            $this->createStub(ComposerClient::class),
            $this->createStub(DistributionRepository::class),
            $this->createStub(LockFactory::class),
            true,
            true,
            $this->storagePath,
        );

        $path = $this->dumpStubDistribution($resolver, $distribution);
        $alternativePath = $this->dumpStubDistribution($resolver, $alternativeDistribution);

        $resolver->remove($distribution);

        self::assertFileDoesNotExist($path);
        self::assertFileExists($alternativePath);
        self::assertDirectoryExists(dirname($path));
    }

    public function testRemoveWithTraversalReferenceDoesNotDeleteOutsideDistributionStorage(): void
    {
        [, , $metadata] = $this->createMockPackageWithMetadata();
        $metadata->setDistributionReference('../../../../outside');
        $distribution = new Distribution($metadata, 'zip');

        $lock = $this->createStub(SharedLockInterface::class);
        $lockFactory = $this->createStub(LockFactory::class);
        $lockFactory->method('createLock')->willReturn($lock);

        $resolver = new PackageDistributionResolver(
            $this->createStub(MessageBusInterface::class),
            $this->createStub(ComposerClient::class),
            $this->createStub(DistributionRepository::class),
            $lockFactory,
            true,
            true,
            $this->storagePath,
        );

        $distributionPath = $this->dumpStubDistribution($resolver, $distribution);
        $outsidePath = $this->storagePath . '/outside.zip';
        new Filesystem()->dumpFile($outsidePath, 'outside');

        $resolver->remove($distribution);

        self::assertFileDoesNotExist($distributionPath);
        self::assertFileExists($outsidePath);
    }

    public function testResolveDeletesDownloadedFileWhenPersistenceFails(): void
    {
        [, , $metadata] = $this->createMockPackageWithMetadata();
        $metadata->setDistributionReference('reference');
        $metadata->setDistributionType('zip');
        $metadata->setDistributionUrl('https://example.com/distribution.zip');

        $httpDownloader = $this->createMock(HttpDownloader::class);
        $httpDownloader->expects(self::once())
            ->method('copy')
            ->willReturnCallback(static function (string $url, string $path): void {
                new Filesystem()->dumpFile($path, 'distribution');
            });

        $composer = $this->createMock(ComposerClient::class);
        $composer->expects(self::once())
            ->method('createHttpDownloader')
            ->willReturn($httpDownloader);

        $persistenceException = new \RuntimeException('Persistence failed');
        $distributionRepository = $this->createMock(DistributionRepository::class);
        $distributionRepository->method('findOneByMetadataAndType')->willReturn(null);
        $distributionRepository->expects(self::once())
            ->method('save')
            ->willThrowException($persistenceException);

        $lock = $this->createMock(SharedLockInterface::class);
        $lock->expects(self::once())->method('acquire')->with(true);
        $lock->expects(self::once())->method('release');

        $lockFactory = $this->createMock(LockFactory::class);
        $lockFactory->expects(self::once())
            ->method('createLock')
            ->willReturn($lock);

        $resolver = new PackageDistributionResolver(
            $this->createStub(MessageBusInterface::class),
            $composer,
            $distributionRepository,
            $lockFactory,
            true,
            true,
            $this->storagePath,
        );
        $path = $resolver->path($metadata, 'zip');

        try {
            $resolver->resolve($metadata, 'zip', async: false);
            self::fail('Expected persistence to fail.');
        } catch (\RuntimeException $exception) {
            self::assertSame($persistenceException, $exception);
        }

        self::assertFileDoesNotExist($path);
    }

    private function dumpStubDistribution(PackageDistributionResolver $resolver, Distribution $distribution): string
    {
        $path = $resolver->path($distribution->getMetadata(), $distribution->getType());

        new Filesystem()->dumpFile($path, 'distribution');

        return $path;
    }
}
