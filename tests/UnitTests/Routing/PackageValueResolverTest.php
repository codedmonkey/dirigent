<?php

declare(strict_types=1);

namespace CodedMonkey\Dirigent\Tests\UnitTests\Routing;

use CodedMonkey\Dirigent\Attribute\MapPackage;
use CodedMonkey\Dirigent\Doctrine\Entity\Metadata;
use CodedMonkey\Dirigent\Doctrine\Entity\Package;
use CodedMonkey\Dirigent\Doctrine\Entity\Version;
use CodedMonkey\Dirigent\Doctrine\Repository\MetadataRepository;
use CodedMonkey\Dirigent\Doctrine\Repository\PackageRepository;
use CodedMonkey\Dirigent\Doctrine\Repository\VersionRepository;
use CodedMonkey\Dirigent\Routing\PackageValueResolver;
use CodedMonkey\Dirigent\Tests\Helper\MockEntityFactoryTrait;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\ControllerMetadata\ArgumentMetadata;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

#[CoversClass(PackageValueResolver::class)]
class PackageValueResolverTest extends TestCase
{
    use MockEntityFactoryTrait;

    public function testResolveIgnoresArgumentsWithoutMapPackage(): void
    {
        $argument = new ArgumentMetadata('package', Package::class, false, false, null);

        self::assertSame(
            [],
            $this->createResolver()->resolve(new Request(), $argument),
            'The resolver must return an empty array for arguments without the `MapPackage` attribute.',
        );
    }

    public static function unsupportedTypes(): iterable
    {
        yield 'scalar' => ['string'];
        yield 'unrelated class' => [Request::class];
        yield 'untyped' => [null];
    }

    #[DataProvider('unsupportedTypes')]
    public function testResolveRejectsUnsupportedArgumentTypes(?string $type): void
    {
        $this->expectException(\LogicException::class);
        $this->expectExceptionMessageIs("Invalid argument type: $type");

        $this->createResolver()->resolve(new Request(), $this->createArgument($type));
    }

    public static function packageDependentTypes(): iterable
    {
        yield 'package' => [Package::class];
        yield 'version' => [Version::class];
        yield 'metadata' => [Metadata::class];
    }

    #[DataProvider('packageDependentTypes')]
    public function testResolveRequiresPackageParameter(string $type): void
    {
        $this->expectException(\LogicException::class);
        $this->expectExceptionMessageIs('No package parameter found.');

        $this->createResolver()->resolve(new Request(), $this->createArgument($type));
    }

    public function testResolveReturnsPackage(): void
    {
        $package = $this->createMockPackage();

        $packageRepository = $this->createMock(PackageRepository::class);
        $packageRepository->expects(self::once())->method('findOneByName')
            ->with($package->getName())->willReturn($package);
        $resolver = $this->createResolver(packageRepository: $packageRepository);

        $request = new Request(attributes: ['package' => $package->getName()]);
        $argument = $this->createArgument(Package::class);

        self::assertSame([$package], $resolver->resolve($request, $argument), 'The correct package must be resolved.');
        self::assertSame($package, $request->attributes->get('_package_entity'), 'The package must be added to the request context.');
        self::assertSame([$package], $resolver->resolve($request, $argument), 'Verify the package is not resolved a second time.');
    }

    public function testResolveThrowsWhenPackageDoesNotExist(): void
    {
        $request = new Request(attributes: ['package' => 'vendor/missing']);

        $packageRepository = $this->createMock(PackageRepository::class);
        $packageRepository->expects(self::once())->method('findOneByName')
            ->with('vendor/missing')->willReturn(null);
        $resolver = $this->createResolver(packageRepository: $packageRepository);

        $this->expectException(NotFoundHttpException::class);
        $this->expectExceptionMessageIs('The package does not exist.');

        $resolver->resolve($request, $this->createArgument(Metadata::class));
    }

    public static function versionDependentTypes(): iterable
    {
        yield 'version' => [Version::class];
        yield 'metadata' => [Metadata::class];
    }

    #[DataProvider('versionDependentTypes')]
    public function testResolveRequiresVersionParameter(string $type): void
    {
        $package = $this->createMockPackage();

        $request = new Request(attributes: [
            'package' => $package->getName(),
            '_package_entity' => $package,
        ]);

        $this->expectException(\LogicException::class);
        $this->expectExceptionMessageIs('No version parameter found.');

        $this->createResolver()->resolve($request, $this->createArgument($type));
    }

    public static function versionNames(): iterable
    {
        yield 'release' => ['1.2.3', '1.2.3.0'];
        yield 'prefixed release' => ['v1.2.3', '1.2.3.0'];
        yield 'prerelease' => ['1.2.3-beta1', '1.2.3.0-beta1'];
        yield 'development branch' => ['dev-main', 'dev-main'];
    }

