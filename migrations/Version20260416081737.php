<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260416081737 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        $this->addSql(<<<'SQL'
            ALTER TABLE version ADD pruned BOOLEAN DEFAULT NULL
        SQL);
        $this->addSql(<<<'SQL'
            UPDATE version SET pruned = false
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE version ALTER pruned SET NOT NULL
        SQL);
    }

    public function down(Schema $schema): void
    {
        $this->addSql(<<<'SQL'
            ALTER TABLE version DROP pruned
        SQL);
    }
}
