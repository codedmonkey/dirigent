<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20250909181654 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add package distribution strategy';
    }

    public function up(Schema $schema): void
    {
        $this->addSql(<<<'SQL'
            ALTER TABLE package ADD distribution_strategy VARCHAR(255) DEFAULT NULL
        SQL);
        $this->addSql(<<<'SQL'
            UPDATE package SET distribution_strategy = 'dynamic'
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE package ALTER distribution_strategy TYPE VARCHAR(255)
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE package ALTER distribution_strategy SET NOT NULL
        SQL);
    }

    public function down(Schema $schema): void
    {
        $this->addSql(<<<'SQL'
            ALTER TABLE package DROP distribution_strategy
        SQL);
    }
}
