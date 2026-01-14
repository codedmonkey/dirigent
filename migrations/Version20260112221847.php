<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260112221847 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Refactor user roles from JSON array to single role enum';
    }

    public function up(Schema $schema): void
    {
        // Add new role column as nullable
        $this->addSql('ALTER TABLE "user" ADD role VARCHAR(64) DEFAULT NULL');

        // Migrate existing data: extract the highest privilege role from the roles array
        $this->addSql(<<<'SQL'
            UPDATE "user"
            SET role = CASE
                WHEN roles::text LIKE '%ROLE_SUPER_ADMIN%' THEN 'ROLE_SUPER_ADMIN'
                WHEN roles::text LIKE '%ROLE_ADMIN%' THEN 'ROLE_ADMIN'
                ELSE 'ROLE_USER'
            END
        SQL);

        // Make role column NOT NULL
        $this->addSql('ALTER TABLE "user" ALTER COLUMN role SET NOT NULL');

        // Drop the old roles column
        $this->addSql('ALTER TABLE "user" DROP roles');
    }

    public function down(Schema $schema): void
    {
        // Add back the roles column
        $this->addSql('ALTER TABLE "user" ADD roles JSON DEFAULT NULL');

        // Migrate data back: convert single role to array
        $this->addSql(<<<'SQL'
            UPDATE "user"
            SET roles = CASE
                WHEN role = 'ROLE_SUPER_ADMIN' THEN '["ROLE_SUPER_ADMIN"]'::json
                WHEN role = 'ROLE_ADMIN' THEN '["ROLE_ADMIN"]'::json
                ELSE '[]'::json
            END
        SQL);

        // Make roles column NOT NULL
        $this->addSql('ALTER TABLE "user" ALTER COLUMN roles SET NOT NULL');

        // Drop the new role column
        $this->addSql('ALTER TABLE "user" DROP role');
    }
}
