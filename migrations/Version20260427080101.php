<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use CodedMonkey\Dirigent\Doctrine\Entity\User;
use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;
use Psr\Log\LoggerInterface;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

final class Version20260427080101 extends AbstractMigration
{
    public function __construct(
        Connection $connection,
        LoggerInterface $logger,
        private readonly UserPasswordHasherInterface $passwordHasher,
    ) {
        parent::__construct($connection, $logger);
    }

    public function getDescription(): string
    {
        return 'Add OAuth info to users';
    }

    public function up(Schema $schema): void
    {
        $this->addSql(<<<'SQL'
            ALTER TABLE "user" ADD oauth_provider VARCHAR(255) DEFAULT NULL
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE "user" ADD oauth_sub VARCHAR(255) DEFAULT NULL
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE "user" ALTER password DROP NOT NULL
        SQL);
    }

    public function down(Schema $schema): void
    {
        $this->addSql(<<<'SQL'
            ALTER TABLE "user" DROP oauth_provider
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE "user" DROP oauth_sub
        SQL);

        // Generate a random password for each user that loses its OAuth credentials
        $users = $this->connection->fetchAllAssociative('SELECT id FROM "user" WHERE password IS NULL');
        foreach ($users as $user) {
            $hashedPassword = $this->passwordHasher->hashPassword(new User(), bin2hex(random_bytes(16)));
            $this->addSql(<<<'SQL'
                UPDATE "user" SET password = ? WHERE id = ?
            SQL, [$hashedPassword, $user['id']]);
        }

        $this->addSql(<<<'SQL'
            ALTER TABLE "user" ALTER password SET NOT NULL
        SQL);
    }
}
