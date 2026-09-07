<?php

declare(strict_types=1);

namespace CodedMonkey\Dirigent\Tests\UnitTests\Package;

use CodedMonkey\Dirigent\Composer\ComposerClient;
use CodedMonkey\Dirigent\Doctrine\Entity\Distribution;
use CodedMonkey\Dirigent\Doctrine\Repository\DistributionRepository;
use CodedMonkey\Dirigent\Package\PackageDistributionResolver;
use CodedMonkey\Dirigent\Tests\Helper\MockEntityFactoryTrait;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Filesystem\Filesystem;
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

    public function testRemoveDeletesDistributionFile(): void
    {
        [, , $metadata] = $this->createMockPackageWithMetadata();
        $distribution = new Distribution($metadata, 'reference', 'zip');

        $resolver = new PackageDistributionResolver(
            $this->createStub(MessageBusInterface::class),
            $this->createStub(ComposerClient::class),
            $this->createStub(DistributionRepository::class),
            $lockFactory = $this->createMock(LockFactory::class),
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
        $distribution = new Distribution($metadata, 'reference', 'zip');
        $alternativeDistribution = new Distribution($metadata, 'reference', 'tar');

        $resolver = new PackageDistributionResolver(
            $this->createStub(MessageBusInterface::class),
            $this->createStub(ComposerClient::class),
            $this->createStub(DistributionRepository::class),
            $this->createStub(LockFactory::class),
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

    private function dumpStubDistribution(PackageDistributionResolver $resolver, Distribution $distribution): string
    {
        $path = $resolver->path($distribution->getMetadata(), $distribution->getReference(), $distribution->getType());

        new Filesystem()->dumpFile($path, 'distribution');

        return $path;
    }
}
