<?php

namespace CodedMonkey\Dirigent\Tests\Docker\Standalone;

use PHPUnit\Framework\TestCase;
use Testcontainers\Container\GenericContainer;
use Testcontainers\Container\StartedGenericContainer;
use Testcontainers\Wait\WaitForLog;

abstract class DockerStandaloneTestCase extends TestCase
{
    protected StartedGenericContainer $container;

    protected function setUp(): void
    {
        $this->container = (new GenericContainer('dirigent-standalone'))
            ->withExposedPorts(7015)
            ->withMount(__DIR__ . '/scripts', '/srv/tests')
            ->withWait(new WaitForLog('ready to handle connections'))
            ->start();
    }

    protected function tearDown(): void
    {
        $this->container->stop();
    }

    protected function assertCommandSuccessful(array $command, ?string $message = null): void
    {
        $result = $this->container->exec(['sh', '/srv/tests/command-successful.sh', ...$command]);
        if ('0' === $result) {
            $this->addToAssertionCount(1);
        } else {
            $commandString = implode(' ', $command);
            $message = $message ? "$message\n" : null;
            $message .= "Failed asserting command \"$commandString\" was successful. ";
            $message .= strlen(trim($result)) ? "Output:\n\n$result\n" : 'The command did not return any output.';

            $this->fail($message);
        }
    }
}
