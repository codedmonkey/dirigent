<?php

namespace CodedMonkey\Conductor\Controller\Admin;

use EasyCorp\Bundle\EasyAdminBundle\Config\Dashboard;
use EasyCorp\Bundle\EasyAdminBundle\Config\MenuItem;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractDashboardController;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class DashboardController extends AbstractDashboardController
{
    public function __construct(
        #[Autowire(param: 'conductor.title')]
        private readonly string $title,
    ) {
    }

    #[Route('/', name: 'admin')]
    public function index(): Response
    {
        return $this->render('admin/dashboard.html.twig');
    }

    public function configureDashboard(): Dashboard
    {
        return Dashboard::new()
            ->setTitle($this->title);
    }

    public function configureMenuItems(): iterable
    {
        yield MenuItem::linkToDashboard('Dashboard', 'fa fa-home');
        yield MenuItem::linkToRoute('Repositories', 'fa fa-server', 'admin_repositories');
        // yield MenuItem::linkToCrud('The Label', 'fas fa-list', EntityClass::class);
    }

    #[Route('/admin/repositories', name: 'admin_repositories')]
    public function repositories(#[Autowire(param: 'conductor.repositories')] array $repositories): Response
    {
        return $this->render('admin/repositories.html.twig', [
            'repositories' => $repositories,
        ]);
    }
}
