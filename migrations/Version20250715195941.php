<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20250715195941 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add last modified columns';
    }

    public function up(Schema $schema): void
    {
        $this->addSql(<<<'SQL'
            ALTER TABLE access_token ADD last_modified_at TIMESTAMP(0) WITHOUT TIME ZONE DEFAULT NULL
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE version ADD last_modified_at TIMESTAMP(0) WITHOUT TIME ZONE DEFAULT NULL
        SQL);
    }

    public function down(Schema $schema): void
    {
        $this->addSql(<<<'SQL'
            ALTER TABLE access_token DROP last_modified_at
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE version DROP last_modified_at
        SQL);
    }
}
