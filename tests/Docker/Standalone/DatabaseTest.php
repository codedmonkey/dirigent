<?php

namespace CodedMonkey\Dirigent\Tests\Docker\Standalone;

class DatabaseTest extends DockerStandaloneTestCase
{
    public function testRunSql(): void
    {
        $this->assertCommandSuccessful(
            ['bin/console', 'dbal:run-sql', 'SELECT true'],
            'Must be able to run database query.',
        );
    }
}
