<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use CodedMonkey\Dirigent\Encryption\Encryption;
use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;
use Psr\Log\LoggerInterface;

final class Version20250311205816 extends AbstractMigration
{
    public function __construct(
        Connection $connection,
        LoggerInterface $logger,
        private readonly Encryption $encryptionUtility,
    ) {
        parent::__construct($connection, $logger);
    }

    public function getDescription(): string
    {
        return 'Encrypt sensitive credentials fields';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE credentials ALTER username TYPE TEXT');
        $this->addSql('ALTER TABLE credentials ALTER password TYPE TEXT');
        $this->addSql('ALTER TABLE credentials ALTER token TYPE TEXT');

        $credentialsCollection = $this->connection->fetchAllAssociative('SELECT id, username, password, token FROM credentials');

        foreach ($credentialsCollection as $credentials) {
            if (null !== $credentials['username']) {
                $sealedUsername = $this->encryptionUtility->seal($credentials['username']);
                $this->addSql('UPDATE credentials SET username = ? WHERE id = ?', [$sealedUsername, $credentials['id']]);
            }

            if (null !== $credentials['password']) {
                $sealedPassword = $this->encryptionUtility->seal($credentials['password']);
                $this->addSql('UPDATE credentials SET password = ? WHERE id = ?', [$sealedPassword, $credentials['id']]);
            }

            if (null !== $credentials['token']) {
                $sealedToken = $this->encryptionUtility->seal($credentials['token']);
                $this->addSql('UPDATE credentials SET token = ? WHERE id = ?', [$sealedToken, $credentials['id']]);
            }
        }
    }

    public function down(Schema $schema): void
    {
        $credentialsCollection = $this->connection->fetchAllAssociative('SELECT id, username, password, token FROM credentials');

        foreach ($credentialsCollection as $credentials) {
            if (null !== $credentials['username']) {
                $username = $this->encryptionUtility->reveal($credentials['username']);
                $this->addSql('UPDATE credentials SET username = ? WHERE id = ?', [$username, $credentials['id']]);
            }

            if (null !== $credentials['password']) {
                $password = $this->encryptionUtility->reveal($credentials['password']);
                $this->addSql('UPDATE credentials SET password = ? WHERE id = ?', [$password, $credentials['id']]);
            }

            if (null !== $credentials['token']) {
                $token = $this->encryptionUtility->reveal($credentials['token']);
                $this->addSql('UPDATE credentials SET token = ? WHERE id = ?', [$token, $credentials['id']]);
            }
        }

        $this->addSql('ALTER TABLE credentials ALTER username TYPE VARCHAR(255)');
        $this->addSql('ALTER TABLE credentials ALTER password TYPE VARCHAR(255)');
        $this->addSql('ALTER TABLE credentials ALTER token TYPE VARCHAR(255)');
    }
}
