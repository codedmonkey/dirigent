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
        $this->addSql('CREATE TABLE dependent (dependent_package_name VARCHAR(191) NOT NULL, dev_dependency BOOLEAN NOT NULL, package_id INT NOT NULL, PRIMARY KEY(package_id, dependent_package_name, dev_dependency))');
        $this->addSql('CREATE INDEX IDX_BB9077A4F44CABFF ON dependent (package_id)');
        $this->addSql('CREATE TABLE provider (provided_package_name VARCHAR(191) NOT NULL, package_id INT NOT NULL, PRIMARY KEY(package_id, provided_package_name))');
        $this->addSql('CREATE INDEX IDX_92C4739CF44CABFF ON provider (package_id)');
        $this->addSql('CREATE TABLE suggester (suggested_package_name VARCHAR(191) NOT NULL, package_id INT NOT NULL, PRIMARY KEY(package_id, suggested_package_name))');
        $this->addSql('CREATE INDEX IDX_4D5681F2F44CABFF ON suggester (package_id)');
        $this->addSql('ALTER TABLE dependent ADD CONSTRAINT FK_BB9077A4F44CABFF FOREIGN KEY (package_id) REFERENCES package (id) NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('ALTER TABLE provider ADD CONSTRAINT FK_92C4739CF44CABFF FOREIGN KEY (package_id) REFERENCES package (id) NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('ALTER TABLE suggester ADD CONSTRAINT FK_4D5681F2F44CABFF FOREIGN KEY (package_id) REFERENCES package (id) NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('ALTER TABLE conflict_link RENAME COLUMN package_name TO linked_package_name');
        $this->addSql('ALTER TABLE conflict_link RENAME COLUMN package_version TO linked_version_constraint');
        $this->addSql('ALTER TABLE dev_require_link RENAME COLUMN package_name TO linked_package_name');
        $this->addSql('ALTER TABLE dev_require_link RENAME COLUMN package_version TO linked_version_constraint');
        $this->addSql('ALTER TABLE provide_link RENAME COLUMN package_name TO linked_package_name');
        $this->addSql('ALTER TABLE provide_link RENAME COLUMN package_version TO linked_version_constraint');
        $this->addSql('ALTER TABLE replace_link RENAME COLUMN package_name TO linked_package_name');
        $this->addSql('ALTER TABLE replace_link RENAME COLUMN package_version TO linked_version_constraint');
        $this->addSql('ALTER TABLE require_link RENAME COLUMN package_name TO linked_package_name');
        $this->addSql('ALTER TABLE require_link RENAME COLUMN package_version TO linked_version_constraint');
        $this->addSql('ALTER TABLE suggest_link RENAME COLUMN package_name TO linked_package_name');
        $this->addSql('ALTER TABLE suggest_link RENAME COLUMN package_version TO linked_version_constraint');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE dependent DROP CONSTRAINT FK_BB9077A4F44CABFF');
        $this->addSql('ALTER TABLE provider DROP CONSTRAINT FK_92C4739CF44CABFF');
        $this->addSql('ALTER TABLE suggester DROP CONSTRAINT FK_4D5681F2F44CABFF');
        $this->addSql('DROP TABLE dependent');
        $this->addSql('DROP TABLE provider');
        $this->addSql('DROP TABLE suggester');
        $this->addSql('ALTER TABLE suggest_link RENAME COLUMN linked_package_name TO package_name');
        $this->addSql('ALTER TABLE suggest_link RENAME COLUMN linked_version_constraint TO package_version');
        $this->addSql('ALTER TABLE provide_link RENAME COLUMN linked_package_name TO package_name');
        $this->addSql('ALTER TABLE provide_link RENAME COLUMN linked_version_constraint TO package_version');
        $this->addSql('ALTER TABLE dev_require_link RENAME COLUMN linked_package_name TO package_name');
        $this->addSql('ALTER TABLE dev_require_link RENAME COLUMN linked_version_constraint TO package_version');
        $this->addSql('ALTER TABLE conflict_link RENAME COLUMN linked_package_name TO package_name');
        $this->addSql('ALTER TABLE conflict_link RENAME COLUMN linked_version_constraint TO package_version');
        $this->addSql('ALTER TABLE replace_link RENAME COLUMN linked_package_name TO package_name');
        $this->addSql('ALTER TABLE replace_link RENAME COLUMN linked_version_constraint TO package_version');
        $this->addSql('ALTER TABLE require_link RENAME COLUMN linked_package_name TO package_name');
        $this->addSql('ALTER TABLE require_link RENAME COLUMN linked_version_constraint TO package_version');
    }
}
