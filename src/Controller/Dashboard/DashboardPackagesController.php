<?php

namespace CodedMonkey\Conductor\Controller\Dashboard;

use CodedMonkey\Conductor\Attribute\IsGrantedAccess;
use CodedMonkey\Conductor\Doctrine\Entity\Package;
use CodedMonkey\Conductor\Doctrine\Repository\PackageRepository;
use CodedMonkey\Conductor\Doctrine\Repository\VersionRepository;
use CodedMonkey\Conductor\EasyAdmin\PackagePaginator;
use CodedMonkey\Conductor\Form\PackageAddMirroringType;
use CodedMonkey\Conductor\Form\PackageAddVcsType;
use CodedMonkey\Conductor\Message\UpdatePackage;
use CodedMonkey\Conductor\Package\PackageMetadataResolver;
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
        private readonly VersionRepository $versionRepository,
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

        usort($versions, Package::class . '::sortVersions');

        // load the default branch version as it is used to display the latest available source.* and homepage info
        $latestVersion = reset($versions);
        foreach ($versions as $v) {
            if ($v->isDefaultBranch()) {
                $latestVersion = $v;
                break;
            }
        }

        if (null !== $packageVersion) {
            $version = $this->versionRepository->findOneBy(['package' => $package, 'version' => $packageVersion]);
        } else {
            $version = $latestVersion;
            foreach ($versions as $candidate) {
                if (!$candidate->isDevelopment()) {
                    $version = $candidate;
                    break;
                }
            }
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

    #[Route('/dashboard/packages/add-mirroring', name: 'dashboard_packages_add_mirroring')]
    #[IsGranted('ROLE_ADMIN')]
    public function addMirror(Request $request): Response
    {
        $form = $this->createForm(PackageAddMirroringType::class);

        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $registry = $form->get('registry')->getData();

            $packageNames = explode(PHP_EOL, $form->get('packages')->getData());
            $packageNames = array_map('trim', $packageNames);

            $results = [];

            foreach ($packageNames as $packageName) {
                if (null !== $this->packageRepository->findOneBy(['name' => $packageName])) {
                    $results[] = [
                        'error' => true,
                        'message' => "The package $packageName already exists and was skipped",
                    ];

                    continue;
                }

                if (!$this->metadataResolver->provides($packageName, $registry)) {
                    $results[] = [
                        'error' => true,
                        'message' => "The package $packageName could not be found and was skipped",
                    ];

                    continue;
                }

                $package = new Package();
                $package->setName($packageName);
                $package->setMirrorRegistry($registry);

                $this->packageRepository->save($package, true);

                $this->messenger->dispatch(new UpdatePackage($package->getId()));

                $results[] = [
                    'error' => false,
                    'message' => "The package $packageName was created successfully",
                ];

                $this->entityManager->flush();
            }

            return $this->render('dashboard/packages/add_mirroring_results.html.twig', [
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
        $form = $this->createForm(PackageAddVcsType::class);

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

        foreach ($package->getVersions() as $version) {
            $this->entityManager->remove($version);
        }

        $this->packageRepository->remove($package, true);

        return $this->redirect($this->adminUrlGenerator->setRoute('dashboard_packages')->generateUrl());
    }
}
