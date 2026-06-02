<?php

declare(strict_types=1);

namespace CodedMonkey\Dirigent\Tests\ImageTests\Standalone;

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
