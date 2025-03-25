<?php

namespace CodedMonkey\Dirigent\Tests\Docker\Standalone;

use PHPUnit\Framework\Attributes\DataProvider;
use Symfony\Component\Filesystem\Filesystem;
use Testcontainers\Container\GenericContainer;
use Testcontainers\Wait\WaitForLog;

class InitTest extends DockerStandaloneIsolatedTestCase
{
    protected Filesystem $filesystem;

    protected function setUp(): void
    {
        $this->filesystem = new Filesystem();
    }

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
    }

    public function testKernelSecretNotRegeneratedOnRestart(): void
    {
        $this->filesystem->mkdir(__DIR__ . '/config/secrets');
        $this->filesystem->chmod(__DIR__ . '/config', 0777, recursive: true);

        // Generate kernel secret first
        $this->container = (new GenericContainer('dirigent-standalone'))
            ->withMount(__DIR__ . '/config', '/srv/config')
            ->withMount(__DIR__ . '/scripts', '/srv/scripts/tests')
            ->withWait(new WaitForLog('ready to handle connections'))
            ->start();

        $initialSecret = $this->filesystem->readFile(__DIR__ . '/config/secrets/kernel_secret');

        $this->container->stop();

        $this->container = (new GenericContainer('dirigent-standalone'))
            ->withMount(__DIR__ . '/config', '/srv/config')
            ->withMount(__DIR__ . '/scripts', '/srv/scripts/tests')
            ->withWait(new WaitForLog('ready to handle connections'))
            ->start();

        $this->assertContainerLogsContain('Kernel secret exists');

        $secret = $this->filesystem->readFile(__DIR__ . '/config/secrets/kernel_secret');

        $this->assertSame($initialSecret, $secret, 'The kernel_secret file must not be changed if it already exists.');
    }

    public static function kernelSecretEnvVarProvider(): array
    {
        return [
            ['KERNEL_SECRET', 'fernando'],
            ['KERNEL_SECRET_FILE', '/srv/config/secrets/kernel_secret'],
        ];
    }

    #[DataProvider('kernelSecretEnvVarProvider')]
    public function testKernelSecretNotGeneratedIfEnvVarExists(string $varName, string $varValue): void
    {
        $this->filesystem->mkdir(__DIR__ . '/config/secrets');
        $this->filesystem->chmod(__DIR__ . '/config', 0777, recursive: true);

        $this->container = (new GenericContainer('dirigent-standalone'))
            ->withMount(__DIR__ . '/config', '/srv/config')
            ->withMount(__DIR__ . '/scripts', '/srv/scripts/tests')
            ->withEnvironment([$varName => $varValue])
            ->withWait(new WaitForLog('ready to handle connections'))
            ->start();

        $this->assertContainerLogsContain('Kernel secret is defined as an environment variable');

        $kernelSecretExists = $this->filesystem->exists(__DIR__ . '/config/secrets/kernel_secret');
        $this->assertFalse($kernelSecretExists, 'The kernel_secret file must not be generated if the kernel secret is defined through an environment variable.');
    }
}
