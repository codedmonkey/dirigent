<?php

namespace CodedMonkey\Conductor\Controller\Dashboard;

use CodedMonkey\Conductor\Attribute\IsGrantedAccess;
use CodedMonkey\Conductor\Doctrine\Entity\AccessToken;
use CodedMonkey\Conductor\Doctrine\Entity\Credentials;
use CodedMonkey\Conductor\Doctrine\Entity\Registry;
use CodedMonkey\Conductor\Doctrine\Entity\User;
use EasyCorp\Bundle\EasyAdminBundle\Config\Assets;
use EasyCorp\Bundle\EasyAdminBundle\Config\Dashboard;
use EasyCorp\Bundle\EasyAdminBundle\Config\MenuItem;
use EasyCorp\Bundle\EasyAdminBundle\Config\UserMenu;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractDashboardController;
use EasyCorp\Bundle\EasyAdminBundle\Router\AdminUrlGenerator;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Symfony\Component\Yaml\Yaml;

class DashboardRootController extends AbstractDashboardController
{
    public function __construct(
        #[Autowire(param: 'conductor.title')]
        private readonly string $title,
        #[Autowire(param: 'conductor.security.registration_enabled')]
        private readonly bool $registrationEnabled,
    ) {
    }

    public function configureDashboard(): Dashboard
    {
        return Dashboard::new()
            ->setTitle($this->title);
    }

    public function configureAssets(): Assets
    {
        return Assets::new()
            ->addWebpackEncoreEntry('dashboard');
    }

    public function configureMenuItems(): iterable
    {
        $request = $this->container->get('request_stack')->getCurrentRequest();
        $user = $this->getUser();

        $packagesItem = MenuItem::linkToRoute('Packages', 'fa fa-cubes', 'dashboard_packages');
        if (str_starts_with($request->query->get('routeName'), 'dashboard_packages_')) {
            $packagesItem->getAsDto()->setSelected(true);
        }

        yield MenuItem::linkToDashboard('Dashboard', 'fa fa-home');
        yield $packagesItem;

        yield MenuItem::section('Personal');
        if ($user) {
            yield MenuItem::linkToCrud('Access tokens', 'fa fa-key', AccessToken::class);
            yield MenuItem::linkToRoute('Account', 'fa fa-id-card', 'dashboard_account');
            yield MenuItem::linkToLogout('Sign out', 'fa fa-user-xmark');
        } else {
            yield MenuItem::linkToRoute('Sign in', 'fa fa-user', 'dashboard_login');

            if ($this->registrationEnabled) {
                yield MenuItem::linkToRoute('Register', 'fa fa-user-plus', 'dashboard_register');
            }
        }

        if ($user?->isAdmin()) {
            yield MenuItem::section('Administration');
            yield MenuItem::linkToCrud('Users', 'fa fa-users', User::class);
            yield MenuItem::linkToCrud('Registries', 'fa fa-server', Registry::class);
            yield MenuItem::linkToCrud('Credentials', 'fa fa-lock-open', Credentials::class);
        }

        yield MenuItem::section('Documentation');
        yield MenuItem::linkToRoute('Usage', 'fa fa-file', 'dashboard_usage_docs');
        yield MenuItem::linkToRoute('Administration', 'fa fa-file', 'dashboard_admin_docs')
            ->setPermission('ROLE_ADMIN');
        yield MenuItem::linkToRoute('Credits', 'fa fa-file', 'dashboard_credits');
    }

    public function configureUserMenu(UserInterface $user): UserMenu
    {
        $menu = parent::configureUserMenu($user)
            ->setName($user->getUserIdentifier())
            ->addMenuItems([
                MenuItem::linkToRoute('Account', 'fa fa-id-card', 'dashboard_account'),
            ]);

        if ($email = $user->getEmail()) {
            $menu->setGravatarEmail($email);
        }

        return $menu;
    }

    #[Route('/', name: 'dashboard')]
    #[IsGrantedAccess]
    public function index(): Response
    {
        return $this->render('dashboard/index.html.twig');
    }

    #[Route('/dashboard/docs/usage/{page}', name: 'dashboard_usage_docs')]
    #[IsGrantedAccess]
    public function docs(string $page = 'readme'): Response
    {
        return $this->render('dashboard/docs/usage.html.twig', [
            'page' => $this->parseDocumentationFile('usage', $page),
            'pageName' => $page,
        ]);
    }

    #[Route('/dashboard/docs/admin/{page}', name: 'dashboard_admin_docs')]
    #[IsGranted('ROLE_ADMIN')]
    public function adminDocs(string $page = 'readme'): Response
    {
        return $this->render('dashboard/docs/admin.html.twig', [
            'page' => $this->parseDocumentationFile('admin', $page),
            'pageName' => $page,
        ]);
    }

    #[Route('/dashboard/credits', name: 'dashboard_credits')]
    public function credits(): Response
    {
        return $this->render('dashboard/credits.html.twig');
    }

    private function parseDocumentationFile(string $directory, string $page): array
    {
        $adminUrlGenerator = $this->container->get(AdminUrlGenerator::class);
        $twig = $this->container->get('twig');

        $template = "dashboard/docs/$directory/$page.md.twig";

        if (!$twig->getLoader()->exists($template)) {
            throw new NotFoundHttpException();
        }

        $markdownContents = $twig->render($template);

        // Extract front matter
        $frontMatterPattern = '/^---\s*\n(.*?)\n---\s*\n/s';

        if (!preg_match($frontMatterPattern, $markdownContents, $matches)) {
            throw new \LogicException("Template \"$template\" doesn't contain a front matter.");
        }

        $frontMatter = $matches[1];
        $markdownContents = preg_replace($frontMatterPattern, '', $markdownContents);

        // Parse front matter
        $data = Yaml::parse($frontMatter);

        // Fix relative URLs
        $relativeLinkPattern = '/(\[.*?]\()([^\/)]+)(\))/';
        $docsUrlPattern = $adminUrlGenerator->setRoute("dashboard_{$directory}_docs", ['page' => 'pagename'])->generateUrl();
        $docsUrlPattern = str_replace('pagename', '$2', $docsUrlPattern);
        $relativeLinkReplacementPattern = "\$1{$docsUrlPattern}\$3";

        $markdownContents = preg_replace($relativeLinkPattern, $relativeLinkReplacementPattern, $markdownContents);

        // Finish parsing
        $data['contents'] = $markdownContents;

        return $data;
    }
}
