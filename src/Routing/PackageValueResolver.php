<?php

declare(strict_types=1);

namespace CodedMonkey\Dirigent\Routing;

use CodedMonkey\Dirigent\Attribute\MapPackage;
use CodedMonkey\Dirigent\Doctrine\Entity\Metadata;
use CodedMonkey\Dirigent\Doctrine\Entity\Package;
use CodedMonkey\Dirigent\Doctrine\Entity\Version;
use CodedMonkey\Dirigent\Doctrine\Repository\MetadataRepository;
use CodedMonkey\Dirigent\Doctrine\Repository\PackageRepository;
use CodedMonkey\Dirigent\Doctrine\Repository\VersionRepository;
use Composer\Semver\VersionParser;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Controller\ValueResolverInterface;
use Symfony\Component\HttpKernel\ControllerMetadata\ArgumentMetadata;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

readonly class PackageValueResolver implements ValueResolverInterface
{
    public function __construct(
        private MetadataRepository $metadataRepository,
        private PackageRepository $packageRepository,
        private VersionRepository $versionRepository,
    ) {
    }

    public function resolve(Request $request, ArgumentMetadata $argument): iterable
    {
        if (0 === count($argument->getAttributes(MapPackage::class))) {
            return [];
        }

        static $supportedEntities = [Metadata::class, Package::class, Version::class];
        $entity = $argument->getType();

        if (!in_array($entity, $supportedEntities, true)) {
            throw new \LogicException("Invalid argument type: $entity");
        }

        // Resolve the package before we can resolve the version
        if (!$request->attributes->has('package')) {
            throw new \LogicException('No package parameter found.');
        }

        if (!$request->attributes->has('_package_entity')) {
            $packageName = $request->attributes->getString('package');

            if (null === $package = $this->packageRepository->findOneByName($packageName)) {
                throw new NotFoundHttpException('The package does not exist.');
            }

            $request->attributes->set('_package_entity', $package);
        }

        $package ??= $request->attributes->get('_package_entity');

        if (Package::class === $entity) {
            return [$package];
        }

        // Resolve the version before we can resolve the metadata
        if (!$request->attributes->has('version')) {
            throw new \LogicException('No version parameter found.');
        }

        static $versionParser = new VersionParser();

        if (!$request->attributes->has('_version_entity')) {
            $versionName = $request->attributes->getString('version');
            $versionName = $versionParser->normalize($versionName);

            if (null === $version = $this->versionRepository->findOneByNormalizedName($package, $versionName)) {
                throw new NotFoundHttpException('The version does not exist.');
            }

            $request->attributes->set('_version_entity', $version);
        }

        $version ??= $request->attributes->get('_version_entity');

        if (Version::class === $entity) {
            return [$version];
        }

        // Finally, resolve the metadata
        if (!$request->attributes->has('_metadata_entity')) {
            // To resolve metadata we check for the optional revision parameter
            if (!$request->query->has('revision')) {
                $request->attributes->set('_metadata_entity', $metadata = $version->getCurrentMetadata());
            } else {
                $revision = $request->query->getInt('revision');

                if (null === $metadata = $this->metadataRepository->findMetadataForVersion($version, $revision)) {
                    throw new NotFoundHttpException('The metadata does not exist.');
                }

                $request->attributes->set('_metadata_entity', $metadata);
            }
        }

        $metadata ??= $request->attributes->get('_metadata_entity');

        return [$metadata];
    }
}
