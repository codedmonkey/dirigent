<?php

namespace CodedMonkey\Dirigent\Tests\Message;

use CodedMonkey\Dirigent\Doctrine\Entity\Package;
use CodedMonkey\Dirigent\Doctrine\Entity\Version;
use CodedMonkey\Dirigent\Doctrine\Repository\PackageRepository;
use CodedMonkey\Dirigent\Doctrine\Repository\VersionRepository;
use CodedMonkey\Dirigent\Message\TrackInstallations;
use CodedMonkey\Dirigent\Message\TrackInstallationsHandler;
use Doctrine\ORM\EntityManager;
use PHPUnit\Framework\TestCase;

class TrackInstallationsHandlerTest extends TestCase
{
    private TrackInstallationsHandler $handler;
    private EntityManager $entityManager;
    private PackageRepository $packageRepository;
    private VersionRepository $versionRepository;

    protected function setUp(): void
    {
        $this->entityManager = $this->createMock(EntityManager::class);
        $this->packageRepository = $this->createMock(PackageRepository::class);
        $this->versionRepository = $this->createMock(VersionRepository::class);

        $this->handler = new TrackInstallationsHandler($this->entityManager, $this->packageRepository, $this->versionRepository);
    }

    public function testInvoke(): void
    {
        $package = new Package();
        $package->setName('foo/bar');

        $version = new Version();
        $version->setPackage($package);
        $version->setNormalizedVersion('1.0.0');

        $this->packageRepository->expects($this->once())
            ->method('findOneByName')
            ->with('foo/bar')
            ->willReturn($package);

        $this->versionRepository->expects($this->once())
            ->method('findOneByNormalizedVersion')
            ->with($package, '1.0.0')
            ->willReturn($version);

        $this->entityManager->expects($this->exactly(2))
            ->method('persist');

        $this->entityManager->expects($this->once())
            ->method('flush');

        $message = new TrackInstallations(
            [
                ['name' => 'foo/bar', 'version' => '1.0.0'],
            ],
            new \DateTimeImmutable('2024-05-06'),
        );

        $this->handler->__invoke($message);

        self::assertSame(1, $package->getInstallations()->getTotal());
        self::assertSame([
            '20240506' => 1,
        ], $package->getInstallations()->getData());

        self::assertSame(1, $version->getInstallations()->getTotal());
        self::assertSame([
            '20240506' => 1,
        ], $version->getInstallations()->getData());
    }
}
