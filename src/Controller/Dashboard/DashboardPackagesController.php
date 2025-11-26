<?php

declare(strict_types=1);

namespace CodedMonkey\Dirigent\Controller\Dashboard;

use CodedMonkey\Dirigent\Attribute\IsGrantedAccess;
use CodedMonkey\Dirigent\Attribute\MapPackage;
use CodedMonkey\Dirigent\Doctrine\Entity\Package;
use CodedMonkey\Dirigent\Doctrine\Repository\PackageRepository;
use CodedMonkey\Dirigent\EasyAdmin\PackagePaginator;
use CodedMonkey\Dirigent\Entity\PackageFetchStrategy;
use CodedMonkey\Dirigent\Entity\PackageUpdateSource;
use CodedMonkey\Dirigent\Form\PackageAddMirroringFormType;
use CodedMonkey\Dirigent\Form\PackageAddVcsFormType;
use CodedMonkey\Dirigent\Form\PackageFormType;
use CodedMonkey\Dirigent\Message\UpdatePackage;
use CodedMonkey\Dirigent\Package\PackageMetadataResolver;
use Doctrine\ORM\EntityManagerInterface;
use EasyCorp\Bundle\EasyAdminBundle\Attribute\AdminRoute;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Messenger\MessageBusInterface;
use Symfony\Component\Security\Http\Attribute\IsGranted;

class DashboardPackagesController extends AbstractController
{
    public function __construct(
        private readonly EntityManagerInterface $entityManager,
        private readonly PackageRepository $packageRepository,
        private readonly PackageMetadataResolver $metadataResolver,
        private readonly MessageBusInterface $messenger,
        #[Autowire(param: 'dirigent.metadata.default_mirror_fetch_strategy')]
        private readonly PackageFetchStrategy $defaultMirrorFetchStrategy,
    ) {
    }

    #[AdminRoute('/packages', name: 'packages')]
    #[IsGrantedAccess]
    public function list(Request $request): Response
    {
        $queryBuilder = $this->packageRepository->createQueryBuilder('package');

        if (null !== $query = $request->query->get('query')) {
            $queryBuilder->andWhere($queryBuilder->expr()->like('package.name', ':query'));
            $queryBuilder->setParameter('query', "%{$query}%");
        }

        $paginator = PackagePaginator::fromRequest($request, $queryBuilder, $this->container->get('router'));

        return $this->render('dashboard/packages/list.html.twig', [
            'paginator' => $paginator,
        ]);
    }

    #[AdminRoute('/packages/add-mirroring', name: 'packages_add_mirroring')]
    #[IsGranted('ROLE_ADMIN')]
    public function addMirroring(Request $request): Response
    {
        $form = $this->createForm(PackageAddMirroringFormType::class);

        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $registry = $form->get('registry')->getData();

            $packageNamesInput = (string) $form->get('packages')->getData();
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

                $package = new Package($packageName);
                $package->setMirrorRegistry($registry);
                $package->setFetchStrategy($this->defaultMirrorFetchStrategy);

                $this->packageRepository->save($package, true);

                $this->messenger->dispatch(new UpdatePackage($package->getId(), PackageUpdateSource::Manual));

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

    #[AdminRoute('/packages/add-vcs', name: 'packages_add_vcs')]
    #[IsGranted('ROLE_ADMIN')]
    public function addVcsRepository(Request $request): Response
    {
        $form = $this->createForm(PackageAddVcsFormType::class);

        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            /** @var Package $package */
            $package = $form->getData();

            $this->packageRepository->save($package, true);

            $this->messenger->dispatch(new UpdatePackage($package->getId(), PackageUpdateSource::Manual));

            return $this->redirectToRoute('dashboard_packages');
        }

        return $this->render('dashboard/packages/add_vcs.html.twig', [
            'form' => $form,
        ]);
    }

    #[AdminRoute('/packages/{package}/edit', name: 'packages_edit', options: ['requirements' => ['package' => MapPackage::PACKAGE_REGEX]])]
    #[IsGranted('ROLE_ADMIN')]
    public function edit(Request $request, #[MapPackage] Package $package): Response
    {
        $form = $this->createForm(PackageFormType::class, $package);

        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            /** @var Package $package */
            $package = $form->getData();
            $this->packageRepository->save($package, true);

            $this->messenger->dispatch(new UpdatePackage($package->getId(), PackageUpdateSource::Manual));

            return $this->redirectToRoute('dashboard_packages_info', ['package' => $package->getName()]);
        }

        return $this->render('dashboard/packages/edit.html.twig', [
            'package' => $package,
            'form' => $form,
        ]);
    }

    #[AdminRoute('/packages/{package}/update', name: 'packages_update', options: ['requirements' => ['package' => MapPackage::PACKAGE_REGEX]])]
    #[IsGranted('ROLE_ADMIN')]
    public function update(#[MapPackage] Package $package): Response
    {
        $this->messenger->dispatch(new UpdatePackage($package->getId(), PackageUpdateSource::Manual));

        return $this->redirectToRoute('dashboard_packages_info', ['package' => $package->getName()]);
    }

    #[AdminRoute('/packages/{package}/delete', name: 'packages_delete', options: ['requirements' => ['package' => MapPackage::PACKAGE_REGEX]])]
    #[IsGranted('ROLE_ADMIN')]
    public function delete(#[MapPackage] Package $package): Response
    {
        $this->packageRepository->remove($package, true);

        return $this->redirectToRoute('dashboard_packages');
    }
}
