<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260513125839 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add next_revision counter to version table';
    }

    public function up(Schema $schema): void
    {
        $this->addSql(<<<'SQL'
            ALTER TABLE version ADD next_revision INT DEFAULT NULL
        SQL);
        $this->addSql(<<<'SQL'
            UPDATE version SET next_revision = COALESCE((SELECT MAX(revision) FROM metadata WHERE metadata.version_id = version.id), 0) + 1
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE version ALTER next_revision SET NOT NULL
        SQL);
    }

    public function down(Schema $schema): void
    {
        $this->addSql(<<<'SQL'
            ALTER TABLE version DROP next_revision
        SQL);
    }
}
