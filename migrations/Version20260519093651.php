<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260519093651 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add pinned property to version table';
    }

    public function up(Schema $schema): void
    {
        $this->addSql(<<<'SQL'
            ALTER TABLE version ADD pinned BOOLEAN DEFAULT NULL
        SQL);
        $this->addSql(<<<'SQL'
            UPDATE version SET pinned = false
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE version ALTER pinned SET NOT NULL
        SQL);
    }

    public function down(Schema $schema): void
    {
        $this->addSql(<<<'SQL'
            ALTER TABLE version DROP pinned
        SQL);
    }
}
