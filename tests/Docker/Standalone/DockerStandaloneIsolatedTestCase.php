<?php

namespace CodedMonkey\Dirigent\Tests\Docker\Standalone;

abstract class DockerStandaloneIsolatedTestCase extends DockerStandaloneTestCase
{
    protected function setUp(): void
    {
    }

    protected function setUpDefaultContainer(): void
    {
        parent::setUp();
    }
}
