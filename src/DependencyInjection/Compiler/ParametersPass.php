<?php

namespace CodedMonkey\Dirigent\DependencyInjection\Compiler;

use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;

class ParametersPass implements CompilerPassInterface
{
    public function process(ContainerBuilder $container): void
    {
        $this->setTwigGlobal($container);
    }

    private function setTwigGlobal(ContainerBuilder $container): void
    {
        $parameterBag = $container->getParameterBag();

        $variables = [
            'slug' => $parameterBag->get('dirigent.slug'),
        ];

        $container->getDefinition('twig')->addMethodCall('addGlobal', ['dirigent', $variables]);
    }
}
