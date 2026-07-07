<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260707081705 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Replace the source and dist json fields of metadata with individual fields';
    }

    public function up(Schema $schema): void
    {
        $this->addSql(<<<'SQL'
            ALTER TABLE metadata ADD source_type VARCHAR(255) DEFAULT NULL
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE metadata ADD source_url VARCHAR(255) DEFAULT NULL
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE metadata ADD source_reference VARCHAR(255) DEFAULT NULL
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE metadata ADD distribution_type VARCHAR(255) DEFAULT NULL
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE metadata ADD distribution_url VARCHAR(255) DEFAULT NULL
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE metadata ADD distribution_reference VARCHAR(255) DEFAULT NULL
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE metadata ADD distribution_sha1_checksum VARCHAR(255) DEFAULT NULL
        SQL);
        $this->addSql(<<<'SQL'
            UPDATE metadata SET
                source_type = source ->> 'type',
                source_url = source ->> 'url',
                source_reference = source ->> 'reference',
                distribution_type = dist ->> 'type',
                distribution_url = dist ->> 'url',
                distribution_reference = dist ->> 'reference',
                distribution_sha1_checksum = dist ->> 'shasum'
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE metadata DROP source
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE metadata DROP dist
        SQL);
    }

    public function down(Schema $schema): void
    {
        $this->addSql(<<<'SQL'
            ALTER TABLE metadata ADD source JSON DEFAULT NULL
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE metadata ADD dist JSON DEFAULT NULL
        SQL);
        $this->addSql(<<<'SQL'
            UPDATE metadata SET source = json_build_object(
                'type', source_type,
                'url', source_url,
                'reference', source_reference
            )
            WHERE source_type IS NOT NULL
        SQL);
        $this->addSql(<<<'SQL'
            UPDATE metadata SET dist = json_build_object(
                'type', distribution_type,
                'url', distribution_url,
                'reference', distribution_reference,
                'shasum', distribution_sha1_checksum
            )
            WHERE distribution_type IS NOT NULL
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE metadata DROP source_type
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE metadata DROP source_url
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE metadata DROP source_reference
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE metadata DROP distribution_type
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE metadata DROP distribution_url
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE metadata DROP distribution_reference
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE metadata DROP distribution_sha1_checksum
        SQL);
    }
}
