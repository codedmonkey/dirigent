<?php

declare(strict_types=1);

namespace CodedMonkey\Dirigent\Tests\UnitTests\Distribution;

use CodedMonkey\Dirigent\Composer\ComposerClient;
use CodedMonkey\Dirigent\Distribution\DistributionFilesystem;
use CodedMonkey\Dirigent\Distribution\DistributionMirrorer;
use CodedMonkey\Dirigent\Doctrine\Entity\Distribution;
use CodedMonkey\Dirigent\Tests\Helper\MockEntityFactoryTrait;
use Composer\Util\HttpDownloader;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(DistributionMirrorer::class)]
class DistributionMirrorerTest extends TestCase
{
    use MockEntityFactoryTrait;

    public function testMirrorDownloadsDistributionAndRecordsSource(): void
    {
        [, , $metadata] = $this->createMockPackageWithMetadata();
        $metadata->setDistributionReference('reference');
        $metadata->setDistributionUrl('https://example.com/distribution.zip');
        $distribution = new Distribution($metadata, 'zip');

        $path = '/distribution.zip';

        $filesystem = $this->createMock(DistributionFilesystem::class);
        $filesystem->expects(self::once())->method('createDirectory')->with($path);

        $httpDownloader = $this->createMock(HttpDownloader::class);
        $httpDownloader->expects(self::once())->method('copy')->with($metadata->getDistributionUrl(), $path);

        $composer = $this->createMock(ComposerClient::class);
        $composer->expects(self::once())->method('createHttpDownloader')->willReturn($httpDownloader);

        $mirrorer = new DistributionMirrorer($filesystem, $composer);

        self::assertTrue($mirrorer->mirror($distribution, $path), 'The mirroring must succeed.');

        self::assertSame($metadata->getDistributionUrl(), $distribution->getSource(), 'The source must be the distribution URL.');
    }
}
