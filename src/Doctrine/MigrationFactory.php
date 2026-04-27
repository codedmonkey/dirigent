<?php

namespace CodedMonkey\Dirigent\Doctrine;

use CodedMonkey\Dirigent\Encryption\Encryption;
use Doctrine\DBAL\Connection;
use Doctrine\Migrations\AbstractMigration;
use Doctrine\Migrations\Version\MigrationFactory as MigrationFactoryInterface;
use DoctrineMigrations\Version20250311205816;
use DoctrineMigrations\Version20260427080101;
use Psr\Log\LoggerInterface;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

readonly class MigrationFactory implements MigrationFactoryInterface
{
    public function __construct(
        private Connection $connection,
        private LoggerInterface $logger,
        private Encryption $encryptionUtility,
        private UserPasswordHasherInterface $passwordHasher,
    ) {
    }

    public function createVersion(string $migrationClassName): AbstractMigration
    {
        $additionalParameters = match ($migrationClassName) {
            Version20250311205816::class => [$this->encryptionUtility],
            Version20260427080101::class => [$this->passwordHasher],
            default => [],
        };

        $parameters = [$this->connection, $this->logger, ...$additionalParameters];

        return new $migrationClassName(...$parameters);
    }
}
