<?php

namespace CodedMonkey\Dirigent\Twig;

use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;
use Twig\Extension\AbstractExtension;
use Twig\Extension\GlobalsInterface;

class DashboardExtension extends AbstractExtension implements GlobalsInterface
{
    public function __construct(
        private readonly ParameterBagInterface $parameterBag,
    ) {
    }

    public function getGlobals(): array
    {
        return [
            'dirigent' => [
                'slug' => $this->parameterBag->get('dirigent.slug'),
            ],
        ];
    }
}
