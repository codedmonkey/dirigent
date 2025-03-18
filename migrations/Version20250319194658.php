<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20250319194658 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add generalized package link tables';
    }

    public function up(Schema $schema): void
    {
        $this->addSql(<<<'SQL'
            CREATE TABLE dependent (dependent_package_name VARCHAR(191) NOT NULL, dev_dependency BOOLEAN NOT NULL, package_id INT NOT NULL, PRIMARY KEY(package_id, dependent_package_name, dev_dependency))
        SQL);
        $this->addSql(<<<'SQL'
            CREATE INDEX IDX_BB9077A4F44CABFF ON dependent (package_id)
        SQL);
        $this->addSql(<<<'SQL'
            CREATE TABLE provider (provided_package_name VARCHAR(191) NOT NULL, implementation BOOLEAN NOT NULL, package_id INT NOT NULL, PRIMARY KEY(package_id, provided_package_name, implementation))
        SQL);
        $this->addSql(<<<'SQL'
            CREATE INDEX IDX_92C4739CF44CABFF ON provider (package_id)
        SQL);
        $this->addSql(<<<'SQL'
            CREATE TABLE suggester (suggested_package_name VARCHAR(191) NOT NULL, package_id INT NOT NULL, PRIMARY KEY(package_id, suggested_package_name))
        SQL);
        $this->addSql(<<<'SQL'
            CREATE INDEX IDX_4D5681F2F44CABFF ON suggester (package_id)
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE dependent ADD CONSTRAINT FK_BB9077A4F44CABFF FOREIGN KEY (package_id) REFERENCES package (id) NOT DEFERRABLE INITIALLY IMMEDIATE
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE provider ADD CONSTRAINT FK_92C4739CF44CABFF FOREIGN KEY (package_id) REFERENCES package (id) NOT DEFERRABLE INITIALLY IMMEDIATE
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE suggester ADD CONSTRAINT FK_4D5681F2F44CABFF FOREIGN KEY (package_id) REFERENCES package (id) NOT DEFERRABLE INITIALLY IMMEDIATE
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE conflict_link RENAME COLUMN package_name TO linked_package_name
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE conflict_link ALTER linked_package_name TYPE VARCHAR(191)
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE conflict_link RENAME COLUMN package_version TO linked_version_constraint
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE conflict_link ALTER linked_version_constraint TYPE TEXT
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE dev_require_link RENAME COLUMN package_name TO linked_package_name
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE dev_require_link ALTER linked_package_name TYPE VARCHAR(191)
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE dev_require_link RENAME COLUMN package_version TO linked_version_constraint
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE dev_require_link ALTER linked_version_constraint TYPE TEXT
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE provide_link RENAME COLUMN package_name TO linked_package_name
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE provide_link ALTER linked_package_name TYPE VARCHAR(191)
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE provide_link RENAME COLUMN package_version TO linked_version_constraint
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE provide_link ALTER linked_version_constraint TYPE TEXT
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE replace_link RENAME COLUMN package_name TO linked_package_name
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE replace_link ALTER linked_package_name TYPE VARCHAR(191)
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE replace_link RENAME COLUMN package_version TO linked_version_constraint
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE replace_link ALTER linked_version_constraint TYPE TEXT
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE require_link RENAME COLUMN package_name TO linked_package_name
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE require_link ALTER linked_package_name TYPE VARCHAR(191)
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE require_link RENAME COLUMN package_version TO linked_version_constraint
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE require_link ALTER linked_version_constraint TYPE TEXT
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE suggest_link RENAME COLUMN package_name TO linked_package_name
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE suggest_link ALTER linked_package_name TYPE VARCHAR(191)
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE suggest_link RENAME COLUMN package_version TO linked_version_constraint
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE suggest_link ALTER linked_version_constraint TYPE TEXT
        SQL);
    }

    public function down(Schema $schema): void
    {
        $this->addSql(<<<'SQL'
            ALTER TABLE dependent DROP CONSTRAINT FK_BB9077A4F44CABFF
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE provider DROP CONSTRAINT FK_92C4739CF44CABFF
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE suggester DROP CONSTRAINT FK_4D5681F2F44CABFF
        SQL);
        $this->addSql(<<<'SQL'
            DROP TABLE dependent
        SQL);
        $this->addSql(<<<'SQL'
            DROP TABLE provider
        SQL);
        $this->addSql(<<<'SQL'
            DROP TABLE suggester
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE conflict_link RENAME COLUMN linked_package_name TO package_name
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE conflict_link ALTER package_name TYPE VARCHAR(191)
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE conflict_link RENAME COLUMN linked_version_constraint TO package_version
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE conflict_link ALTER package_version TYPE TEXT
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE suggest_link RENAME COLUMN linked_package_name TO package_name
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE suggest_link ALTER package_name TYPE VARCHAR(191)
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE suggest_link RENAME COLUMN linked_version_constraint TO package_version
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE suggest_link ALTER package_version TYPE TEXT
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE dev_require_link RENAME COLUMN linked_package_name TO package_name
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE dev_require_link ALTER package_name TYPE VARCHAR(191)
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE dev_require_link RENAME COLUMN linked_version_constraint TO package_version
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE dev_require_link ALTER package_version TYPE TEXT
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE provide_link RENAME COLUMN linked_package_name TO package_name
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE provide_link ALTER package_name TYPE VARCHAR(191)
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE provide_link RENAME COLUMN linked_version_constraint TO package_version
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE provide_link ALTER package_version TYPE TEXT
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE replace_link RENAME COLUMN linked_package_name TO package_name
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE replace_link ALTER package_name TYPE VARCHAR(191)
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE replace_link RENAME COLUMN linked_version_constraint TO package_version
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE replace_link ALTER package_version TYPE TEXT
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE require_link RENAME COLUMN linked_package_name TO package_name
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE require_link ALTER package_name TYPE VARCHAR(191)
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE require_link RENAME COLUMN linked_version_constraint TO package_version
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE require_link ALTER package_version TYPE TEXT
        SQL);
    }
}
