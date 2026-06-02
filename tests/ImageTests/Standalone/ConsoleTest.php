<?php

declare(strict_types=1);

namespace CodedMonkey\Dirigent\Tests\ImageTests\Standalone;

class ConsoleTest extends DockerStandaloneTestCase
{
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
