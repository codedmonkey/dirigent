<?php

namespace CodedMonkey\Conductor\Twig;

use EasyCorp\Bundle\EasyAdminBundle\Router\AdminUrlGenerator;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;
use Twig\Extension\AbstractExtension;
use Twig\Extension\GlobalsInterface;
use Twig\TwigFunction;

class DashboardExtension extends AbstractExtension implements GlobalsInterface
{
    public function __construct(
        private readonly AdminUrlGenerator $adminUrlGenerator,
        private readonly ParameterBagInterface $parameterBag,
    ) {
    }

    public function getFunctions(): array
    {
        return [
            new TwigFunction('dashboard_path', [$this, 'path']),
        ];
    }

    public function getGlobals(): array
    {
        return [
            'conductor' => [
                'slug' => $this->parameterBag->get('conductor.slug'),
            ]
        ];
    }

    public function path(string $route, array $routeParams = []): string
    {
        return $this->adminUrlGenerator->setRoute($route, $routeParams)->generateUrl();
    }
}
