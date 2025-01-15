<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20250115140046 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Reset sequences';
    }

    public function up(Schema $schema): void
    {
        $tables = [
            'access_token',
            'conflict_link',
            'credentials',
            'dev_require_link',
            'messenger_messages',
            'package',
            'provide_link',
            'registry',
            'replace_link',
            'require_link',
            'reset_password_request',
            'suggest_link',
            'tag',
            'user',
            'version',
        ];

        foreach ($tables as $table) {
            $this->addSql("DROP SEQUENCE {$table}_id_seq");
            $this->addSql("ALTER SEQUENCE {$table}_id_seq1 RENAME TO {$table}_id_seq");
            $this->addSql("SELECT setval(pg_get_serial_sequence('$table', 'id'), (SELECT max(id) FROM \"$table\"), true);");
        }
    }

    public function down(Schema $schema): void
    {
        $tables = [
            'access_token',
            'conflict_link',
            'credentials',
            'dev_require_link',
            'messenger_messages',
            'package',
            'provide_link',
            'registry',
            'replace_link',
            'require_link',
            'reset_password_request',
            'suggest_link',
            'tag',
            'user',
            'version',
        ];

        foreach ($tables as $table) {
            $this->addSql("ALTER SEQUENCE {$table}_id_seq RENAME TO {$table}_id_seq1");
            $this->addSql("SELECT setval(pg_get_serial_sequence('$table', 'id'), (SELECT max(id) FROM \"$table\"), true);");
            $this->addSql("CREATE SEQUENCE {$table}_id_seq INCREMENT BY 1 MINVALUE 1 START 1");
        }
    }
}
