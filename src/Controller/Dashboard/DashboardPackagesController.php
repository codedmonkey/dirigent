<?php

namespace CodedMonkey\Dirigent\Controller\Dashboard;

use CodedMonkey\Dirigent\Attribute\IsGrantedAccess;
use CodedMonkey\Dirigent\Doctrine\Entity\Package;
use CodedMonkey\Dirigent\Doctrine\Entity\PackageFetchStrategy;
use CodedMonkey\Dirigent\Doctrine\Repository\PackageRepository;
use CodedMonkey\Dirigent\EasyAdmin\PackagePaginator;
use CodedMonkey\Dirigent\Form\PackageAddMirroringFormType;
use CodedMonkey\Dirigent\Form\PackageAddVcsFormType;
use CodedMonkey\Dirigent\Form\PackageFormType;
use CodedMonkey\Dirigent\Message\UpdatePackage;
use CodedMonkey\Dirigent\Package\PackageMetadataResolver;
use Composer\Semver\VersionParser;
use Doctrine\ORM\EntityManagerInterface;
use EasyCorp\Bundle\EasyAdminBundle\Dto\PaginatorDto;
use EasyCorp\Bundle\EasyAdminBundle\Router\AdminUrlGenerator;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Messenger\MessageBusInterface;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

class DashboardPackagesController extends AbstractController
{
    public function __construct(
        private readonly EntityManagerInterface $entityManager,
        private readonly PackageRepository $packageRepository,
        private readonly PackageMetadataResolver $metadataResolver,
        private readonly MessageBusInterface $messenger,
        private readonly AdminUrlGenerator $adminUrlGenerator,
    ) {
    }

    #[Route('/dashboard/packages', name: 'dashboard_packages')]
    #[IsGrantedAccess]
    public function list(Request $request): Response
    {
        $queryBuilder = $this->packageRepository->createQueryBuilder('package');
        $queryBuilder->addOrderBy('package.name', 'ASC');

        if (null !== $query = $request->query->get('query')) {
            $queryBuilder->andWhere($queryBuilder->expr()->like('package.name', ':query'));
            $queryBuilder->setParameter('query', "%{$query}%");
        }

        $paginatorDto = new PaginatorDto(20, 3, 1, true, null);
        $paginatorDto->setPageNumber($request->query->getInt('page', 1));
        $paginator = (new PackagePaginator($this->adminUrlGenerator))->paginate($paginatorDto, $queryBuilder);
        $packages = $paginator->getResults();

        return $this->render('dashboard/packages/list.html.twig', [
            'packages' => $packages,
            'paginator' => $paginator,
        ]);
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

        return $this->render('dashboard/packages/package_info.html.twig', [
            'package' => $package,
            'latestVersion' => $latestVersion,
            'version' => $version,
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

    #[Route('/dashboard/packages/add-mirroring', name: 'dashboard_packages_add_mirroring')]
    #[IsGranted('ROLE_ADMIN')]
    public function addMirroring(Request $request): Response
    {
        $form = $this->createForm(PackageAddMirroringFormType::class);

        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $registry = $form->get('registry')->getData();

            $packageNamesInput = $form->get('packages')->getData();
            $packageNames = preg_split('#(\s|,)+#', $packageNamesInput);

            $results = [];

            foreach ($packageNames as $packageName) {
                if (!preg_match('#[a-z0-9_.-]+/[a-z0-9_.-]+#', $packageName)) {
                    $results[] = [
                        'packageName' => $packageName,
                        'registryName' => null,
                        'created' => false,
                        'error' => true,
                        'message' => "The package name $packageName is invalid.",
                    ];

                    continue;
                }

                if (null !== $this->packageRepository->findOneBy(['name' => $packageName])) {
                    $results[] = [
                        'packageName' => $packageName,
                        'registryName' => null,
                        'created' => false,
                        'error' => false,
                        'message' => "The package $packageName already exists and was skipped.",
                    ];

                    continue;
                }

                if (!$this->metadataResolver->provides($packageName, $registry)) {
                    $results[] = [
                        'packageName' => $packageName,
                        'registryName' => $registry->getName(),
                        'created' => false,
                        'error' => true,
                        'message' => "The package $packageName could not be found and was skipped.",
                    ];

                    continue;
                }

                $package = new Package();
                $package->setName($packageName);
                $package->setMirrorRegistry($registry);
                $package->setFetchStrategy(PackageFetchStrategy::Mirror);

                $this->packageRepository->save($package, true);

                $this->messenger->dispatch(new UpdatePackage($package->getId()));

                $results[] = [
                    'packageName' => $packageName,
                    'registryName' => $registry->getName(),
                    'created' => true,
                    'error' => false,
                    'message' => "The package $packageName was created successfully.",
                ];

                $this->entityManager->flush();
            }

            return $this->json([
                'results' => $results,
            ]);
        }

        return $this->render('dashboard/packages/add_mirroring.html.twig', [
            'form' => $form,
        ]);
    }

    #[Route('/dashboard/packages/add-vcs', name: 'dashboard_packages_add_vcs')]
    #[IsGranted('ROLE_ADMIN')]
    public function addVcsRepository(Request $request): Response
    {
        $form = $this->createForm(PackageAddVcsFormType::class);

        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            /** @var Package $package */
            $package = $form->getData();
            $this->packageRepository->save($package, true);

            $this->messenger->dispatch(new UpdatePackage($package->getId()));

            return $this->redirect($this->adminUrlGenerator->setRoute('dashboard_packages')->generateUrl());
        }

        return $this->render('dashboard/packages/add_vcs.html.twig', [
            'form' => $form,
        ]);
    }

    #[Route('/dashboard/packages/edit/{packageName}', name: 'dashboard_packages_edit', requirements: ['packageName' => '[a-z0-9_.-]+/[a-z0-9_.-]+'])]
    #[IsGranted('ROLE_ADMIN')]
    public function edit(Request $request, string $packageName): Response
    {
        $package = $this->packageRepository->findOneByName($packageName);

        $form = $this->createForm(PackageFormType::class, $package);

        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            /** @var Package $package */
            $package = $form->getData();
            $this->packageRepository->save($package, true);

            $this->messenger->dispatch(new UpdatePackage($package->getId()));

            return $this->redirect($this->adminUrlGenerator->setRoute('dashboard_packages_info', ['packageName' => $package->getName()])->generateUrl());
        }

        return $this->render('dashboard/packages/package_edit.html.twig', [
            'package' => $package,
            'form' => $form,
        ]);
    }

    #[Route('/dashboard/packages/update/{packageName}', name: 'dashboard_packages_update', requirements: ['packageName' => '[a-z0-9_.-]+/[a-z0-9_.-]+'])]
    #[IsGranted('ROLE_ADMIN')]
    public function update(string $packageName): Response
    {
        $package = $this->packageRepository->findOneByName($packageName);

        $this->messenger->dispatch(new UpdatePackage($package->getId(), forceRefresh: true));

        return $this->redirect($this->adminUrlGenerator->setRoute('dashboard_packages_info', ['packageName' => $package->getName()])->generateUrl());
    }

    #[Route('/dashboard/packages/delete/{packageName}', name: 'dashboard_packages_delete', requirements: ['packageName' => '[a-z0-9_.-]+/[a-z0-9_.-]+'])]
    #[IsGranted('ROLE_ADMIN')]
    public function delete(string $packageName): Response
    {
        $package = $this->packageRepository->findOneByName($packageName);

        $this->packageRepository->remove($package, true);

        return $this->redirect($this->adminUrlGenerator->setRoute('dashboard_packages')->generateUrl());
    }
}
