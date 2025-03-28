<?php

namespace CodedMonkey\Dirigent\Tests\UnitTests\Command;

use CodedMonkey\Dirigent\Command\EncryptionGenerateKeysCommand;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Console\Output\BufferedOutput;
use Symfony\Component\Console\Output\NullOutput;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\Finder\Finder;

class EncryptionGenerateKeysCommandTest extends KernelTestCase
{
    private string $privateKeyPath = __DIR__ . '/keys/private.key';
    private string $publicKeyPath = __DIR__ . '/keys/public.key';

    private Filesystem $filesystem;

    protected function setUp(): void
    {
        $this->filesystem = new Filesystem();

        $this->clearKeys();
    }

    protected function tearDown(): void
    {
        $this->clearKeys();
    }

    private function clearKeys(): void
    {
        $files = Finder::create()
            ->files()
            ->in(__DIR__ . '/keys')
            ->name('*.key');

        foreach ($files as $file) {
            $this->filesystem->remove($file->getRealPath());
        }
    }

    public function testGenerateNewKeys(): void
    {
        $command = new EncryptionGenerateKeysCommand(null, $this->privateKeyPath, null, $this->publicKeyPath);
        $output = new BufferedOutput();

        $exitCode = $command->run(new ArrayInput([]), $output);

        $this->assertSame(Command::SUCCESS, $exitCode);
        $this->assertStringContainsString('Generated encryption keys.', $output->fetch());
    }

    public function testEncryptionKeysAlreadyExist(): void
    {
        $command = new EncryptionGenerateKeysCommand(null, $this->privateKeyPath, null, $this->publicKeyPath);
        $output = new BufferedOutput();

        // Generate keys first
        $command->run(new ArrayInput([]), new NullOutput());

        $exitCode = $command->run(new ArrayInput([]), $output);

        $this->assertSame(Command::SUCCESS, $exitCode);
        $this->assertStringContainsString('Encryption keys already exist.', $output->fetch());
    }

    public function testGenerateNewPublicKey(): void
    {
        $command = new EncryptionGenerateKeysCommand(null, $this->privateKeyPath, null, $this->publicKeyPath);
        $output = new BufferedOutput();

        // Generate keys first
        $command->run(new ArrayInput([]), new NullOutput());

        $this->filesystem->remove($this->publicKeyPath);

        $exitCode = $command->run(new ArrayInput([]), $output);

        $this->assertSame(Command::SUCCESS, $exitCode);
        $this->assertStringContainsString('Generated a new (public) encryption key.', $output->fetch());
    }

    public function testMissingPrivateKey(): void
    {
        $command = new EncryptionGenerateKeysCommand(null, $this->privateKeyPath, null, $this->publicKeyPath);
        $output = new BufferedOutput();

        // Generate keys first
        $command->run(new ArrayInput([]), new NullOutput());

        $this->filesystem->remove($this->privateKeyPath);

        $exitCode = $command->run(new ArrayInput([]), $output);

        $this->assertSame(Command::FAILURE, $exitCode);
        $this->assertStringContainsString('Unable to generate (private) decryption key', $output->fetch());
    }

    public function testInvalidKeys(): void
    {
        $command = new EncryptionGenerateKeysCommand(null, $this->privateKeyPath, null, $this->publicKeyPath);
        $output = new BufferedOutput();

        // Generate keys first
        $command->run(new ArrayInput([]), new NullOutput());

        $this->filesystem->rename($this->privateKeyPath, $this->privateKeyPath . '_tmp');
        $this->filesystem->remove($this->publicKeyPath);

        // Generate new keys
        $command->run(new ArrayInput([]), new NullOutput());

        $this->filesystem->rename($this->privateKeyPath . '_tmp', $this->privateKeyPath, true);

        $exitCode = $command->run(new ArrayInput([]), $output);

        $this->assertSame(Command::FAILURE, $exitCode);
        $this->assertStringContainsString('The encryption key is not valid.', $output->fetch());
    }

    public function testEncryptionKeysDisabled(): void
    {
        $command = new EncryptionGenerateKeysCommand('123', null, '123', null);
        $output = new BufferedOutput();

        $exitCode = $command->run(new ArrayInput([]), $output);

        $this->assertSame(Command::SUCCESS, $exitCode);
        $this->assertStringContainsString('Encryption key files are disabled.', $output->fetch());
    }

    public function testEncryptionKeysMissing(): void
    {
        $command = new EncryptionGenerateKeysCommand(null, null, null, null);
        $output = new BufferedOutput();

        $exitCode = $command->run(new ArrayInput([]), $output);

        $this->assertSame(Command::FAILURE, $exitCode);
        $this->assertStringContainsString('Please provide a path for both a public and a private encryption key.', $output->fetch());
    }
}
