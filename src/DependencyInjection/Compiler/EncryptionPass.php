<?php

namespace CodedMonkey\Dirigent\DependencyInjection\Compiler;

use CodedMonkey\Dirigent\Command\EncryptionGenerateKeysCommand;
use CodedMonkey\Dirigent\Encryption\Encryption;
use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;

class EncryptionPass implements CompilerPassInterface
{
    public function process(ContainerBuilder $container): void
    {
        $parameterBag = $container->getParameterBag();

        $privateKey = $parameterBag->get('dirigent.encryption.private_key');
        $publicKey = $parameterBag->get('dirigent.encryption.public_key');
        $rotatedKeys = $parameterBag->get('dirigent.encryption.rotated_keys');

        $privateKeyPath = $parameterBag->get('dirigent.encryption.private_key_path');
        $publicKeyPath = $parameterBag->get('dirigent.encryption.public_key_path');
        $rotatedKeyPaths = $parameterBag->get('dirigent.encryption.rotated_key_paths');

        $container->getDefinition(Encryption::class)
            ->setFactory([Encryption::class, 'create'])
            ->setArguments([
                $privateKey,
                $privateKeyPath,
                $publicKey,
                $publicKeyPath,
                $rotatedKeys,
                $rotatedKeyPaths,
            ]);

        $container->getDefinition(EncryptionGenerateKeysCommand::class)
            ->setArguments([
                $privateKey,
                $privateKeyPath,
                $publicKey,
                $publicKeyPath,
            ]);

        $parameterBag->remove('dirigent.encryption.private_key');
        $parameterBag->remove('dirigent.encryption.public_key');
        $parameterBag->remove('dirigent.encryption.rotated_keys');

        $parameterBag->remove('dirigent.encryption.private_key_path');
        $parameterBag->remove('dirigent.encryption.public_key_path');
        $parameterBag->remove('dirigent.encryption.rotated_key_paths');
    }
}
