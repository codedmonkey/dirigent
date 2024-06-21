<?php

namespace CodedMonkey\Conductor\Controller;

use CodedMonkey\Conductor\Doctrine\Entity\Package;
use CodedMonkey\Conductor\Doctrine\Entity\RegistryPackageMirroring;
use CodedMonkey\Conductor\Doctrine\Repository\PackageRepository;
use CodedMonkey\Conductor\Doctrine\Repository\RegistryRepository;
use CodedMonkey\Conductor\Package\PackageDistributionResolver;
use CodedMonkey\Conductor\Package\PackageMetadataResolver;
use CodedMonkey\Conductor\Package\PackageProviderPool;
use CodedMonkey\Conductor\Registry\RegistryClientManager;
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
        private readonly EntityManagerInterface $entityManager,
        private readonly PackageRepository $packageRepository,
        private readonly PackageMetadataResolver $metadataResolver,
        private readonly PackageDistributionResolver $distributionResolver,
        private readonly PackageProviderPool $providerPool,
    ) {
    }

    #[Route('/packages.json', name: 'api_root', methods: ['GET'])]
    public function root(RouterInterface $router): JsonResponse
    {
        $metadataUrlPattern = u($router->getRouteCollection()->get('api_package_metadata')->getPath())
            ->replace('{packageName}', '%package%')
            ->toString();

        $distributionUrlPattern = u($router->getRouteCollection()->get('api_package_distribution')->getPath())
            ->replace('{packageName}', '%package%')
            ->replace('{version}', '%version%')
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
    public function packageMetadata(string $packageName): Response
    {
        $basePackageName = u($packageName)->trimSuffix('~dev')->toString();
        $package = $this->findPackage($basePackageName);

        if (null === $package) {
            throw new NotFoundHttpException();
        }

        $this->metadataResolver->resolve($package);

        if (null !== $package->getCrawledAt()) {
            $this->entityManager->flush();
        }

        if (!$this->providerPool->exists($packageName)) {
            throw new NotFoundHttpException();
        }

        return new BinaryFileResponse($this->providerPool->path($packageName), headers: ['Content-Type' => 'application/json']);
    }

    #[Route('/dist/{packageName}/{version}-{reference}.{type}',
        name: 'api_package_distribution',
        requirements: [
            'packageName' => '[a-z0-9_.-]+/[a-z0-9_.-]+',
            'version' => '.+',
            'reference' => '[a-z0-9]+',
            'type' => '(zip)',
        ],
        methods: ['GET'],
    )]
    public function packageDistribution(string $packageName, string $version, string $reference, string $type): Response
    {
        if (!$this->distributionResolver->exists($packageName, $version, $reference, $type)) {
            $package = $this->findPackage($packageName);

            if (null === $package) {
                throw new NotFoundHttpException();
            }

            if (!$this->distributionResolver->resolve($package, $version, $reference, $type)) {
                throw new NotFoundHttpException();
            }
        }

        $path = $this->distributionResolver->path($packageName, $version, $reference, $type);

        return $this->file($path);
    }

    #[Route('/downloads', name: 'api_track_downloads', methods: ['POST'])]
    public function trackDownloads(): Response
    {
        return new Response();
    }

    private function findPackage(string $packageName): ?Package
    {
        if ($package = $this->packageRepository->findOneBy(['name' => $packageName])) {
            return $package;
        }

        $package = new Package();
        $package->setName($packageName);

        if (null === $registry = $this->metadataResolver->whatProvides($package)) {
            return null;
        }

        $package->setMirrorRegistry($registry);

        return $package;
    }
}
