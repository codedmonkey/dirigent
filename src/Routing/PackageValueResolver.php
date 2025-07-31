<?php

namespace CodedMonkey\Dirigent\Routing;

use CodedMonkey\Dirigent\Attribute\MapPackage;
use CodedMonkey\Dirigent\Doctrine\Entity\Package;
use CodedMonkey\Dirigent\Doctrine\Entity\Version;
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
        private PackageRepository $packageRepository,
        private VersionRepository $versionRepository,
    ) {
    }

    public function resolve(Request $request, ArgumentMetadata $argument): iterable
    {
        if (0 === count($argument->getAttributes(MapPackage::class))) {
            return [];
        }

        $entity = match ($argument->getType()) {
            Package::class, Version::class => $argument->getType(),
            default => throw new \LogicException('Invalid argument type: ' . $argument->getType()),
        };

        // There should always be a package name, so fetch the package first
        if (!$request->attributes->has('_package')) {
            $packageName = $request->attributes->get('package');

            if (null === $package = $this->packageRepository->findOneByName($packageName)) {
                throw new NotFoundHttpException('The package does not exist.');
            }

            $request->attributes->set('_package', $package);
        }

        $package ??= $request->attributes->get('_package');

        if (Package::class === $entity) {
            return [$package];
        }

        static $versionParser = new VersionParser();

        if (!$request->attributes->has('_package_version')) {
            $versionName = $request->attributes->get('version');
            $versionName = $versionParser->normalize($versionName);

            if (null === $version = $this->versionRepository->findOneByNormalizedVersion($package, $versionName)) {
                throw new NotFoundHttpException('The package version does not exist.');
            }

            $request->attributes->set('_package_version', $version);
        }

        $version ??= $request->attributes->get('_package_version');

        return [$version];
    }
}
