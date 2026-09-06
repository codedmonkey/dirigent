<?php

declare(strict_types=1);

namespace CodedMonkey\Dirigent\Tests\UnitTests\Package;

use CodedMonkey\Dirigent\Composer\ComposerClient;
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

    public function testResolveRechecksForDistributionAfterAcquiringLock(): void
    {
        [, , $metadata] = $this->createMockPackageWithMetadata();
        $metadata->setDistributionReference('reference');
        $metadata->setDistributionType('zip');
        $metadata->setDistributionUrl('https://example.com/distribution.zip');

        $repository = $this->createMock(DistributionRepository::class);
        $repository->expects(self::never())->method('findOneByReferenceAndType');

        $lock = $this->createMock(SharedLockInterface::class);
        $lockFactory = $this->createMock(LockFactory::class);
        $resolver = new PackageDistributionResolver(
            $this->createStub(MessageBusInterface::class),
            $this->createStub(ComposerClient::class),
            $repository,
            $lockFactory,
            true,
            $this->storagePath,
        );
        $path = $resolver->path($metadata, 'reference', 'zip');

        $lockFactory->expects(self::once())
            ->method('createLock')
            ->with('distribution.' . hash('sha256', $path), null)
            ->willReturn($lock);
        $lock->expects(self::once())
            ->method('acquire')
            ->with(true)
            ->willReturnCallback(static function () use ($path): bool {
                new Filesystem()->dumpFile($path, 'distribution');

                return true;
            });
        $lock->expects(self::once())->method('release');

        self::assertTrue($resolver->resolve($metadata, 'reference', 'zip', async: false));
    }
}
