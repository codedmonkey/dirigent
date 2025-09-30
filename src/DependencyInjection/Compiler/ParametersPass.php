<?php

namespace CodedMonkey\Dirigent\DependencyInjection\Compiler;

use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;

class ParametersPass implements CompilerPassInterface
{
    public function process(ContainerBuilder $container): void
    {
        $parameterBag = $container->getParameterBag();

        $slug = $parameterBag->get('dirigent.slug');
        $container->getDefinition('twig')->addMethodCall('addGlobal', ['dirigent', ['slug' => $slug]]);
        $container->setParameter('scheb_two_factor.totp.issuer', $slug);
    }
}