    #[DataProvider('versionNames')]
    public function testResolveReturnsVersion(string $name, string $normalizedName): void
    {
        $package = $this->createMockPackage();
        $version = $this->createMockVersion($package, $name);

        $versionRepository = $this->createMock(VersionRepository::class);
        $versionRepository->expects(self::once())->method('findOneByNormalizedName')
            ->with($package, $normalizedName)->willReturn($version);
        $resolver = $this->createResolver(versionRepository: $versionRepository);

        $request = new Request(attributes: [
            'package' => $package->getName(),
            '_package_entity' => $package,
            'version' => $name,
        ]);
        $argument = $this->createArgument(Version::class);

        self::assertSame([$version], $resolver->resolve($request, $argument), 'The correct version must be resolved.');
        self::assertSame($version, $request->attributes->get('_version_entity'), 'The version must be added to the request context.');
        self::assertSame([$version], $resolver->resolve($request, $argument), 'Verify the version is not resolved a second time.');
    }

    public function testResolveThrowsWhenVersionDoesNotExist(): void
    {
        $package = $this->createMockPackage();

        $versionRepository = $this->createMock(VersionRepository::class);
        $versionRepository->expects(self::once())->method('findOneByNormalizedName')
            ->with($package, '1.2.3.0')->willReturn(null);
        $resolver = $this->createResolver(versionRepository: $versionRepository);

        $request = new Request(attributes: [
            'package' => $package->getName(),
            '_package_entity' => $package,
            'version' => '1.2.3',
        ]);

        $this->expectException(NotFoundHttpException::class);
        $this->expectExceptionMessageIs('The version does not exist.');

        $resolver->resolve($request, $this->createArgument(Metadata::class));
    }

    public function testResolveReturnsCurrentMetadata(): void
    {
        [$package, $version, $metadata] = $this->createMockPackageWithMetadata();

        $resolver = $this->createResolver();

        $request = new Request(attributes: [
            'package' => $package->getName(),
            '_package_entity' => $package,
            'version' => $version->getName(),
            '_version_entity' => $version,
        ]);
        $argument = $this->createArgument(Metadata::class);

        self::assertSame([$metadata], $resolver->resolve($request, $argument), 'The correct metadata must be resolved.');
        self::assertSame($metadata, $request->attributes->get('_metadata_entity'), 'The metadata must be added to the request context.');

        $version->setCurrentMetadata($this->createMockMetadata($version));

        self::assertSame([$metadata], $resolver->resolve($request, $argument), 'Verify the metadata is not resolved a second time.');
    }

    public function testResolveReturnsMetadataRevision(): void
    {
        [$package, $version] = $this->createMockPackageWithMetadata();
        $metadata = $this->createMockMetadata($version);

        $metadataRepository = $this->createMock(MetadataRepository::class);
        $metadataRepository->expects(self::once())->method('findMetadataForVersion')
            ->with($version, 1972)->willReturn($metadata);
        $resolver = $this->createResolver(metadataRepository: $metadataRepository);

        $request = new Request(query: ['revision' => '1972'], attributes: [
            'package' => $package->getName(),
            '_package_entity' => $package,
            'version' => $version->getName(),
            '_version_entity' => $version,
        ]);
        $argument = $this->createArgument(Metadata::class);

        self::assertSame([$metadata], $resolver->resolve($request, $argument), 'The correct metadata must be resolved.');
        self::assertSame($metadata, $request->attributes->get('_metadata_entity'), 'The metadata must be added to the request context.');
        self::assertSame([$metadata], $resolver->resolve($request, $argument), 'Verify the metadata is not resolved a second time.');
    }

    public function testResolveThrowsWhenMetadataRevisionDoesNotExist(): void
    {
        [$package, $version] = $this->createMockPackageWithMetadata();

        $metadataRepository = $this->createMock(MetadataRepository::class);
        $metadataRepository->expects(self::once())->method('findMetadataForVersion')
            ->with($version, 1972)->willReturn(null);
        $resolver = $this->createResolver(metadataRepository: $metadataRepository);

        $request = new Request(query: ['revision' => '1972'], attributes: [
            'package' => $package->getName(),
            '_package_entity' => $package,
            'version' => $version->getName(),
            '_version_entity' => $version,
        ]);

        $this->expectException(NotFoundHttpException::class);
        $this->expectExceptionMessageIs('The metadata does not exist.');

        $resolver->resolve($request, $this->createArgument(Metadata::class));
    }

    private function createArgument(?string $type): ArgumentMetadata
    {
        return new ArgumentMetadata('entity', $type, false, false, null, attributes: [new MapPackage()]);
    }

    private function createResolver(
        (MetadataRepository&MockObject)|null $metadataRepository = null,
        (PackageRepository&MockObject)|null $packageRepository = null,
        (VersionRepository&MockObject)|null $versionRepository = null,
    ): PackageValueResolver {
        if (null === $metadataRepository) {
            $metadataRepository = $this->createMock(MetadataRepository::class);
            $metadataRepository->expects(self::never())->method('findMetadataForVersion');
        }

        if (null === $packageRepository) {
            $packageRepository = $this->createMock(PackageRepository::class);
            $packageRepository->expects(self::never())->method('findOneByName');
        }

        if (null === $versionRepository) {
            $versionRepository = $this->createMock(VersionRepository::class);
            $versionRepository->expects(self::never())->method('findOneByNormalizedName');
        }

        return new PackageValueResolver($metadataRepository, $packageRepository, $versionRepository);
    }
}
