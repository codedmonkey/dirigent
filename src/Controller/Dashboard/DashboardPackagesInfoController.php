<?php

namespace CodedMonkey\Dirigent\Controller\Dashboard;

use CodedMonkey\Dirigent\Attribute\IsGrantedAccess;
use CodedMonkey\Dirigent\Doctrine\Entity\Package;
use CodedMonkey\Dirigent\Doctrine\Repository\PackageRepository;
use Composer\Semver\VersionParser;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

class DashboardPackagesInfoController extends AbstractController
{
    public function __construct(
        private readonly PackageRepository $packageRepository,
    ) {
    }

    #[Route('/packages/{packageName}', name: 'dashboard_packages_info', requirements: ['packageName' => '[a-z0-9_.-]+/[a-z0-9_.-]+'])]
    #[IsGrantedAccess]
    public function info(string $packageName): Response
    {
        $package = $this->packageRepository->findOneBy(['name' => $packageName]);
        $version = $package->getLatestVersion();

        return $this->render('dashboard/packages/package_info.html.twig', [
            'package' => $package,
            'version' => $version,
        ]);
    }

    #[Route('/packages/{packageName}/v/{packageVersion}', name: 'dashboard_packages_version_info', requirements: ['packageName' => '[a-z0-9_.-]+/[a-z0-9_.-]+'])]
    #[IsGrantedAccess]
    public function versionInfo(string $packageName, string $packageVersion): Response
    {
        $package = $this->packageRepository->findOneBy(['name' => $packageName]);
        $version = $package->getVersion((new VersionParser())->normalize($packageVersion));

        return $this->render('dashboard/packages/package_info.html.twig', [
            'package' => $package,
            'version' => $version,
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
