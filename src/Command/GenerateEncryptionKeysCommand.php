<?php

namespace CodedMonkey\Dirigent\Command;

use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(
    name: 'encryption:generate-keys',
    description: 'Generates an encryption key pair',
)]
class GenerateEncryptionKeysCommand extends Command
{
    public function __construct(
        public readonly string $encryptionKeyPath,
        public readonly string $decryptionKeyPath,
    ) {
        parent::__construct();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        $encryptionKeyExists = file_exists($this->encryptionKeyPath);
        $decryptionKeyExists = file_exists($this->decryptionKeyPath);

        if ($encryptionKeyExists && !$decryptionKeyExists) {
            $io->error('Unable to generate (private) decryption key if a (public) encryption key exists.');

            return Command::FAILURE;
        }

        if ($encryptionKeyExists && $decryptionKeyExists) {
            $io->info('Encryption keys already exist.');

            return Command::SUCCESS;
        }

        $decryptionKey = sodium_crypto_box_keypair();
        $encryptionKey = sodium_crypto_box_publickey($decryptionKey);

        file_put_contents($this->encryptionKeyPath, sodium_bin2hex($encryptionKey));
        file_put_contents($this->decryptionKeyPath, sodium_bin2hex($decryptionKey));

        $io->success('Generated encryption keys.');

        return Command::SUCCESS;
    }
}
