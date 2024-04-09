<?php

namespace CodedMonkey\Conductor\Controller\Admin;

use CodedMonkey\Conductor\Doctrine\Repository\PackageRepository;
use CodedMonkey\Conductor\Form\PackageAddRegistryType;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

class PackagesController extends AbstractController
{
    #[Route('/admin/packages', name: 'admin_packages')]
    public function list(PackageRepository $packageRepository): Response
    {
        $packages = $packageRepository->findBy([], ['name' => 'ASC']);

        return $this->render('admin/packages/list.html.twig', [
            'packages' => $packages,
        ]);
    }

    #[Route('/admin/packages/info/{packageName}', name: 'admin_packages_info', requirements: ['packageName' => '[a-z0-9_.-]+/[a-z0-9_.-]+'])]
    public function info(string $packageName, PackageRepository $packageRepository): Response
    {
        $package = $packageRepository->findOneBy(['name' => $packageName]);

        return $this->render('admin/packages/info.html.twig', [
            'package' => $package,
        ]);
    }

    #[Route('/admin/packages/add-registry', name: 'admin_packages_add_registry')]
    public function addFromRegistry(Request $request): Response
    {
        $form = $this->createForm(PackageAddRegistryType::class);

        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $data = $form->getData();

            $packages = explode(PHP_EOL, $data['packages']);
            $packages = array_map('trim', $packages);

            dump($packages);
        }

        return $this->render('admin/packages/add_registry.html.twig', [
            'form' => $form,
        ]);
    }
}
