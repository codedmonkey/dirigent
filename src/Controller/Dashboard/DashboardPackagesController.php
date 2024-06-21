<?php

namespace CodedMonkey\Conductor\Controller\Dashboard;

use CodedMonkey\Conductor\Doctrine\Entity\Package;
use CodedMonkey\Conductor\Doctrine\Entity\Version;
use CodedMonkey\Conductor\Doctrine\Repository\PackageRepository;
use CodedMonkey\Conductor\Doctrine\Repository\VersionRepository;
use CodedMonkey\Conductor\Form\PackageAddRegistryType;
use CodedMonkey\Conductor\Package\PackageMetadataResolver;
use CodedMonkey\Conductor\Registry\RegistryClientManager;
use Composer\Package\Loader\ArrayLoader;
use Doctrine\ORM\EntityManagerInterface;
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
        private readonly RegistryClientManager $registryClientManager,
        private readonly VersionRepository $versionRepository,
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

        if (null !== $packageVersion) {
            $version = $this->versionRepository->findOneBy(['package' => $package, 'version' => $packageVersion]);
        } else {
            $version = $this->versionRepository->findOneBy(['package' => $package, 'defaultBranch' => true]);
        }

        dump($version);
        dump($this->versionRepository->findBy(['package' => $package]));

        return $this->render('dashboard/packages/package_info.html.twig', [
            'package' => $package,
            'version' => $version,
        ]);
    }

    #[Route('/dashboard/packages/versions/{packageName}', name: 'dashboard_packages_versions', requirements: ['packageName' => '[a-z0-9_.-]+/[a-z0-9_.-]+'])]
    public function versions(string $packageName): Response
    {
        $package = $this->packageRepository->findOneBy(['name' => $packageName]);
        $versions = $package->getVersions()->toArray();

        $versionsMap = array_combine(
            array_map(fn (Version $version) => $version->getNormalizedVersion(), $versions),
            array_map(fn (Version $version) => $version->getVersion(), $versions),
        );

        return $this->render('dashboard/packages/package_versions.html.twig', [
            'package' => $package,
            'versionsMap' => $versionsMap,
        ]);
    }

    #[Route('/dashboard/packages/add-registry', name: 'dashboard_packages_add_registry')]
    public function addFromRegistry(Request $request): Response
    {
        $form = $this->createForm(PackageAddRegistryType::class);

        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $formData = $form->getData();

            $registry = $formData['registry'];
            $registryClient = $this->registryClientManager->getClient($registry);

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

                if (!$registryClient->packageExists($packageName)) {
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

            return $this->render('dashboard/packages/add_registry_results.html.twig', [
                'results' => $results,
            ]);
        }

        return $this->render('dashboard/packages/add_registry.html.twig', [
            'form' => $form,
        ]);
    }
}
