<?php

namespace CodedMonkey\Dirigent\Controller\Dashboard;

use CodedMonkey\Dirigent\Attribute\IsGrantedAccess;
use CodedMonkey\Dirigent\Doctrine\Entity\Dependent;
use CodedMonkey\Dirigent\Doctrine\Entity\Package;
use CodedMonkey\Dirigent\Doctrine\Entity\Provider;
use CodedMonkey\Dirigent\Doctrine\Entity\Suggester;
use CodedMonkey\Dirigent\Doctrine\Repository\PackageRepository;
use CodedMonkey\Dirigent\EasyAdmin\PackagePaginator;
use Composer\Semver\VersionParser;
use Doctrine\ORM\EntityManagerInterface;
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

    #[Route('/dashboard/packages/info/{packageName}/{packageVersion}', name: 'dashboard_packages_info', requirements: ['packageName' => '[a-z0-9_.-]+/[a-z0-9_.-]+'])]
    #[IsGrantedAccess]
    public function info(string $packageName, ?string $packageVersion = null): Response
    {
        $package = $this->packageRepository->findOneBy(['name' => $packageName]);

        $versions = $package->getVersions()->toArray();
        $latestVersion = $package->getDefaultVersion();

        usort($versions, Package::class . '::sortVersions');

        if (null !== $packageVersion) {
            $version = $package->getVersion((new VersionParser())->normalize($packageVersion));
        } else {
            $version = $package->getLatestVersion();
        }

        $dependentCount = $this->entityManager->getRepository(Dependent::class)->count(['dependentPackageName' => $package->getName()]);
        $implementationCount = $this->entityManager->getRepository(Provider::class)->count(['providedPackageName' => $package->getName(), 'implementation' => true]);
        $providerCount = $this->entityManager->getRepository(Provider::class)->count(['providedPackageName' => $package->getName(), 'implementation' => false]);
        $suggesterCount = $this->entityManager->getRepository(Suggester::class)->count(['suggestedPackageName' => $package->getName()]);

        return $this->render('dashboard/packages/package_info.html.twig', [
            'package' => $package,
            'latestVersion' => $latestVersion,
            'version' => $version,

            'dependentCount' => $dependentCount,
            'implementationCount' => $implementationCount,
            'providerCount' => $providerCount,
            'suggesterCount' => $suggesterCount,
        ]);
    }

    #[Route('/dashboard/packages/versions/{packageName}', name: 'dashboard_packages_versions', requirements: ['packageName' => '[a-z0-9_.-]+/[a-z0-9_.-]+'])]
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

    #[Route('/dashboard/packages/dependents/{packageName}', name: 'dashboard_packages_dependents', requirements: ['packageName' => '[a-z0-9_.-]+/[a-z0-9_.-]+'])]
    #[IsGrantedAccess]
    public function dependents(Request $request, string $packageName): Response
    {
        $package = $this->packageRepository->findOneBy(['name' => $packageName]);

        $dependentRepository = $this->entityManager->getRepository(Dependent::class);
        $queryBuilder = $dependentRepository->createQueryBuilder('dependent');
        $queryBuilder
            ->leftJoin('dependent.package', 'package')
            ->andWhere('dependent.dependentPackageName = :packageName')
            ->setParameter('packageName', $package->getName())
            ->addOrderBy('package.name', 'ASC');

        $paginator = PackagePaginator::fromRequest($request, $queryBuilder, $this->container->get('router'));
        $dependents = $paginator->getResults();

        return $this->render('dashboard/packages/package_dependents.html.twig', [
            'package' => $package,
            'dependents' => $dependents,
            'paginator' => $paginator,
        ]);
    }

    #[Route('/dashboard/packages/implementations/{packageName}', name: 'dashboard_packages_implementations', requirements: ['packageName' => '[a-z0-9_.-]+/[a-z0-9_.-]+'])]
    #[IsGrantedAccess]
    public function implementations(Request $request, string $packageName): Response
    {
        $package = $this->packageRepository->findOneBy(['name' => $packageName]);

        $providerRepository = $this->entityManager->getRepository(Provider::class);
        $queryBuilder = $providerRepository->createQueryBuilder('provider');
        $queryBuilder
            ->leftJoin('provider.package', 'package')
            ->andWhere('provider.providedPackageName = :packageName')
            ->andWhere('provider.implementation = true')
            ->setParameter('packageName', $package->getName())
            ->addOrderBy('package.name', 'ASC');

        $paginator = PackagePaginator::fromRequest($request, $queryBuilder, $this->container->get('router'));
        $providers = $paginator->getResults();

        return $this->render('dashboard/packages/package_implementations.html.twig', [
            'package' => $package,
            'providers' => $providers,
            'paginator' => $paginator,
        ]);
    }

    #[Route('/dashboard/packages/providers/{packageName}', name: 'dashboard_packages_providers', requirements: ['packageName' => '[a-z0-9_.-]+/[a-z0-9_.-]+'])]
    #[IsGrantedAccess]
    public function providers(Request $request, string $packageName): Response
    {
        $package = $this->packageRepository->findOneBy(['name' => $packageName]);

        $providerRepository = $this->entityManager->getRepository(Provider::class);
        $queryBuilder = $providerRepository->createQueryBuilder('provider');
        $queryBuilder
            ->leftJoin('provider.package', 'package')
            ->andWhere('provider.providedPackageName = :packageName')
            ->andWhere('provider.implementation = false')
            ->setParameter('packageName', $package->getName())
            ->addOrderBy('package.name', 'ASC');

        $paginator = PackagePaginator::fromRequest($request, $queryBuilder, $this->container->get('router'));
        $providers = $paginator->getResults();

        return $this->render('dashboard/packages/package_providers.html.twig', [
            'package' => $package,
            'providers' => $providers,
            'paginator' => $paginator,
        ]);
    }

    #[Route('/dashboard/packages/suggesters/{packageName}', name: 'dashboard_packages_suggesters', requirements: ['packageName' => '[a-z0-9_.-]+/[a-z0-9_.-]+'])]
    #[IsGrantedAccess]
    public function suggesters(Request $request, string $packageName): Response
    {
        $package = $this->packageRepository->findOneBy(['name' => $packageName]);

        $suggesterRepository = $this->entityManager->getRepository(Suggester::class);
        $queryBuilder = $suggesterRepository->createQueryBuilder('suggester');
        $queryBuilder
            ->leftJoin('suggester.package', 'package')
            ->andWhere('suggester.suggestedPackageName = :packageName')
            ->setParameter('packageName', $package->getName())
            ->addOrderBy('package.name', 'ASC');

        $paginator = PackagePaginator::fromRequest($request, $queryBuilder, $this->container->get('router'));
        $suggesters = $paginator->getResults();

        return $this->render('dashboard/packages/package_suggesters.html.twig', [
            'package' => $package,
            'suggesters' => $suggesters,
            'paginator' => $paginator,
        ]);
    }

    #[Route('/dashboard/packages/statistics/{packageName}', name: 'dashboard_packages_statistics', requirements: ['packageName' => '[a-z0-9_.-]+/[a-z0-9_.-]+'])]
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
