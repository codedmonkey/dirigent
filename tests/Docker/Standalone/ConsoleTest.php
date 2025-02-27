<?php

namespace CodedMonkey\Dirigent\Tests\Docker\Standalone;

class ConsoleTest extends DockerStandaloneTestCase
{
    public function testComposerPlatformRequirements(): void
    {
        $this->assertCommandSuccessful(
            ['composer', 'check-platform-reqs', '--no-dev'],
            'Platform requirements of Composer packages must be met.',
        );
    }

    public function testConsole(): void
    {
        $this->assertCommandSuccessful(
            ['bin/console'],
            'Must be able to run console binary.',
        );
    }

    public function testDirigent(): void
    {
        $this->assertCommandSuccessful(
            ['bin/dirigent'],
            'Must be able to run dirigent binary.',
        );
    }
}
