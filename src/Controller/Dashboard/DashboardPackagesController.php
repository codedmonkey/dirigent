<?php

namespace CodedMonkey\Dirigent\Controller\Dashboard;

use CodedMonkey\Dirigent\Attribute\IsGrantedAccess;
use CodedMonkey\Dirigent\Attribute\MapPackage;
use CodedMonkey\Dirigent\Doctrine\Entity\Package;
use CodedMonkey\Dirigent\Doctrine\Entity\PackageFetchStrategy;
use CodedMonkey\Dirigent\Doctrine\Entity\Registry;
use CodedMonkey\Dirigent\Doctrine\Repository\PackageRepository;
use CodedMonkey\Dirigent\Doctrine\Repository\RegistryRepository;
use CodedMonkey\Dirigent\EasyAdmin\PackagePaginator;
use CodedMonkey\Dirigent\Form\PackageAddMirroringFormType;
use CodedMonkey\Dirigent\Form\PackageAddVcsFormType;
use CodedMonkey\Dirigent\Form\PackageFormType;
use CodedMonkey\Dirigent\Message\UpdatePackage;
use CodedMonkey\Dirigent\Package\PackageMetadataResolver;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Messenger\MessageBusInterface;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use function Symfony\Component\String\u;

class DashboardPackagesController extends AbstractController
{
    public function __construct(
        private readonly EntityManagerInterface $entityManager,
        private readonly PackageRepository $packageRepository,
        private readonly PackageMetadataResolver $metadataResolver,
        private readonly MessageBusInterface $messenger,
    ) {
    }

    #[Route('/packages', name: 'dashboard_packages')]
    #[IsGrantedAccess]
    public function list(Request $request, RegistryRepository $registryRepository): Response
    {
        $queryBuilder = $this->packageRepository->createQueryBuilder('package');
        $filtersActive = false;

        if ($request->query->has('query')) {
            $filtersActive = true;
            $searchQuery = $request->query->getString('query');
            $searchQuery = u($searchQuery)->lower();

            $queryBuilder->andWhere($queryBuilder->expr()->like('package.name', ':query'));
            $queryBuilder->setParameter('query', "%{$searchQuery}%");
        }

        if ($request->query->has('registry')) {
            $registryId = $request->query->getInt('registry');

            if (0 === $registryId) {
                $filtersActive = true;
                $selectedRegistry = 'local';

                $queryBuilder->andWhere($queryBuilder->expr()->isNull('package.mirrorRegistry'));
            } elseif (null !== $selectedRegistry = $registryRepository->find($registryId)) {
                $filtersActive = true;

                $queryBuilder->andWhere($queryBuilder->expr()->eq('package.mirrorRegistry', ':registry'));
                $queryBuilder->setParameter('registry', $selectedRegistry);
            }
        }

        $paginator = PackagePaginator::fromRequest($request, $queryBuilder, $this->container->get('router'));

        $registries = $this->entityManager->getRepository(Registry::class)->findAll();

        return $this->render('dashboard/packages/list.html.twig', [
            'filtersActive' => $filtersActive,
            'paginator' => $paginator,
            'registries' => $registries,
            'searchQuery' => $searchQuery ?? null,
            'selectedRegistry' => $selectedRegistry ?? null,
        ]);
    }

    #[Route('/packages/add-mirroring', name: 'dashboard_packages_add_mirroring')]
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
                if (!preg_match('#' . MapPackage::PACKAGE_REGEX . '#', $packageName)) {
                    $results[] = [
                        'packageName' => $packageName,
                        'registryName' => null,
                        'created' => false,
                        'error' => true,
                        'message' => "The package name $packageName is invalid.",
                    ];

                    continue;
                }

                if (null !== $this->packageRepository->findOneByName($packageName)) {
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

    #[Route('/packages/add-vcs', name: 'dashboard_packages_add_vcs')]
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

            return $this->redirectToRoute('dashboard_packages');
        }

        return $this->render('dashboard/packages/add_vcs.html.twig', [
            'form' => $form,
        ]);
    }

    #[Route('/packages/{package}/edit', name: 'dashboard_packages_edit', requirements: ['package' => MapPackage::PACKAGE_REGEX])]
    #[IsGranted('ROLE_ADMIN')]
    public function edit(Request $request, #[MapPackage] Package $package): Response
    {
        $form = $this->createForm(PackageFormType::class, $package);

        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            /** @var Package $package */
            $package = $form->getData();
            $this->packageRepository->save($package, true);

            $this->messenger->dispatch(new UpdatePackage($package->getId()));

            return $this->redirectToRoute('dashboard_packages_info', ['package' => $package->getName()]);
        }

        return $this->render('dashboard/packages/edit.html.twig', [
            'package' => $package,
            'form' => $form,
        ]);
    }

    #[Route('/packages/{package}/update', name: 'dashboard_packages_update', requirements: ['package' => MapPackage::PACKAGE_REGEX])]
    #[IsGranted('ROLE_ADMIN')]
    public function update(#[MapPackage] Package $package): Response
    {
        $this->messenger->dispatch(new UpdatePackage($package->getId(), forceRefresh: true));

        return $this->redirectToRoute('dashboard_packages_info', ['package' => $package->getName()]);
    }

    #[Route('/packages/{package}/delete', name: 'dashboard_packages_delete', requirements: ['package' => MapPackage::PACKAGE_REGEX])]
    #[IsGranted('ROLE_ADMIN')]
    public function delete(#[MapPackage] Package $package): Response
    {
        $this->packageRepository->remove($package, true);

        return $this->redirectToRoute('dashboard_packages');
    }
}
