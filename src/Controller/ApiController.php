<?php

namespace CodedMonkey\Conductor\Controller;

use CodedMonkey\Conductor\Conductor;
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
    #[Route('/packages.json', name: 'api_root', methods: ['GET'])]
    public function root(RouterInterface $router): JsonResponse
    {
        $distributionUrlPattern = u($router->getRouteCollection()->get('api_package_distribution')->getPath())
            ->replace('{packageName}', '%package%')
            ->replace('{version}', '%version%')
            ->replace('{reference}', '%reference%')
            ->replace('{type}', '%type%')
            ->toString();

        return new JsonResponse(json_encode([
            'metadata-url' => '/p2/%package%.json',
            'mirrors' => [
                ['dist-url' => $distributionUrlPattern, 'preferred' => true],
            ],
            'packages' => [],
        ], JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE), json: true);
    }

    #[Route('/p2/{packageName}.json',
        name: 'api_package_metadata',
        requirements: ['packageName' => '^[a-z0-9_.-]+/[a-z0-9_.-]+(~dev)?$'],
        methods: ['GET'],
    )]
    public function packageMetadata(string $packageName, Conductor $conductor): Response
    {
        $basePackageName = u($packageName)->trimSuffix('~dev')->toString();

        $conductor->resolvePackageMetadata($basePackageName);

        $providerPath = $conductor->getProviderPath($packageName);
        $isCached = file_exists($providerPath);

        if (!$isCached) {
            //return new JsonResponse("404 | computer says no", Response::HTTP_NOT_FOUND);
            throw new NotFoundHttpException();
        }

        return new BinaryFileResponse($providerPath, headers: ['Content-Type' => 'application/json']);
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
    public function packageDistribution(string $packageName, string $version, string $reference, string $type, Conductor $conductor): Response
    {
        $distributionPath = $conductor->getDistributionPath($packageName, $version, $reference, $type);
        $isCached = file_exists($distributionPath);

        if (!$isCached) {
            $conductor->resolvePackageDistribution($packageName, $version);

            $isCached = file_exists($distributionPath);

            if (!$isCached) {
                throw new NotFoundHttpException();
            }
        }

        return new BinaryFileResponse($distributionPath);
    }
}
