<?php

declare(strict_types=1);

namespace CodedMonkey\Dirigent\Tests\UnitTests\Package;

use CodedMonkey\Dirigent\Entity\PackageFetchStrategy;
use CodedMonkey\Dirigent\Package\PackageProviderManager;
use CodedMonkey\Dirigent\Tests\Helper\MockEntityFactoryTrait;
use Composer\MetadataMinifier\MetadataMinifier;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

#[CoversClass(PackageProviderManager::class)]
class PackageProviderManagerTest extends TestCase
{
    use MockEntityFactoryTrait;

    private string $storagePath;
    private PackageProviderManager $manager;

    #[\Override]
    protected function setUp(): void
    {
        $this->storagePath = sys_get_temp_dir() . '/' . uniqid('dirigent-provider-manager-');
        $this->manager = new PackageProviderManager(
            $this->createStub(UrlGeneratorInterface::class),
            true,
            false,
            $this->storagePath,
        );
    }

    #[\Override]
    protected function tearDown(): void
    {
        new Filesystem()->remove($this->storagePath);
    }

    public function testPath(): void
    {
        self::assertSame(
            "{$this->storagePath}/provider/foo/bar.json",
            $this->manager->path('foo/bar'),
        );
    }

    public static function sourceTypes(): iterable
    {
        yield 'git' => ['git', true];
        yield 'svn' => ['svn', false];
        yield 'mercurial' => ['hg', false];
        yield 'perforce' => ['perforce', false];
        yield 'fossil' => ['fossil', false];
        yield 'unknown' => [null, false];
    }

    #[DataProvider('sourceTypes')]
    public function testOnlyGitSourcesReceiveGeneratedDistributionUrls(?string $sourceType, bool $expected): void
    {
        [$package, , $metadata] = $this->createMockPackageWithMetadata();
        $package->setFetchStrategy(PackageFetchStrategy::Vcs);
        $metadata->setSourceType($sourceType);
        $metadata->setSourceReference('svn' === $sourceType ? '/tags/1.0.0/@123' : 'reference');
        $metadata->setDistributionType('zip');
        $metadata->setDistributionUrl('https://example.com/original.zip');
        $originalDistribution = $metadata->toComposerArray()['dist'];

        $router = $this->createMock(UrlGeneratorInterface::class);
        $router->expects($expected ? self::once() : self::never())
            ->method('generate')->willReturn('https://example.com/generated.zip');

        $manager = new PackageProviderManager($router, true, false, $this->storagePath);
        $manager->dump($package);

        $data = json_decode(file_get_contents($manager->path($package->getName())), true);
        $packages = MetadataMinifier::expand($data['packages'][$package->getName()]);

        if ($expected) {
            self::assertSame('https://example.com/generated.zip', $packages[0]['dist']['url']);
        } else {
            self::assertSame($originalDistribution, $packages[0]['dist']);
        }
    }

    public function testExists(): void
    {
        $packageName = sprintf('%s/%s', uniqid(), uniqid());
        $path = $this->manager->path($packageName);

        self::assertFalse($this->manager->exists($packageName), 'File must not exist before dumping.');

        new Filesystem()->dumpFile($path, '{}');

        self::assertTrue($this->manager->exists($packageName), 'File must exist after dumping.');
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
