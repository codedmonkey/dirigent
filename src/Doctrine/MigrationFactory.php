<?php

declare(strict_types=1);

namespace CodedMonkey\Dirigent\Doctrine;

use CodedMonkey\Dirigent\Encryption\Encryption;
use Doctrine\DBAL\Connection;
use Doctrine\Migrations\AbstractMigration;
use Doctrine\Migrations\Version\MigrationFactory as MigrationFactoryInterface;
use DoctrineMigrations\Version20250311205816;
use Psr\Log\LoggerInterface;

readonly class MigrationFactory implements MigrationFactoryInterface
{
    public function __construct(
        private Connection $connection,
        private LoggerInterface $logger,
        private Encryption $encryptionUtility,
    ) {
    }

    public function createVersion(string $migrationClassName): AbstractMigration
    {
        $additionalParameters = match ($migrationClassName) {
            Version20250311205816::class => [$this->encryptionUtility],
            default => [],
        };

        $parameters = [$this->connection, $this->logger, ...$additionalParameters];

        return new $migrationClassName(...$parameters);
    }
}
