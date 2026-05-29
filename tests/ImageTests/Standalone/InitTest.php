<?php

namespace CodedMonkey\Dirigent\Tests\ImageTests\Standalone;

use PHPUnit\Framework\Attributes\DataProvider;
use Symfony\Component\Filesystem\Filesystem;
use Testcontainers\Container\GenericContainer;
use Testcontainers\Wait\WaitForLog;

class InitTest extends DockerStandaloneIsolatedTestCase
{
    private Filesystem $filesystem;
    private string $configPath;

    #[\Override]
    protected function setUp(): void
    {
        $this->filesystem = new Filesystem();
        $this->configPath = sys_get_temp_dir() . '/dirigent-config-' . uniqid();
    }

    #[\Override]
    protected function tearDown(): void
    {
        parent::tearDown();

        $this->filesystem->remove(__DIR__ . '/config');
    }

    public function testKernelSecretGenerated(): void
    {
        $this->setUpDefaultContainer();

        $this->assertContainerLogsContain('Generated a new kernel secret');

        $this->assertContainerFileExists(
            '/srv/config/secrets/kernel_secret',
            'A kernel_secret file must be generated.',
        );

        $this->assertContainerFileExists(
            '/srv/config/secrets/decryption_key',
            'A decryption_key file must be generated.',
        );

        $this->assertContainerFileExists(
            '/srv/config/secrets/encryption_key',
            'A encryption_key file must be generated.',
        );
    }

    public function testKernelSecretNotRegenerated(): void
    {
        $this->filesystem->mkdir($this->configPath . '/secrets');
        $this->filesystem->chmod($this->configPath, 0777, recursive: true);
        $this->filesystem->dumpFile($this->configPath . '/secrets/kernel_secret', 'fernando');

        $this->container = new GenericContainer('dirigent-standalone')
            ->withMount($this->configPath, '/srv/config')
            ->withMount(__DIR__ . '/scripts', '/srv/scripts/tests')
            ->withWait(new WaitForLog('ready to handle connections'))
            ->start();

        $this->assertContainerLogsContain('Kernel secret exists');

        $secret = $this->filesystem->readFile($this->configPath . '/secrets/kernel_secret');

        $this->assertSame('fernando', $secret, 'The default kernel secret file must not be changed if it already exists.');
    }

    public function testKernelSecretFileNotGeneratedIfKernelSecretEnvVarExists(): void
    {
        $this->filesystem->mkdir($this->configPath . '/secrets');
        $this->filesystem->chmod($this->configPath, 0777, recursive: true);

        $this->container = new GenericContainer('dirigent-standalone')
            ->withMount($this->configPath, '/srv/config')
            ->withMount(__DIR__ . '/scripts', '/srv/scripts/tests')
            ->withEnvironment(['KERNEL_SECRET' => 'fernando'])
            ->withWait(new WaitForLog('ready to handle connections'))
            ->start();

        $this->assertContainerLogsContain('Kernel secret is defined as an environment variable');

        $this->assertFalse(
            $this->filesystem->exists(__DIR__ . '/config/secrets/kernel_secret'),
            'The kernel_secret file must not be generated if the kernel secret is defined through an environment variable.',
        );
    }

    public function testKernelSecretFileNotGeneratedIfKernelSecretFileEnvVarExists(): void
    {
        $this->filesystem->mkdir($this->configPath . '/secrets');
        $this->filesystem->chmod($this->configPath, 0777, recursive: true);
        $this->filesystem->dumpFile($this->configPath . '/secrets/alt_kernel_secret', 'fernando');

        $this->container = new GenericContainer('dirigent-standalone')
            ->withMount($this->configPath, '/srv/config')
            ->withMount(__DIR__ . '/scripts', '/srv/scripts/tests')
            ->withEnvironment(['KERNEL_SECRET_FILE' => '/srv/config/secrets/alt_kernel_secret'])
            ->withWait(new WaitForLog('ready to handle connections'))
            ->start();

        $this->assertContainerLogsContain('Kernel secret is defined as an environment variable');

        $this->assertFalse(
            $this->filesystem->exists(__DIR__ . '/config/secrets/kernel_secret'),
            'The kernel_secret file must not be generated if the kernel secret is defined through an environment variable.',
        );
    }
}
