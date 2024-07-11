<?php

namespace CodedMonkey\Conductor\Controller;

use CodedMonkey\Conductor\Attribute\IsGrantedAccess;
use CodedMonkey\Conductor\Doctrine\Entity\Package;
use CodedMonkey\Conductor\Doctrine\Repository\PackageRepository;
use CodedMonkey\Conductor\Doctrine\Repository\VersionRepository;
use CodedMonkey\Conductor\Package\PackageDistributionResolver;
use CodedMonkey\Conductor\Package\PackageMetadataResolver;
use CodedMonkey\Conductor\Package\PackageProviderManager;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Routing\RouterInterface;
use function Symfony\Component\String\u;

class ApiController extends AbstractController
{
    public function __construct(
        private readonly EntityManagerInterface      $entityManager,
        private readonly PackageRepository           $packageRepository,
        private readonly VersionRepository           $versionRepository,
        private readonly PackageMetadataResolver     $metadataResolver,
        private readonly PackageDistributionResolver $distributionResolver,
        private readonly PackageProviderManager      $providerManager,
    ) {
    }

    #[Route('/packages.json', name: 'api_root', methods: ['GET'])]
    #[IsGrantedAccess]
    public function root(RouterInterface $router): JsonResponse
    {
        $metadataUrlPattern = u($router->getRouteCollection()->get('api_package_metadata')->getPath())
            ->replace('{packageName}', '%package%')
            ->toString();

        $distributionUrlPattern = u($router->getRouteCollection()->get('api_package_distribution')->getPath())
            ->replace('{packageName}', '%package%')
            ->replace('{packageVersion}', '%version%')
            ->replace('{reference}', '%reference%')
            ->replace('{type}', '%type%')
            ->toString();

        return new JsonResponse(json_encode([
            'metadata-url' => $metadataUrlPattern,
            'mirrors' => [
                ['dist-url' => $distributionUrlPattern, 'preferred' => true],
            ],
            'notify-batch' => $router->generate('api_track_downloads'),
            'packages' => [],
        ], JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE), json: true);
    }

    #[Route('/p2/{packageName}.json',
        name: 'api_package_metadata',
        requirements: ['packageName' => '[a-z0-9_.-]+/[a-z0-9_.-]+(~dev)?'],
        methods: ['GET'],
    )]
    #[IsGrantedAccess]
    public function packageMetadata(string $packageName): Response
    {
        $basePackageName = u($packageName)->trimSuffix('~dev')->toString();

        if (null === $package = $this->findPackage($basePackageName)) {
            throw new NotFoundHttpException();
        }

        $this->metadataResolver->resolve($package);
        $this->entityManager->flush();

        if (!$this->providerManager->exists($packageName)) {
            throw new NotFoundHttpException();
        }

        return new BinaryFileResponse($this->providerManager->path($packageName), headers: ['Content-Type' => 'application/json']);
    }

    #[Route('/dist/{packageName}/{packageVersion}-{reference}.{type}',
        name: 'api_package_distribution',
        requirements: [
            'packageName' => '[a-z0-9_.-]+/[a-z0-9_.-]+',
            'packageVersion' => '.+',
            'reference' => '[a-z0-9]+',
            'type' => '(zip)',
        ],
        methods: ['GET'],
    )]
    #[IsGrantedAccess]
    public function packageDistribution(string $packageName, string $packageVersion, string $reference, string $type): Response
    {
        if (!$this->distributionResolver->exists($packageName, $packageVersion, $reference, $type)) {
            if (null === $package = $this->packageRepository->findOneBy(['name' => $packageName])) {
                throw new NotFoundHttpException();
            }

            $this->metadataResolver->resolve($package);

            if (null !== $package->getCrawledAt()) {
                $this->entityManager->flush();
            }

            if (null === $version = $this->versionRepository->findOneBy(['package' => $package, 'normalizedVersion' => $packageVersion])) {
                throw new NotFoundHttpException();
            }

            if (!$this->distributionResolver->resolve($version, $reference, $type)) {
                throw new NotFoundHttpException();
            }
        }

        $path = $this->distributionResolver->path($packageName, $packageVersion, $reference, $type);

        return $this->file($path);
    }

    #[Route('/downloads', name: 'api_track_downloads', methods: ['POST'])]
    #[IsGrantedAccess]
    public function trackDownloads(): Response
    {
        return new Response();
    }

    private function findPackage(string $packageName): ?Package
    {
        if (null !== $package = $this->packageRepository->findOneBy(['name' => $packageName])) {
            return $package;
        }

        if (null === $registry = $this->metadataResolver->findPackageProvider($packageName)) {
            return null;
        }

        $package = new Package();
        $package->setName($packageName);
        $package->setMirrorRegistry($registry);

        return $package;
    }
}
