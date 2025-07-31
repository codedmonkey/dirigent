<?php

namespace CodedMonkey\Dirigent\Controller;

use CodedMonkey\Dirigent\Attribute\IsGrantedAccess;
use CodedMonkey\Dirigent\Attribute\MapPackage;
use CodedMonkey\Dirigent\Doctrine\Entity\Package;
use CodedMonkey\Dirigent\Doctrine\Entity\PackageFetchStrategy;
use CodedMonkey\Dirigent\Doctrine\Repository\PackageRepository;
use CodedMonkey\Dirigent\Doctrine\Repository\VersionRepository;
use CodedMonkey\Dirigent\Message\TrackInstallations;
use CodedMonkey\Dirigent\Message\UpdatePackage;
use CodedMonkey\Dirigent\Package\PackageDistributionResolver;
use CodedMonkey\Dirigent\Package\PackageMetadataResolver;
use CodedMonkey\Dirigent\Package\PackageProviderManager;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Messenger\Envelope;
use Symfony\Component\Messenger\MessageBusInterface;
use Symfony\Component\Messenger\Stamp\TransportNamesStamp;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Routing\RouterInterface;
use function Symfony\Component\String\u;

class ApiController extends AbstractController
{
    public function __construct(
        private readonly PackageRepository $packageRepository,
        private readonly VersionRepository $versionRepository,
        private readonly PackageMetadataResolver $metadataResolver,
        private readonly PackageDistributionResolver $distributionResolver,
        private readonly PackageProviderManager $providerManager,
        private readonly MessageBusInterface $messenger,
    ) {
    }

    #[Route('/packages.json', name: 'api_root', methods: ['GET'])]
    #[IsGrantedAccess]
    public function root(RouterInterface $router): JsonResponse
    {
        $metadataUrlPattern = u($router->getRouteCollection()->get('api_package_metadata')->getPath())
            ->replace('{package}', '%package%')
            ->toString();

        $data = [
            'packages' => [],
            'metadata-url' => $metadataUrlPattern,
            'notify-batch' => $router->generate('api_track_installations'),
        ];

        if ($this->getParameter('dirigent.dist_mirroring.enabled')) {
            $distributionUrlPattern = u($router->getRouteCollection()->get('api_package_distribution')->getPath())
                ->replace('{package}', '%package%')
                ->replace('{version}', '%version%')
                ->replace('{reference}', '%reference%')
                ->replace('{type}', '%type%')
                ->toString();

            $data['mirrors'] = [[
                'dist-url' => $distributionUrlPattern,
                'preferred' => $this->getParameter('dirigent.dist_mirroring.preferred'),
            ]];
        }

        return new JsonResponse(json_encode($data, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE), json: true);
    }

    #[Route('/p2/{package}.json',
        name: 'api_package_metadata',
        requirements: ['package' => MapPackage::PACKAGE_DEV_REGEX],
        methods: ['GET'],
    )]
    #[IsGrantedAccess]
    public function packageMetadata(Request $request): Response
    {
        $packageName = $request->attributes->get('package');
        $basePackageName = u($packageName)->trimSuffix('~dev')->toString();

        if (null === $package = $this->findPackage($basePackageName)) {
            throw $this->createNotFoundException();
        }

        $this->messenger->dispatch(new UpdatePackage($package->getId()));

        if (!$this->providerManager->exists($packageName)) {
            throw $this->createNotFoundException();
        }

        return new BinaryFileResponse($this->providerManager->path($packageName), headers: ['Content-Type' => 'application/json']);
    }

    #[Route('/dist/{package}/{version}-{reference}.{type}',
        name: 'api_package_distribution',
        requirements: [
            'package' => MapPackage::PACKAGE_REGEX,
            'version' => '.+',
            'reference' => '[a-z0-9]+',
            'type' => '(zip)',
        ],
        methods: ['GET'],
    )]
    #[IsGrantedAccess]
    public function packageDistribution(Request $request, string $reference, string $type): Response
    {
        if (!$this->getParameter('dirigent.dist_mirroring.enabled')) {
            throw $this->createNotFoundException();
        }

        $packageName = $request->attributes->get('package');
        $versionName = $request->attributes->get('version');

        if (!$this->distributionResolver->exists($packageName, $versionName, $reference, $type)) {
            if (null === $package = $this->findPackage($packageName)) {
                throw $this->createNotFoundException();
            }

            $this->messenger->dispatch(new UpdatePackage($package->getId()));

            if (null === $version = $this->versionRepository->findOneByNormalizedVersion($package, $versionName)) {
                throw $this->createNotFoundException();
            }

            if ($version->isDevelopment() && !$this->getParameter('dirigent.dist_mirroring.dev_packages')) {
                throw $this->createNotFoundException();
            }

            if (!$this->distributionResolver->resolve($version, $reference, $type)) {
                throw $this->createNotFoundException();
            }
        }

        $path = $this->distributionResolver->path($packageName, $versionName, $reference, $type);

        return $this->file($path);
    }

    #[Route('/downloads', name: 'api_track_installations', methods: ['POST'])]
    #[IsGrantedAccess]
    public function trackInstallations(Request $request): Response
    {
        $contents = json_decode($request->getContent(), true);
        $invalidInputs = static function ($item) {
            return !isset($item['name'], $item['version']);
        };

        if (!is_array($contents) || !isset($contents['downloads']) || !is_array($contents['downloads']) || array_filter($contents['downloads'], $invalidInputs)) {
            return new JsonResponse(['status' => 'error', 'message' => 'Invalid request format, must be a json object containing a downloads key filled with an array of name/version objects'], 200);
        }

        $message = Envelope::wrap(new TrackInstallations($contents['downloads'], new \DateTimeImmutable()))
            ->with(new TransportNamesStamp('async'));
        $this->messenger->dispatch($message);

        return new JsonResponse(['status' => 'success'], Response::HTTP_CREATED);
    }

    private function findPackage(string $packageName): ?Package
    {
        // Search for the package in the database
        if (null !== $package = $this->packageRepository->findOneByName($packageName)) {
            return $package;
        }

        // Attempt to find a package from external registries
        if (null === $registry = $this->metadataResolver->findPackageProvider($packageName)) {
            return null;
        }

        $package = new Package();
        $package->setName($packageName);
        $package->setMirrorRegistry($registry);
        $package->setFetchStrategy(PackageFetchStrategy::Mirror);

        $this->packageRepository->save($package, true);

        return $package;
    }
}
