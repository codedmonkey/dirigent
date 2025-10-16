<?php

namespace CodedMonkey\Dirigent\DependencyInjection\Compiler;

use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;

class ParametersPass implements CompilerPassInterface
{
    public function process(ContainerBuilder $container): void
    {
        $this->setMfaIssuer($container);
        $this->setTwigGlobal($container);
    }

    private function setMfaIssuer(ContainerBuilder $container): void
    {
        $container->setParameter('scheb_two_factor.totp.issuer', $container->getParameter('dirigent.title'));
    }

    private function setTwigGlobal(ContainerBuilder $container): void
    {
        $variables = [
            'slug' => $container->getParameter('dirigent.slug'),
        ];

        $container->getDefinition('twig')->addMethodCall('addGlobal', ['dirigent', $variables]);
    }
}
