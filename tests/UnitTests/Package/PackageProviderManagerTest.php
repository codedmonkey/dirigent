<?php

namespace CodedMonkey\Dirigent\Tests\UnitTests\Package;

use CodedMonkey\Dirigent\Package\PackageProviderManager;
use CodedMonkey\Dirigent\Tests\Helper\MockEntityFactoryTrait;
use Composer\MetadataMinifier\MetadataMinifier;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Filesystem\Filesystem;

#[CoversClass(PackageProviderManager::class)]
class PackageProviderManagerTest extends TestCase
{
    use MockEntityFactoryTrait;

    private string $storagePath;
    private PackageProviderManager $manager;

    #[\Override]
    protected function setUp(): void
    {
        $this->storagePath = sys_get_temp_dir() . '/dirigent-provider-manager-' . uniqid();
        $this->manager = new PackageProviderManager($this->storagePath);
    }

    #[\Override]
    protected function tearDown(): void
    {
        (new Filesystem())->remove($this->storagePath);
    }

    public function testPath(): void
    {
        self::assertSame(
            "{$this->storagePath}/provider/foo/bar.json",
            $this->manager->path('foo/bar'),
        );
    }

    public function testExistsReturnsTrueAfterDump(): void
    {
        $package = $this->createMockPackage();
        $version = $this->createMockVersion($package);
        $version->setCurrentMetadata($this->createMockMetadata($version));

        $this->manager->dump($package);

        self::assertTrue($this->manager->exists($package->getName()));
    }

    public function testExistsReturnsFalseWhenFileIsMissing(): void
    {
        self::assertFalse($this->manager->exists('foo/bar'));
    }

    public function testDumpWritesReleaseAndDevelopmentProviderFiles(): void
    {
        $package = $this->createMockPackage();

        $releaseVersion = $this->createMockVersion($package, '1.0.0');
        $releaseVersion->setCurrentMetadata($this->createMockMetadata($releaseVersion));

        $devVersion = $this->createMockVersion($package, 'dev-main', development: true);
        $devVersion->setCurrentMetadata($this->createMockMetadata($devVersion));

        $this->manager->dump($package);

        $packageName = $package->getName();
        $releasePath = "{$this->storagePath}/provider/{$packageName}.json";
        $devPath = "{$this->storagePath}/provider/{$packageName}~dev.json";

        self::assertFileExists($releasePath);
        self::assertFileExists($devPath);

        $releaseData = json_decode(file_get_contents($releasePath), true);
        self::assertSame('composer/2.0', $releaseData['minified']);
        self::assertArrayHasKey($packageName, $releaseData['packages']);
        $releasePackages = MetadataMinifier::expand($releaseData['packages'][$packageName]);
        self::assertCount(1, $releasePackages);
        self::assertSame('1.0.0', $releasePackages[0]['version']);

        $devData = json_decode(file_get_contents($devPath), true);
        self::assertSame('composer/2.0', $devData['minified']);
        self::assertArrayHasKey($packageName, $devData['packages']);
        $devPackages = MetadataMinifier::expand($devData['packages'][$packageName]);
        self::assertCount(1, $devPackages);
        self::assertSame('dev-main', $devPackages[0]['version']);
    }

    public function testDumpSortsVersionsFromNewestToOldest(): void
    {
        $package = $this->createMockPackage();

        foreach (['1.0.0', '2.0.0', '1.5.0'] as $versionName) {
            $version = $this->createMockVersion($package, $versionName);
            $version->setCurrentMetadata($this->createMockMetadata($version));
        }

        $this->manager->dump($package);

        $data = json_decode(file_get_contents($this->manager->path($package->getName())), true);
        $packages = MetadataMinifier::expand($data['packages'][$package->getName()]);

        self::assertSame(['2.0.0', '1.5.0', '1.0.0'], array_column($packages, 'version'));
    }

    public function testDumpWritesEmptyProviderFileWhenPackageHasNoVersions(): void
    {
        $package = $this->createMockPackage();

        $this->manager->dump($package);

        $data = json_decode(file_get_contents($this->manager->path($package->getName())), true);
        self::assertSame([], $data['packages'][$package->getName()]);
    }

    public function testDumpUpdatesDumpedAtTimestamp(): void
    {
        $package = $this->createMockPackage();
        $version = $this->createMockVersion($package);
        $version->setCurrentMetadata($this->createMockMetadata($version));

        self::assertNull($package->getDumpedAt());

        $this->manager->dump($package);

        self::assertInstanceOf(\DateTimeImmutable::class, $package->getDumpedAt());
    }

    public function testRemoveDeletesBothProviderFiles(): void
    {
        $package = $this->createMockPackage();
        $version = $this->createMockVersion($package);
        $version->setCurrentMetadata($this->createMockMetadata($version));

        $this->manager->dump($package);

        $packageName = $package->getName();
        $releasePath = "{$this->storagePath}/provider/{$packageName}.json";
        $devPath = "{$this->storagePath}/provider/{$packageName}~dev.json";

        self::assertFileExists($releasePath);
        self::assertFileExists($devPath);

        $this->manager->remove($package);

        self::assertFileDoesNotExist($releasePath);
        self::assertFileDoesNotExist($devPath);
    }
}
