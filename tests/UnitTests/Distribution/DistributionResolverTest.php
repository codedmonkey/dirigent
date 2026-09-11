<?php

declare(strict_types=1);

namespace CodedMonkey\Dirigent\Tests\UnitTests\Distribution;

use CodedMonkey\Dirigent\Composer\ComposerClient;
use CodedMonkey\Dirigent\Distribution\DistributionBuilder;
use CodedMonkey\Dirigent\Distribution\DistributionFilesystem;
use CodedMonkey\Dirigent\Distribution\DistributionMirrorer;
use CodedMonkey\Dirigent\Distribution\DistributionResolver;
use CodedMonkey\Dirigent\Doctrine\Repository\DistributionRepository;
use CodedMonkey\Dirigent\Tests\Helper\MockEntityFactoryTrait;
use Composer\Util\HttpDownloader;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use Psr\Log\NullLogger;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\Lock\LockFactory;
use Symfony\Component\Lock\SharedLockInterface;
use Symfony\Component\Messenger\MessageBusInterface;

#[CoversClass(DistributionResolver::class)]
class DistributionResolverTest extends TestCase
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

    public function testResolveDeletesDownloadedFileWhenPersistenceFails(): void
    {
        [, , $metadata] = $this->createMockPackageWithMetadata();
        $metadata->setDistributionReference('reference');
        $metadata->setDistributionType('zip');
        $metadata->setDistributionUrl('https://example.com/distribution.zip');

        $persistenceException = new \RuntimeException('Persistence failed');

        $httpDownloader = $this->createMock(HttpDownloader::class);
        $httpDownloader->expects(self::once())->method('copy')
            ->willReturnCallback(static function (string $url, string $path): void {
                new Filesystem()->dumpFile($path, 'distribution');
            });

        $composer = $this->createMock(ComposerClient::class);
        $composer->expects(self::once())->method('createHttpDownloader')->willReturn($httpDownloader);

        $distributionRepository = $this->createMock(DistributionRepository::class);
        $distributionRepository->method('findOneByMetadataAndType')->willReturn(null);
        $distributionRepository->expects(self::once())->method('save')->willThrowException($persistenceException);

        $lock = $this->createMock(SharedLockInterface::class);
        $lock->expects(self::once())->method('acquire')->with(true);
        $lock->expects(self::once())->method('release');

        $lockFactory = $this->createMock(LockFactory::class);
        $lockFactory->expects(self::once())->method('createLock')->willReturn($lock);

        $distributionFilesystem = new DistributionFilesystem($lockFactory, $this->storagePath);
        $distributionMirrorer = new DistributionMirrorer($distributionFilesystem, $composer);

        $path = $distributionFilesystem->path($metadata, 'zip');

        $resolver = new DistributionResolver(
            $this->createStub(DistributionBuilder::class),
            $distributionFilesystem,
            $distributionMirrorer,
            $this->createStub(MessageBusInterface::class),
            $distributionRepository,
            new NullLogger(),
            true,
            true,
            true,
        );

        try {
            $resolver->resolve($metadata, 'zip', async: false);
            self::fail('Expected persistence to fail.');
        } catch (\RuntimeException $exception) {
            self::assertSame($persistenceException, $exception, 'The exception must be the same as the one thrown by the persistence layer.');
        }

        self::assertFileDoesNotExist($path, 'The downloaded file must be deleted.');
    }
}
