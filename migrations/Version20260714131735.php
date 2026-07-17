<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260714131735 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Make the package fetch strategy required';
    }

    public function up(Schema $schema): void
    {
        // Make the package fetch strategy required while preserving the previous default for existing packages
        $this->addSql(<<<'SQL'
            UPDATE package
            SET fetch_strategy = CASE WHEN mirror_registry_id IS NULL THEN 'source' ELSE 'mirror' END
            WHERE fetch_strategy IS NULL
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE package ALTER fetch_strategy SET NOT NULL
        SQL);
    }

    public function down(Schema $schema): void
    {
        $this->addSql(<<<'SQL'
            ALTER TABLE package ALTER fetch_strategy DROP NOT NULL
        SQL);
    }
}
