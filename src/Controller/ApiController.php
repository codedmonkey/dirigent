<?php

namespace CodedMonkey\Conductor\Controller;

use CodedMonkey\Conductor\Conductor;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Routing\Attribute\Route;
use function Symfony\Component\String\u;

class ApiController extends AbstractController
{
    #[Route('/packages.json', name: 'api_root')]
    public function root(): JsonResponse
    {
        return new JsonResponse(json_encode([
            'metadata-url' => '/p2/%package%.json',
            'packages' => [],
        ], JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE), json: true);
    }

    #[Route('/p2/{packageName}.json',
        name: 'api_package_metadata',
        requirements: ['packageName' => '^[a-z0-9_.-]+/[a-z0-9_.-]+(~dev)?$'],
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

    #[Route('/dist/{packageName}/{version}-{key}.{ext}',
        name: 'api_package_distribution',
        requirements: [
            'packageName' => '[a-z0-9_.-]+/[a-z0-9_.-]+',
            'version' => '.+',
            'key' => '[a-z0-9]+',
            'ext' => '(zip)',
        ],
    )]
    public function packageDistribution(string $packageName, string $version, string $key, string $ext, Conductor $conductor): Response
    {
        $distributionPath = $conductor->getDistributionPath($packageName, $version, $key, $ext);
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
