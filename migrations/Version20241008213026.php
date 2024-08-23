<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20241008213026 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add downloads';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('CREATE TABLE package_downloads (package_id INT NOT NULL, historical_data JSON NOT NULL, recent_data JSON NOT NULL, total INT NOT NULL, updated_at TIMESTAMP(0) WITHOUT TIME ZONE DEFAULT NULL, merged_at TIMESTAMP(0) WITHOUT TIME ZONE DEFAULT NULL, PRIMARY KEY(package_id))');
        $this->addSql('COMMENT ON COLUMN package_downloads.updated_at IS \'(DC2Type:datetime_immutable)\'');
        $this->addSql('COMMENT ON COLUMN package_downloads.merged_at IS \'(DC2Type:datetime_immutable)\'');
        $this->addSql('CREATE TABLE version_downloads (version_id INT NOT NULL, historical_data JSON NOT NULL, recent_data JSON NOT NULL, total INT NOT NULL, updated_at TIMESTAMP(0) WITHOUT TIME ZONE DEFAULT NULL, merged_at TIMESTAMP(0) WITHOUT TIME ZONE DEFAULT NULL, PRIMARY KEY(version_id))');
        $this->addSql('COMMENT ON COLUMN version_downloads.updated_at IS \'(DC2Type:datetime_immutable)\'');
        $this->addSql('COMMENT ON COLUMN version_downloads.merged_at IS \'(DC2Type:datetime_immutable)\'');
        $this->addSql('ALTER TABLE package_downloads ADD CONSTRAINT FK_E19D697EF44CABFF FOREIGN KEY (package_id) REFERENCES package (id) NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('ALTER TABLE version_downloads ADD CONSTRAINT FK_45C4D6DB4BBC2705 FOREIGN KEY (version_id) REFERENCES version (id) NOT DEFERRABLE INITIALLY IMMEDIATE');

        $this->addSql('INSERT INTO package_downloads (package_id, historical_data, recent_data, total) SELECT id, \'{}\', \'{}\', 0 FROM package');
        $this->addSql('INSERT INTO version_downloads (version_id, historical_data, recent_data, total) SELECT id, \'{}\', \'{}\', 0 FROM version');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE package_downloads DROP CONSTRAINT FK_E19D697EF44CABFF');
        $this->addSql('ALTER TABLE version_downloads DROP CONSTRAINT FK_45C4D6DB4BBC2705');
        $this->addSql('DROP TABLE package_downloads');
        $this->addSql('DROP TABLE version_downloads');
    }
}
