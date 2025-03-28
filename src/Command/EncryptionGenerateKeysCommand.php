<?php

namespace CodedMonkey\Dirigent\Command;

use CodedMonkey\Dirigent\Encryption\Encryption;
use CodedMonkey\Dirigent\Encryption\EncryptionException;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\StyleInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\Filesystem\Filesystem;

#[AsCommand(
    name: 'encryption:generate-keys',
    description: 'Generates an encryption key pair',
)]
class EncryptionGenerateKeysCommand extends Command
{
    public function __construct(
        public readonly ?string $privateKey,
        public readonly ?string $privateKeyPath,
        public readonly ?string $publicKey,
        public readonly ?string $publicKeyPath,
    ) {
        parent::__construct();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        if ($this->privateKey || $this->publicKey) {
            $io->info('Encryption key files are disabled.');

            return Command::SUCCESS;
        }

        if (!$this->privateKeyPath || !$this->publicKeyPath) {
            $io->warning('Please provide a path for both a public and a private encryption key.');

            return Command::FAILURE;
        }

        $filesystem = new Filesystem();

        $decryptionKeyExists = $filesystem->exists($this->privateKeyPath);
        $encryptionKeyExists = $filesystem->exists($this->publicKeyPath);

        if (!$decryptionKeyExists && $encryptionKeyExists) {
            $io->error('Unable to generate (private) decryption key because a (public) encryption key exists.');

            return Command::FAILURE;
        } elseif ($decryptionKeyExists && $encryptionKeyExists) {
            $io->info('Encryption keys already exist.');
        } elseif ($decryptionKeyExists && !$encryptionKeyExists) {
            $decryptionKey = sodium_hex2bin($filesystem->readFile($this->privateKeyPath));
            $encryptionKey = sodium_crypto_box_publickey($decryptionKey);

            $filesystem->dumpFile($this->publicKeyPath, sodium_bin2hex($encryptionKey));

            $io->success('Generated a new (public) encryption key.');
        } else {
            $decryptionKey = sodium_crypto_box_keypair();
            $encryptionKey = sodium_crypto_box_publickey($decryptionKey);

            $filesystem->dumpFile($this->privateKeyPath, sodium_bin2hex($decryptionKey));
            $filesystem->dumpFile($this->publicKeyPath, sodium_bin2hex($encryptionKey));

            $io->success('Generated encryption keys.');
        }

        return $this->validateKeys($io);
    }

    private function validateKeys(StyleInterface $output): int
    {
        $encryption = Encryption::create(null, $this->privateKeyPath, null, $this->publicKeyPath, [], []);

        try {
            $encryption->validate();

            return Command::SUCCESS;
        } catch (EncryptionException $exception) {
            $output->error($exception->getMessage());

            return Command::FAILURE;
        }
    }
}
