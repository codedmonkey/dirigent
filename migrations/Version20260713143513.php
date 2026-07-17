<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260713143513 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Convert the vcs package fetch strategy to source to preserve behavior of existing packages';
    }

    public function up(Schema $schema): void
    {
        // Previously the vcs fetch strategy used the default Composer drivers (using APIs where
        // possible), which is now the behavior of the source fetch strategy. The vcs fetch
        // strategy now always clones repositories instead.
        $this->addSql(<<<'SQL'
            UPDATE package SET fetch_strategy = 'source' WHERE fetch_strategy = 'vcs'
        SQL);
        // Packages without a fetch strategy or mirror registry fell back to the vcs fetch strategy.
        $this->addSql(<<<'SQL'
            UPDATE package SET fetch_strategy = 'source' WHERE fetch_strategy IS NULL AND mirror_registry_id IS NULL
        SQL);
    }

    public function down(Schema $schema): void
    {
        $this->addSql(<<<'SQL'
            UPDATE package SET fetch_strategy = 'vcs' WHERE fetch_strategy = 'source'
        SQL);
    }
}
