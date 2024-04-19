<?php

namespace CodedMonkey\Conductor\Controller\Dashboard;

use CodedMonkey\Conductor\Doctrine\Entity\AccessToken;
use CodedMonkey\Conductor\Doctrine\Entity\Credentials;
use CodedMonkey\Conductor\Doctrine\Entity\Registry;
use CodedMonkey\Conductor\Doctrine\Entity\User;
use EasyCorp\Bundle\EasyAdminBundle\Config\Dashboard;
use EasyCorp\Bundle\EasyAdminBundle\Config\MenuItem;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractDashboardController;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

class DashboardRootController extends AbstractDashboardController
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
        yield MenuItem::linkToRoute('Packages', 'fa fa-cubes', 'dashboard_packages');

        yield MenuItem::section('Personal');
        if ($user) {
            yield MenuItem::linkToCrud('Access tokens', 'fa fa-key', AccessToken::class);
        } else {
            //yield MenuItem::linkToRoute('Create account', 'fa fa-user', 'login');
            yield MenuItem::linkToRoute('Log in', 'fa fa-user', 'dashboard_login');
        }

        if ($user?->isAdmin()) {
            yield MenuItem::section('Admin');
            yield MenuItem::linkToCrud('Registries', 'fa fa-server', Registry::class);
            yield MenuItem::linkToCrud('Credentials', 'fa fa-lock-open', Credentials::class);
            yield MenuItem::linkToCrud('Users', 'fa fa-user', User::class);
        }
    }

    #[Route('/', name: 'dashboard')]
    public function index(): Response
    {
        return $this->render('dashboard/index.html.twig');
    }
}
