<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20250606221557 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add generalized package link tables';
    }

    public function up(Schema $schema): void
    {
        $this->addSql(<<<'SQL'
            CREATE TABLE package_provide_link (linked_package_name VARCHAR(191) NOT NULL, implementation BOOLEAN NOT NULL, package_id INT NOT NULL, PRIMARY KEY(linked_package_name, package_id, implementation))
        SQL);
        $this->addSql(<<<'SQL'
            CREATE INDEX IDX_BB570504F44CABFF ON package_provide_link (package_id)
        SQL);
        $this->addSql(<<<'SQL'
            CREATE TABLE package_require_link (linked_package_name VARCHAR(191) NOT NULL, dev_dependency BOOLEAN NOT NULL, package_id INT NOT NULL, PRIMARY KEY(linked_package_name, package_id, dev_dependency))
        SQL);
        $this->addSql(<<<'SQL'
            CREATE INDEX IDX_D4456EB9F44CABFF ON package_require_link (package_id)
        SQL);
        $this->addSql(<<<'SQL'
            CREATE TABLE package_suggest_link (linked_package_name VARCHAR(191) NOT NULL, package_id INT NOT NULL, PRIMARY KEY(linked_package_name, package_id))
        SQL);
        $this->addSql(<<<'SQL'
            CREATE INDEX IDX_70C2B280F44CABFF ON package_suggest_link (package_id)
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE package_provide_link ADD CONSTRAINT FK_BB570504F44CABFF FOREIGN KEY (package_id) REFERENCES package (id) NOT DEFERRABLE INITIALLY IMMEDIATE
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE package_require_link ADD CONSTRAINT FK_D4456EB9F44CABFF FOREIGN KEY (package_id) REFERENCES package (id) NOT DEFERRABLE INITIALLY IMMEDIATE
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE package_suggest_link ADD CONSTRAINT FK_70C2B280F44CABFF FOREIGN KEY (package_id) REFERENCES package (id) NOT DEFERRABLE INITIALLY IMMEDIATE
        SQL);
    }

    public function down(Schema $schema): void
    {
        $this->addSql(<<<'SQL'
            ALTER TABLE package_provide_link DROP CONSTRAINT FK_BB570504F44CABFF
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE package_require_link DROP CONSTRAINT FK_D4456EB9F44CABFF
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE package_suggest_link DROP CONSTRAINT FK_70C2B280F44CABFF
        SQL);
        $this->addSql(<<<'SQL'
            DROP TABLE package_provide_link
        SQL);
        $this->addSql(<<<'SQL'
            DROP TABLE package_require_link
        SQL);
        $this->addSql(<<<'SQL'
            DROP TABLE package_suggest_link
        SQL);
    }
}
