<?php

namespace CodedMonkey\Dirigent\Tests\Docker\Standalone;

class DatabaseTest extends DockerStandaloneTestCase
{
    public function testSchemaValid(): void
    {
        $this->assertCommandSuccessful(
            ['bin/console', 'doctrine:schema:validate', '--skip-mapping', '--skip-property-types', '--no-interaction'],
            'The database schema must be valid.',
        );

        $this->assertCommandSuccessful(
            ['bin/console', 'doctrine:migrations:up-to-date', '--no-interaction'],
            'The database migrations must be up-to-date.',
        );
    }

    public function testRunSql(): void
    {
        $this->assertCommandSuccessful(
            ['bin/console', 'dbal:run-sql', 'SELECT true'],
            'Must be able to run database query.',
        );
    }
}
