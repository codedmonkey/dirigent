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

        $useFiles = !$privateKey && !$publicKey;

        if ($useFiles) {
            $container->getDefinition(Encryption::class)
                ->setLazy(true)
                ->setFactory([Encryption::class, 'createFromFiles'])
                ->setArguments([$privateKeyPath, $publicKeyPath, $rotatedKeyPaths]);

            $container->getDefinition(EncryptionGenerateKeysCommand::class)
                ->setArguments([$privateKeyPath, $publicKeyPath]);
        } else {
            $container->getDefinition(Encryption::class)
                ->setLazy(true)
                ->setFactory([Encryption::class, 'createFromHex'])
                ->setArguments([$privateKey, $publicKey, $rotatedKeys]);

            $container->getDefinition(EncryptionGenerateKeysCommand::class)
                ->setArguments([null, null]);
        }

        $parameterBag->remove('dirigent.encryption.private_key');
        $parameterBag->remove('dirigent.encryption.public_key');
        $parameterBag->remove('dirigent.encryption.rotated_keys');

        $parameterBag->remove('dirigent.encryption.private_key_path');
        $parameterBag->remove('dirigent.encryption.public_key_path');
        $parameterBag->remove('dirigent.encryption.rotated_key_paths');
    }
}
