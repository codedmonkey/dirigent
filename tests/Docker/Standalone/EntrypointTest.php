<?php

namespace CodedMonkey\Dirigent\Tests\Docker\Standalone;

use PHPUnit\Framework\TestCase;
use Testcontainers\Container\GenericContainer;
use Testcontainers\Wait\WaitForLog;

class EntrypointTest extends TestCase
{
    public function testInit(): void
    {
        // Running the container without a command must result in a running application.
        (new GenericContainer('dirigent-standalone'))
            ->withWait(new WaitForLog('ready to handle connections'))
            ->start()
            ->stop();

        $this->addToAssertionCount(1);

        // Running the container with the `-init` command must result in a running application.
        (new GenericContainer('dirigent-standalone'))
            ->withCommand(['-init'])
            ->withWait(new WaitForLog('ready to handle connections'))
            ->start()
            ->stop();

        $this->addToAssertionCount(1);
    }

    public function testDirigent(): void
    {
        $container = (new GenericContainer('dirigent-standalone'))
            ->withCommand(['list'])
            ->withWait(new WaitForLog('Dirigent'))
            ->start();

        $result = $container->logs();

        $container->stop();

        $this->assertStringStartsWith('Dirigent', $result, 'Running the container with any other command (than `-init`) must be passed to the Dirigent binary.');
    }

    public function testPassthrough(): void
    {
        $container = (new GenericContainer('dirigent-standalone'))
            ->withCommand(['--', 'bin/console', 'list'])
            ->withWait(new WaitForLog('Symfony'))
            ->start();

        $result = $container->logs();

        $container->stop();

        $this->assertStringStartsWith(')Symfony', $result, 'Running the container with an `--` argument must have its remaining arguments be interpreted as a command.');
    }
}
