<?php

namespace CodedMonkey\Dirigent\Tests\Docker\Standalone;

class PhpPlatformTest extends DockerStandaloneTestCase
{
    public function testComposerPlatformRequirements(): void
    {
        $this->assertCommandSuccessful(
            ['composer', 'check-platform-reqs', '--no-dev'],
            'Platform requirements of Composer packages must be met.',
        );
    }

    public function testSymfonyCacheGenerated(): void
    {
        $this->assertContainerFileExists(
            'var/cache/symfony/prod/CodedMonkey_Dirigent_KernelProdContainer.php',
            'The Symfony cache must be generated during initialization.',
        );
    }
}
