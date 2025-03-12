<?php

namespace CodedMonkey\Dirigent\Tests\Docker\Standalone;

class InitTest extends DockerStandaloneTestCase
{
    public function testKernelSecretGenerated(): void
    {
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
}
