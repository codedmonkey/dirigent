<?php

namespace CodedMonkey\Dirigent\Tests\Docker\Standalone;

abstract class DockerStandaloneIsolatedTestCase extends DockerStandaloneTestCase
{
    #[\Override]
    protected function setUp(): void
    {
    }

    protected function setUpDefaultContainer(): void
    {
        parent::setUp();
    }
}
