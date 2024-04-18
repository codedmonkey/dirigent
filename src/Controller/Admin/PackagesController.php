<?php

namespace CodedMonkey\Conductor\Controller\Admin;

use CodedMonkey\Conductor\Doctrine\Entity\Package;
use CodedMonkey\Conductor\Doctrine\Repository\PackageRepository;
use CodedMonkey\Conductor\Form\PackageAddRegistryType;
use CodedMonkey\Conductor\Package\PackageMetadataResolver;
use CodedMonkey\Conductor\Registry\RegistryClientManager;
use Composer\Package\Loader\ArrayLoader;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

class PackagesController extends AbstractController
{
    public function __construct(
        private readonly EntityManagerInterface $entityManager,
        private readonly PackageRepository $packageRepository,
        private readonly PackageMetadataResolver $metadataResolver,
        private readonly RegistryClientManager $registryClientManager,
    ) {
    }

    #[Route('/admin/packages', name: 'admin_packages')]
    public function list(): Response
    {
        $packages = $this->packageRepository->findBy([], ['name' => 'ASC']);

        return $this->render('admin/packages/list.html.twig', [
            'packages' => $packages,
        ]);
    }

    #[Route('/admin/packages/info/{packageName}/{version}', name: 'admin_packages_info', requirements: ['packageName' => '[a-z0-9_.-]+/[a-z0-9_.-]+'])]
    public function info(string $packageName, ?string $version = null): Response
    {
        $package = $this->packageRepository->findOneBy(['name' => $packageName]);
        $metadata = $this->metadataResolver->resolve($package);

        if (null === $version) {
            $version = array_key_first($metadata['versions']);
        }

        $versionMetadata = $metadata['versions'][$version];
        $composerPackage = (new ArrayLoader())->load($versionMetadata);

        return $this->render('admin/packages/package_info.html.twig', [
            'package' => $package,
            'composerPackage' => $composerPackage,
        ]);
    }

    #[Route('/admin/packages/versions/{packageName}', name: 'admin_packages_versions', requirements: ['packageName' => '[a-z0-9_.-]+/[a-z0-9_.-]+'])]
    public function versions(string $packageName): Response
    {
        $package = $this->packageRepository->findOneBy(['name' => $packageName]);

        $metadata = $this->metadataResolver->resolve($package);

        $versions = array_combine(
            array_map(fn (array $vars) => $vars['version_normalized'], $metadata['versions']),
            array_map(fn (array $vars) => $vars['version'], $metadata['versions']),
        );

        return $this->render('admin/packages/package_versions.html.twig', [
            'package' => $package,
            'versions' => $versions,
        ]);
    }

    #[Route('/admin/packages/add-registry', name: 'admin_packages_add_registry')]
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
                $package->name = $packageName;
                $package->mirrorRegistry = $formData['registry'];

                $this->metadataResolver->resolve($package);

                $results[] = [
                    'error' => false,
                    'message' => "The package $packageName was created successfully",
                ];

                $this->packageRepository->save($package);
            }

            $this->entityManager->flush();

            return $this->render('admin/packages/add_registry_results.html.twig', [
                'results' => $results,
            ]);
        }

        return $this->render('admin/packages/add_registry.html.twig', [
            'form' => $form,
        ]);
    }
}
