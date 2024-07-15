<?php

namespace CodedMonkey\Conductor\Twig;

use EasyCorp\Bundle\EasyAdminBundle\Router\AdminUrlGenerator;
use Twig\Extension\AbstractExtension;
use Twig\TwigFunction;

class DashboardExtension extends AbstractExtension
{
    public function __construct(
        private readonly AdminUrlGenerator $adminUrlGenerator,
    ) {
    }

    public function getFunctions(): array
    {
        return [
            new TwigFunction('dashboard_path', [$this, 'path']),
        ];
    }

    public function path(string $route, array $routeParams = []): string
    {
        return $this->adminUrlGenerator->setRoute($route, $routeParams)->generateUrl();
    }
}
