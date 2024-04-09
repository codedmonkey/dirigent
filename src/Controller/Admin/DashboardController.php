<?php

namespace CodedMonkey\Conductor\Controller\Admin;

use CodedMonkey\Conductor\Doctrine\Entity\AccessToken;
use CodedMonkey\Conductor\Doctrine\Entity\User;
use EasyCorp\Bundle\EasyAdminBundle\Config\Dashboard;
use EasyCorp\Bundle\EasyAdminBundle\Config\MenuItem;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractDashboardController;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

class DashboardController extends AbstractDashboardController
{
    public function __construct(
        #[Autowire(param: 'conductor.title')]
        private readonly string $title,
    ) {
    }

    public function configureDashboard(): Dashboard
    {
        return Dashboard::new()
            ->setTitle($this->title);
    }

    public function configureMenuItems(): iterable
    {
        $user = $this->getUser();

        yield MenuItem::linkToDashboard('Dashboard', 'fa fa-home');
        yield MenuItem::linkToRoute('Repositories', 'fa fa-server', 'admin_repositories');

        if ($user?->isAdmin()) {
            yield MenuItem::linkToCrud('Users', 'fa fa-user', User::class);
        }

        yield MenuItem::section('Personal');
        if ($user) {
            yield MenuItem::linkToCrud('Access tokens', 'fa fa-key', AccessToken::class);
        } else {
            //yield MenuItem::linkToRoute('Create account', 'fa fa-user', 'login');
            yield MenuItem::linkToRoute('Log in', 'fa fa-user', 'login');
        }
    }

    #[Route('/', name: 'admin')]
    public function index(): Response
    {
        return $this->render('admin/dashboard.html.twig');
    }

    #[Route('/admin/repositories', name: 'admin_repositories')]
    public function repositories(#[Autowire(param: 'conductor.repositories')] array $repositories): Response
    {
        return $this->render('admin/repositories.html.twig', [
            'repositories' => $repositories,
        ]);
    }
}
