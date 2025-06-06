<?php

namespace CodedMonkey\Dirigent\Controller\Dashboard;

use CodedMonkey\Dirigent\Attribute\IsGrantedAccess;
use CodedMonkey\Dirigent\Doctrine\Entity\Package;
use CodedMonkey\Dirigent\Doctrine\Entity\PackageProvideLink;
use CodedMonkey\Dirigent\Doctrine\Entity\PackageRequireLink;
use CodedMonkey\Dirigent\Doctrine\Entity\PackageSuggestLink;
use CodedMonkey\Dirigent\Doctrine\Repository\PackageRepository;
use CodedMonkey\Dirigent\EasyAdmin\PackagePaginator;
use Composer\Semver\VersionParser;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\QueryBuilder;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

class DashboardPackagesInfoController extends AbstractController
{
    public function __construct(
        private readonly EntityManagerInterface $entityManager,
        private readonly PackageRepository $packageRepository,
    ) {
    }

    #[Route('/packages/{packageName}', name: 'dashboard_packages_info', requirements: ['packageName' => '[a-z0-9_.-]+/[a-z0-9_.-]+'])]
    #[IsGrantedAccess]
    public function info(string $packageName): Response
    {
        $package = $this->packageRepository->findOneBy(['name' => $packageName]);
        $version = $package->getLatestVersion();

        return $this->versionInfo($packageName, $version->getNormalizedVersion());
    }

    #[Route('/packages/{packageName}/v/{packageVersion}', name: 'dashboard_packages_version_info', requirements: ['packageName' => '[a-z0-9_.-]+/[a-z0-9_.-]+'])]
    #[IsGrantedAccess]
    public function versionInfo(string $packageName, string $packageVersion): Response
    {
        $package = $this->packageRepository->findOneBy(['name' => $packageName]);
        $version = $package->getVersion((new VersionParser())->normalize($packageVersion));

        $dependentCount = $this->entityManager->getRepository(PackageRequireLink::class)->count(['linkedPackageName' => $package->getName()]);
        $implementationCount = $this->entityManager->getRepository(PackageProvideLink::class)->count(['linkedPackageName' => $package->getName(), 'implementation' => true]);
        $providerCount = $this->entityManager->getRepository(PackageProvideLink::class)->count(['linkedPackageName' => $package->getName(), 'implementation' => false]);
        $suggesterCount = $this->entityManager->getRepository(PackageSuggestLink::class)->count(['linkedPackageName' => $package->getName()]);

        return $this->render('dashboard/packages/package_info.html.twig', [
            'package' => $package,
            'version' => $version,

            'dependentCount' => $dependentCount,
            'implementationCount' => $implementationCount,
            'providerCount' => $providerCount,
            'suggesterCount' => $suggesterCount,
        ]);
    }

    #[Route('/packages/{packageName}/versions', name: 'dashboard_packages_versions', requirements: ['packageName' => '[a-z0-9_.-]+/[a-z0-9_.-]+'])]
    #[IsGrantedAccess]
    public function versions(string $packageName): Response
    {
        $package = $this->packageRepository->findOneBy(['name' => $packageName]);
        $versions = $package->getVersions()->toArray();

        usort($versions, Package::class . '::sortVersions');

        return $this->render('dashboard/packages/package_versions.html.twig', [
            'package' => $package,
            'versions' => $versions,
        ]);
    }

    #[Route('/packages/{packageName}/dependents', name: 'dashboard_packages_dependents', requirements: ['packageName' => '[a-z0-9_.-]+/[a-z0-9_.-]+'])]
    #[IsGrantedAccess]
    public function dependents(Request $request, string $packageName): Response
    {
        $package = $this->packageRepository->findOneBy(['name' => $packageName]);

        return $this->packageLinks($request, $package, PackageRequireLink::class, 'Dependents');
    }

