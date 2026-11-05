<?php

declare(strict_types=1);

namespace CodedMonkey\Dirigent\Tests\UnitTests\Command;

use CodedMonkey\Dirigent\Command\EncryptionGenerateKeysCommand;
use PHPUnit\Framework\Attributes\DataProvider;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Console\Output\BufferedOutput;
use Symfony\Component\Console\Output\NullOutput;
use Symfony\Component\Filesystem\Exception\IOExceptionInterface;
use Symfony\Component\Filesystem\Filesystem;

class EncryptionGenerateKeysCommandTest extends KernelTestCase
{
    private string $storagePath;
    private string $privateKeyPath;
    private string $publicKeyPath;
    private Filesystem $filesystem;

    #[\Override]
    protected function setUp(): void
    {
        $this->storagePath = sys_get_temp_dir() . '/' . uniqid('dirigent-encryption-keys-');
        $this->privateKeyPath = "$this->storagePath/private.key";
        $this->publicKeyPath = "$this->storagePath/public.key";
        $this->filesystem = new Filesystem();
    }

    #[\Override]
    protected function tearDown(): void
    {
        new Filesystem()->remove($this->storagePath);
    }

    public function testGenerateNewKeys(): void
    {
        $command = new EncryptionGenerateKeysCommand(null, $this->privateKeyPath, null, $this->publicKeyPath);
        $output = new BufferedOutput();

        $exitCode = $command->run(new ArrayInput([]), $output);

        $this->assertSame(Command::SUCCESS, $exitCode);
        $this->assertStringContainsString('Generated encryption keys.', $output->fetch());
    }

    public static function umasks(): iterable
    {
        yield 'permissive' => [0o000];
        yield 'standard' => [0o022];
        yield 'restrictive' => [0o077];
    }

    #[DataProvider('umasks')]
    public function testGenerateNewPrivateKeyIsOwnerOnly(int $mask): void
    {
        if ('Windows' === PHP_OS_FAMILY) {
            $this->markTestSkipped('POSIX file permissions are not available on Windows.');
        }

        $command = new EncryptionGenerateKeysCommand(null, $this->privateKeyPath, null, $this->publicKeyPath);
        $originalMask = umask($mask);

        try {
            $exitCode = $command->run(new ArrayInput([]), new NullOutput());

            $this->assertSame(Command::SUCCESS, $exitCode);
            $this->assertSame($mask, umask(), 'The command must restore the caller\'s umask.');
            $this->assertSame(0o600, fileperms($this->privateKeyPath) & 0o777);
            $this->assertSame(0o666 & ~$mask, fileperms($this->publicKeyPath) & 0o777);
        } finally {
            umask($originalMask);
        }
    }

    public function testPrivateKeyWriteFailureRestoresUmask(): void
    {
        $blockingPath = $this->storagePath . '/not-a-directory';
        $this->filesystem->dumpFile($blockingPath, 'not a directory');
        $command = new EncryptionGenerateKeysCommand(null, $blockingPath . '/private.key', null, $this->publicKeyPath);

        $this->expectException(IOExceptionInterface::class);
        $originalMask = umask(0o022);

        try {
            $command->run(new ArrayInput([]), new NullOutput());
        } finally {
            $actualMask = umask($originalMask);
            $this->assertSame(0o022, $actualMask, 'The command must restore the caller\'s umask.');
        }
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
