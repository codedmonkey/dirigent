<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20241219124024 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add unique package name constraint';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('CREATE UNIQUE INDEX package_name_idx ON package (name)');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('DROP INDEX package_name_idx');
    }
}
