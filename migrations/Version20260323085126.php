<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260323085126 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add version link indices';
    }

    public function up(Schema $schema): void
    {
        $tables = [
            'version_conflict_link',
            'version_dev_require_link',
            'version_provide_link',
            'version_replace_link',
            'version_require_link',
            'version_suggest_link',
        ];

        foreach ($tables as $table) {
            $this->addSql(<<<SQL
                ALTER TABLE $table ADD index INT DEFAULT NULL
            SQL);
            $this->addSql(<<<SQL
                UPDATE $table t
                SET "index" = sub.row_num - 1
                FROM (
                    SELECT id, ROW_NUMBER() OVER (PARTITION BY version_id ORDER BY id) AS row_num
                    FROM $table
                ) sub
                WHERE t.id = sub.id
            SQL);
            $this->addSql(<<<SQL
                ALTER TABLE $table ALTER COLUMN index SET NOT NULL
            SQL);
        }
    }

    public function down(Schema $schema): void
    {
        $tables = [
            'version_conflict_link',
            'version_dev_require_link',
            'version_provide_link',
            'version_replace_link',
            'version_require_link',
            'version_suggest_link',
        ];

        foreach ($tables as $table) {
            $this->addSql(<<<SQL
                ALTER TABLE $table DROP index
            SQL);
        }
    }
}
