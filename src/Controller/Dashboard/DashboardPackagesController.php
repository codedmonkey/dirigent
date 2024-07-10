<?php

namespace CodedMonkey\Conductor\Controller\Dashboard;

use CodedMonkey\Conductor\Doctrine\Entity\Package;
use CodedMonkey\Conductor\Doctrine\Entity\Version;
use CodedMonkey\Conductor\Doctrine\Repository\PackageRepository;
use CodedMonkey\Conductor\Doctrine\Repository\VersionRepository;
use CodedMonkey\Conductor\Form\PackageAddMirroringType;
use CodedMonkey\Conductor\Form\PackageAddVcsType;
use CodedMonkey\Conductor\Package\PackageMetadataResolver;
use Doctrine\ORM\EntityManagerInterface;
use EasyCorp\Bundle\EasyAdminBundle\Router\AdminUrlGenerator;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

class DashboardPackagesController extends AbstractController
{
    public function __construct(
        private readonly EntityManagerInterface $entityManager,
        private readonly PackageRepository $packageRepository,
        private readonly PackageMetadataResolver $metadataResolver,
        private readonly VersionRepository $versionRepository,
        private readonly AdminUrlGenerator $adminUrlGenerator,
    ) {
    }

    #[Route('/dashboard/packages', name: 'dashboard_packages')]
    public function list(): Response
    {
        $packages = $this->packageRepository->findBy([], ['name' => 'ASC']);

        return $this->render('dashboard/packages/list.html.twig', [
            'packages' => $packages,
        ]);
    }

    #[Route('/dashboard/packages/info/{packageName}/{packageVersion}', name: 'dashboard_packages_info', requirements: ['packageName' => '[a-z0-9_.-]+/[a-z0-9_.-]+'])]
    public function info(string $packageName, ?string $packageVersion = null): Response
    {
        $package = $this->packageRepository->findOneBy(['name' => $packageName]);

        $versions = $package->getVersions()->toArray();

        usort($versions, Package::class.'::sortVersions');

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
    public function versions(string $packageName): Response
    {
        $package = $this->packageRepository->findOneBy(['name' => $packageName]);
        $versions = $package->getVersions()->toArray();

        usort($versions, Package::class.'::sortVersions');

        return $this->render('dashboard/packages/package_versions.html.twig', [
            'package' => $package,
            'versions' => $versions,
        ]);
    }

    #[Route('/dashboard/packages/add-mirroring', name: 'dashboard_packages_add_mirroring')]
    public function addMirror(Request $request): Response
    {
        $this->denyAccessUnlessGranted('ROLE_ADMIN');

        $form = $this->createForm(PackageAddMirroringType::class);

        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $formData = $form->getData();

            $registry = $formData['registry'];

            $packageNames = explode(PHP_EOL, $formData['packages']);
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
                $package->setMirrorRegistry($formData['registry']);

                $this->metadataResolver->resolve($package);

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
    public function addVcsRepository(Request $request): Response
    {
        $this->denyAccessUnlessGranted('ROLE_ADMIN');

        $form = $this->createForm(PackageAddVcsType::class);

        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $formData = $form->getData();

            $package = new Package();
            $package->setRepositoryCredentials($formData['repositoryCredentials']);
            $package->setRepositoryUrl($formData['repositoryUrl']);

            $this->metadataResolver->resolve($package);

            $this->entityManager->flush();

            return $this->redirect($this->adminUrlGenerator->setRoute('dashboard_packages')->generateUrl());
        }

        return $this->render('dashboard/packages/add_vcs.html.twig', [
            'form' => $form,
        ]);
    }

    #[Route('/dashboard/packages/update/{packageName}', name: 'dashboard_packages_update', requirements: ['packageName' => '[a-z0-9_.-]+/[a-z0-9_.-]+'])]
    public function update(string $packageName): Response
    {
        $package = $this->packageRepository->findOneBy(['name' => $packageName]);

        $this->metadataResolver->resolve($package);

        $this->entityManager->flush();

        return $this->redirect($this->adminUrlGenerator->setRoute('dashboard_packages_info', ['packageName' => $package->getName()])->generateUrl());
    }

    #[Route('/dashboard/packages/delete/{packageName}', name: 'dashboard_packages_delete', requirements: ['packageName' => '[a-z0-9_.-]+/[a-z0-9_.-]+'])]
    public function delete(string $packageName): Response
    {
        $package = $this->packageRepository->findOneBy(['name' => $packageName]);

        foreach ($package->getVersions() as $version) {
            $this->entityManager->remove($version);
        }

        $this->entityManager->remove($package);
        $this->entityManager->flush();

        return $this->redirect($this->adminUrlGenerator->setRoute('dashboard_packages')->generateUrl());
    }
}
