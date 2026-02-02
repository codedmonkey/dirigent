<?php

namespace CodedMonkey\Dirigent\Controller\Dashboard;

use CodedMonkey\Dirigent\Attribute\IsGrantedAccess;
use CodedMonkey\Dirigent\Doctrine\Entity\AccessToken;
use CodedMonkey\Dirigent\Doctrine\Entity\Credentials;
use CodedMonkey\Dirigent\Doctrine\Entity\Registry;
use CodedMonkey\Dirigent\Doctrine\Entity\User;
use CodedMonkey\Dirigent\Doctrine\Repository\PackageRepository;
use CodedMonkey\Dirigent\Kernel;
use Composer\Composer;
use EasyCorp\Bundle\EasyAdminBundle\Attribute\AdminDashboard;
use EasyCorp\Bundle\EasyAdminBundle\Config\Assets;
use EasyCorp\Bundle\EasyAdminBundle\Config\Dashboard;
use EasyCorp\Bundle\EasyAdminBundle\Config\MenuItem;
use EasyCorp\Bundle\EasyAdminBundle\Config\UserMenu;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractDashboardController;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\HttpKernel\Kernel as HttpKernel;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Symfony\Component\Yaml\Yaml;

#[AdminDashboard(routePath: '/', routeName: 'dashboard')]
class DashboardRootController extends AbstractDashboardController
{
    public function __construct(
        private readonly PackageRepository $packageRepository,
        #[Autowire(param: 'dirigent.title')]
        private readonly string $title,
        #[Autowire(param: 'dirigent.security.registration_enabled')]
        private readonly bool $registrationEnabled,
    ) {
    }

    #[\Override]
    public function configureDashboard(): Dashboard
    {
        return Dashboard::new()
            ->setTitle($this->title);
    }

    #[\Override]
    public function configureAssets(): Assets
    {
        return Assets::new()
            ->addWebpackEncoreEntry('dashboard');
    }

    #[\Override]
    public function configureMenuItems(): iterable
    {
        $request = $this->container->get('request_stack')->getCurrentRequest();
        $routeName = (string) $request->attributes->getString('_route');
        /** @var User|null $user */
        $user = $this->getUser();

        $packagesItem = MenuItem::linkToUrl('Packages', 'fa fa-cubes', $this->generateUrl('dashboard_packages'));
        if (str_starts_with($routeName, 'dashboard_packages_')) {
            $packagesItem->getAsDto()->setSelected(true);
        }

        yield MenuItem::linkToDashboard('Dashboard', 'fa fa-home');
        yield $packagesItem;

        yield MenuItem::section('Personal');
        if ($user) {
            yield MenuItem::linkToCrud('Access tokens', 'fa fa-key', AccessToken::class);
            yield MenuItem::linkToUrl('Account', 'fa fa-id-card', $this->generateUrl('dashboard_account'));
            yield MenuItem::linkToLogout('Sign out', 'fa fa-user-xmark');
        } else {
            yield MenuItem::linkToUrl('Sign in', 'fa fa-user', $this->generateUrl('dashboard_login'));

            if ($this->registrationEnabled) {
                yield MenuItem::linkToUrl('Register', 'fa fa-user-plus', $this->generateUrl('dashboard_register'));
            }
        }

        if ($user?->isAdmin()) {
            yield MenuItem::section('Administration');
            yield MenuItem::linkToCrud('Users', 'fa fa-users', User::class);
            yield MenuItem::linkToCrud('Registries', 'fa fa-server', Registry::class);
            yield MenuItem::linkToCrud('Credentials', 'fa fa-lock-open', Credentials::class);
        }

        yield MenuItem::section('Documentation');
        yield MenuItem::linkToUrl('Usage', 'fa fa-file', $this->generateUrl('dashboard_usage_docs'));
        yield MenuItem::linkToUrl('Administration', 'fa fa-file', $this->generateUrl('dashboard_admin_docs'))
            ->setPermission('ROLE_ADMIN');
        yield MenuItem::linkToUrl('Credits', 'fa fa-file', $this->generateUrl('dashboard_credits'));
    }

    /**
     * @param User $user
     */
    #[\Override]
    public function configureUserMenu(UserInterface $user): UserMenu
    {
        $menu = parent::configureUserMenu($user)
            ->setName($user->getUserIdentifier())
            ->addMenuItems([
                MenuItem::linkToUrl('Account', 'fa fa-id-card', $this->generateUrl('dashboard_account')),
            ]);

        if ($email = $user->getEmail()) {
            $menu->setGravatarEmail($email);
        }

        return $menu;
    }

    #[IsGrantedAccess]
    #[\Override]
    public function index(): Response
    {
        $packageCount = $this->packageRepository->count();

        return $this->render('dashboard/index.html.twig', [
            'packageCount' => $packageCount,
        ]);
    }

    #[Route('/docs/usage/{page}', name: 'dashboard_usage_docs')]
    #[IsGrantedAccess]
    public function docs(string $page = 'readme'): Response
    {
        return $this->render('dashboard/docs/usage.html.twig', [
            'page' => $this->parseDocumentationFile('usage', $page),
            'pageName' => $page,
        ]);
    }

    #[Route('/docs/admin/{page}', name: 'dashboard_admin_docs')]
    #[IsGranted('ROLE_ADMIN')]
    public function adminDocs(string $page = 'readme'): Response
    {
        return $this->render('dashboard/docs/admin.html.twig', [
            'page' => $this->parseDocumentationFile('admin', $page),
            'pageName' => $page,
        ]);
    }

    #[Route('/credits', name: 'dashboard_credits')]
    public function credits(): Response
    {
        return $this->render('dashboard/credits.html.twig', [
            'composerVersion' => Composer::VERSION,
            'dirigentVersion' => Kernel::VERSION,
            'phpVersion' => PHP_VERSION,
            'symfonyVersion' => HttpKernel::VERSION,
        ]);
    }

    private function parseDocumentationFile(string $directory, string $page): array
    {
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
        $docsUrlPattern = $this->generateUrl("dashboard_{$directory}_docs", ['page' => 'pagename']);
        $docsUrlPattern = str_replace('pagename', '$2', $docsUrlPattern);
        $relativeLinkReplacementPattern = "\$1{$docsUrlPattern}\$3";

        $markdownContents = preg_replace($relativeLinkPattern, $relativeLinkReplacementPattern, $markdownContents);

        // Finish parsing
        $data['contents'] = $markdownContents;

        return $data;
    }
}