    #[Route('/packages/{packageName}/implementations', name: 'dashboard_packages_implementations', requirements: ['packageName' => '[a-z0-9_.-]+/[a-z0-9_.-]+'])]
    #[IsGrantedAccess]
    public function implementations(Request $request, string $packageName): Response
    {
        $package = $this->packageRepository->findOneBy(['name' => $packageName]);

        $providerRepository = $this->entityManager->getRepository(PackageProvideLink::class);
        $queryBuilder = $providerRepository->createQueryBuilder('provider');
        $queryBuilder
            ->leftJoin('provider.package', 'package')
            ->andWhere('provider.linkedPackageName = :packageName')
            ->andWhere('provider.implementation = true')
            ->setParameter('packageName', $package->getName());

        return $this->packageLinks($request, $package, PackageProvideLink::class, 'Implementations', queryBuilder: $queryBuilder);
    }

    #[Route('/packages/{packageName}/providers', name: 'dashboard_packages_providers', requirements: ['packageName' => '[a-z0-9_.-]+/[a-z0-9_.-]+'])]
    #[IsGrantedAccess]
    public function providers(Request $request, string $packageName): Response
    {
        $package = $this->packageRepository->findOneBy(['name' => $packageName]);

        $providerRepository = $this->entityManager->getRepository(PackageProvideLink::class);
        $queryBuilder = $providerRepository->createQueryBuilder('provider');
        $queryBuilder
            ->leftJoin('provider.package', 'package')
            ->andWhere('provider.linkedPackageName = :packageName')
            ->andWhere('provider.implementation = false')
            ->setParameter('packageName', $package->getName());

        return $this->packageLinks($request, $package, PackageProvideLink::class, 'Providers', queryBuilder: $queryBuilder);
    }

    #[Route('/packages/{packageName}/suggesters', name: 'dashboard_packages_suggesters', requirements: ['packageName' => '[a-z0-9_.-]+/[a-z0-9_.-]+'])]
    #[IsGrantedAccess]
    public function suggesters(Request $request, string $packageName): Response
    {
        $package = $this->packageRepository->findOneBy(['name' => $packageName]);

        return $this->packageLinks($request, $package, PackageSuggestLink::class, 'Suggesters');
    }

    private function packageLinks(Request $request, Package $package, string $linkClass, string $title, ?QueryBuilder $queryBuilder = null): Response
    {
        if (!$queryBuilder) {
            $dependentRepository = $this->entityManager->getRepository($linkClass);
            $queryBuilder = $dependentRepository->createQueryBuilder('link');
            $queryBuilder
                ->leftJoin('link.package', 'package')
                ->andWhere('link.linkedPackageName = :packageName')
                ->setParameter('packageName', $package->getName());
        }

        $paginator = PackagePaginator::fromRequest($request, $queryBuilder, $this->container->get('router'));
        $packageLinks = $paginator->getResults();

        return $this->render('dashboard/packages/package_links.html.twig', [
            'package' => $package,
            'packageLinks' => $packageLinks,
            'paginator' => $paginator,
            'title' => $title,
        ]);
    }

    #[Route('/packages/{packageName}/statistics', name: 'dashboard_packages_statistics', requirements: ['packageName' => '[a-z0-9_.-]+/[a-z0-9_.-]+'])]
    #[IsGrantedAccess]
    public function statistics(string $packageName): Response
    {
        $package = $this->packageRepository->findOneBy(['name' => $packageName]);

        $versionInstallationsData = [];

        foreach ($package->getVersions() as $version) {
            $majorVersion = $version->getMajorVersion();

            $versionInstallationsData[$majorVersion] ??= [];

            foreach ($version->getInstallations()->getData() as $key => $installations) {
                $versionInstallationsData[$majorVersion][$key] ??= 0;
                $versionInstallationsData[$majorVersion][$key] += $installations;
            }
        }

        $today = new \DateTimeImmutable();
        $todayKey = $today->format('Ymd');
        $installationsToday = $package->getInstallations()->getData()[$todayKey] ?? 0;

        $installationsLast30Days = 0;
        $date = new \DateTimeImmutable('-30 days');

        while ($date <= $today) {
            $dateKey = $date->format('Ymd');
            $installationsLast30Days += $package->getInstallations()->getData()[$dateKey] ?? 0;

            $date = $date->modify('+1 day');
        }

        return $this->render('dashboard/packages/package_statistics.html.twig', [
            'package' => $package,
            'versionInstallationsData' => $versionInstallationsData,
            'installationsTotal' => $package->getInstallations()->getTotal(),
            'installationsLast30Days' => $installationsLast30Days,
            'installationsToday' => $installationsToday,
        ]);
    }
}
