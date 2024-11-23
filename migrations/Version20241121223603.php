<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;
use Symfony\Component\PasswordHasher\Hasher\NativePasswordHasher;

final class Version20241121223603 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Hash access tokens';
    }

    public function up(Schema $schema): void
    {
        $accessTokenHasher = new NativePasswordHasher();
        $accessTokens = $this->connection->fetchAllAssociative('SELECT id, token FROM access_token');

        foreach ($accessTokens as $accessToken) {
            if (str_starts_with($accessToken['token'], 'conductor-')) {
                $hashedToken = $accessTokenHasher->hash($accessToken['token']);

                $this->addSql('UPDATE access_token SET token = ? WHERE id = ?', [$hashedToken, $accessToken['id']]);
            }
        }
    }

    public function down(Schema $schema): void
    {
        $this->throwIrreversibleMigrationException();
    }
}
