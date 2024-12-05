<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20241209221654 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Rename downloads tables';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE package_downloads DROP CONSTRAINT fk_e19d697ef44cabff');
        $this->addSql('ALTER TABLE version_downloads DROP CONSTRAINT fk_45c4d6db4bbc2705');
        $this->addSql('ALTER TABLE package_downloads RENAME TO package_installations');
        $this->addSql('ALTER TABLE version_downloads RENAME TO version_installations');
        $this->addSql('ALTER TABLE package_installations ADD CONSTRAINT FK_69B10EFBF44CABFF FOREIGN KEY (package_id) REFERENCES package (id) NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('ALTER TABLE version_installations ADD CONSTRAINT FK_67C4DF0D4BBC2705 FOREIGN KEY (version_id) REFERENCES version (id) NOT DEFERRABLE INITIALLY IMMEDIATE');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE package_installations DROP CONSTRAINT FK_69B10EFBF44CABFF');
        $this->addSql('ALTER TABLE version_installations DROP CONSTRAINT FK_67C4DF0D4BBC2705');
        $this->addSql('ALTER TABLE package_installations RENAME TO package_downloads');
        $this->addSql('ALTER TABLE version_installations RENAME TO version_downloads');
        $this->addSql('ALTER TABLE package_downloads ADD CONSTRAINT fk_e19d697ef44cabff FOREIGN KEY (package_id) REFERENCES package (id) NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('ALTER TABLE version_downloads ADD CONSTRAINT fk_45c4d6db4bbc2705 FOREIGN KEY (version_id) REFERENCES version (id) NOT DEFERRABLE INITIALLY IMMEDIATE');
    }
}
