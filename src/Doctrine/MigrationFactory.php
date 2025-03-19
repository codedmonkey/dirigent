<?php

namespace CodedMonkey\Dirigent\Doctrine;

use CodedMonkey\Dirigent\Encryption\Encryption;
use Doctrine\DBAL\Connection;
use Doctrine\Migrations\AbstractMigration;
use Doctrine\Migrations\Version\MigrationFactory as BaseMigrationFactory;
use Psr\Log\LoggerInterface;

readonly class MigrationFactory implements BaseMigrationFactory
{
    public function __construct(
        private Connection $connection,
        private LoggerInterface $logger,
        private Encryption $encryptionUtility,
    ) {
    }

    public function createVersion(string $migrationClassName): AbstractMigration
    {
        if (str_contains($migrationClassName, '20250311205816')) {
            return new $migrationClassName($this->connection, $this->logger, $this->encryptionUtility);
        }

        return new $migrationClassName($this->connection, $this->logger);
    }
}
