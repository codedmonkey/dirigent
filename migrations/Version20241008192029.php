<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20241008192029 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add credentials token';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE credentials ADD token VARCHAR(255) DEFAULT NULL');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE credentials DROP token');
    }
}
