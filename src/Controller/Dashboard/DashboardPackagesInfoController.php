<?php

namespace CodedMonkey\Dirigent\Controller\Dashboard;

use CodedMonkey\Dirigent\Attribute\IsGrantedAccess;
use CodedMonkey\Dirigent\Attribute\MapPackage;
use CodedMonkey\Dirigent\Doctrine\Entity\Package;
use CodedMonkey\Dirigent\Doctrine\Entity\PackageProvideLink;
use CodedMonkey\Dirigent\Doctrine\Entity\PackageRequireLink;
use CodedMonkey\Dirigent\Doctrine\Entity\PackageSuggestLink;
use CodedMonkey\Dirigent\Doctrine\Entity\Version;
use CodedMonkey\Dirigent\EasyAdmin\PackagePaginator;
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
    ) {
    }

    /**
     * Show info for the latest available version of the package.
     */
    #[Route('/packages/{package}', name: 'dashboard_packages_info', requirements: ['package' => MapPackage::PACKAGE_REGEX])]
    #[IsGrantedAccess]
    public function info(#[MapPackage] Package $package): Response
    {
        $version = $package->getLatestVersion();

        if (!$version) {
            return $this->redirectToRoute('dashboard_packages_versions', ['package' => $package->getName()]);
        }

        return $this->versionInfo($package, $version);
    }

    #[Route('/packages/{package}/versions/{version}', name: 'dashboard_packages_version_info', requirements: ['package' => MapPackage::PACKAGE_REGEX, 'version' => '.*'])]
    #[IsGrantedAccess]
    public function versionInfo(#[MapPackage] Package $package, #[MapPackage] Version $version): Response
    {
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

    #[Route('/packages/{package}/versions', name: 'dashboard_packages_versions', requirements: ['package' => MapPackage::PACKAGE_REGEX])]
    #[IsGrantedAccess]
    public function versions(#[MapPackage] Package $package): Response
    {
        return $this->render('dashboard/packages/package_versions.html.twig', [
            'package' => $package,
        ]);
    }

    #[Route('/packages/{package}/dependents', name: 'dashboard_packages_dependents', requirements: ['package' => MapPackage::PACKAGE_REGEX])]
    #[IsGrantedAccess]
    public function dependents(Request $request, #[MapPackage] Package $package): Response
    {
        return $this->packageLinks($request, $package, PackageRequireLink::class, 'Dependents');
    }

    #[Route('/packages/{package}/implementations', name: 'dashboard_packages_implementations', requirements: ['package' => MapPackage::PACKAGE_REGEX])]
    #[IsGrantedAccess]
    public function implementations(Request $request, #[MapPackage] Package $package): Response
    {
        $providerRepository = $this->entityManager->getRepository(PackageProvideLink::class);
        $queryBuilder = $providerRepository->createQueryBuilder('provider');
        $queryBuilder
            ->leftJoin('provider.package', 'package')
            ->andWhere('provider.linkedPackageName = :packageName')
            ->andWhere('provider.implementation = true')
            ->setParameter('packageName', $package->getName());

        return $this->packageLinks($request, $package, PackageProvideLink::class, 'Implementations', queryBuilder: $queryBuilder);
    }

    #[Route('/packages/{package}/providers', name: 'dashboard_packages_providers', requirements: ['package' => MapPackage::PACKAGE_REGEX])]
    #[IsGrantedAccess]
    public function providers(Request $request, #[MapPackage] Package $package): Response
    {
        $providerRepository = $this->entityManager->getRepository(PackageProvideLink::class);
        $queryBuilder = $providerRepository->createQueryBuilder('provider');
        $queryBuilder
            ->leftJoin('provider.package', 'package')
            ->andWhere('provider.linkedPackageName = :packageName')
            ->andWhere('provider.implementation = false')
            ->setParameter('packageName', $package->getName());

        return $this->packageLinks($request, $package, PackageProvideLink::class, 'Providers', queryBuilder: $queryBuilder);
    }

    #[Route('/packages/{package}/suggesters', name: 'dashboard_packages_suggesters', requirements: ['package' => MapPackage::PACKAGE_REGEX])]
    #[IsGrantedAccess]
    public function suggesters(Request $request, #[MapPackage] Package $package): Response
    {
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

    #[Route('/packages/{package}/statistics', name: 'dashboard_packages_statistics', requirements: ['package' => MapPackage::PACKAGE_REGEX])]
    #[IsGrantedAccess]
    public function statistics(#[MapPackage] Package $package): Response
    {
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
