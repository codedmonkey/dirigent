<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20240903123717 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Initial schema';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('CREATE SEQUENCE access_token_id_seq INCREMENT BY 1 MINVALUE 1 START 1');
        $this->addSql('CREATE SEQUENCE conflict_link_id_seq INCREMENT BY 1 MINVALUE 1 START 1');
        $this->addSql('CREATE SEQUENCE credentials_id_seq INCREMENT BY 1 MINVALUE 1 START 1');
        $this->addSql('CREATE SEQUENCE dev_require_link_id_seq INCREMENT BY 1 MINVALUE 1 START 1');
        $this->addSql('CREATE SEQUENCE package_id_seq INCREMENT BY 1 MINVALUE 1 START 1');
        $this->addSql('CREATE SEQUENCE provide_link_id_seq INCREMENT BY 1 MINVALUE 1 START 1');
        $this->addSql('CREATE SEQUENCE registry_id_seq INCREMENT BY 1 MINVALUE 1 START 1');
        $this->addSql('CREATE SEQUENCE replace_link_id_seq INCREMENT BY 1 MINVALUE 1 START 1');
        $this->addSql('CREATE SEQUENCE require_link_id_seq INCREMENT BY 1 MINVALUE 1 START 1');
        $this->addSql('CREATE SEQUENCE reset_password_request_id_seq INCREMENT BY 1 MINVALUE 1 START 1');
        $this->addSql('CREATE SEQUENCE suggest_link_id_seq INCREMENT BY 1 MINVALUE 1 START 1');
        $this->addSql('CREATE SEQUENCE tag_id_seq INCREMENT BY 1 MINVALUE 1 START 1');
        $this->addSql('CREATE SEQUENCE "user_id_seq" INCREMENT BY 1 MINVALUE 1 START 1');
        $this->addSql('CREATE SEQUENCE version_id_seq INCREMENT BY 1 MINVALUE 1 START 1');
        $this->addSql('CREATE TABLE access_token (id INT NOT NULL, user_id INT DEFAULT NULL, name VARCHAR(255) NOT NULL, token VARCHAR(255) NOT NULL, created_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL, expires_at TIMESTAMP(0) WITHOUT TIME ZONE DEFAULT NULL, PRIMARY KEY(id))');
        $this->addSql('CREATE INDEX IDX_B6A2DD68A76ED395 ON access_token (user_id)');
        $this->addSql('COMMENT ON COLUMN access_token.created_at IS \'(DC2Type:datetime_immutable)\'');
        $this->addSql('COMMENT ON COLUMN access_token.expires_at IS \'(DC2Type:datetime_immutable)\'');
        $this->addSql('CREATE TABLE conflict_link (id INT NOT NULL, version_id INT NOT NULL, package_name VARCHAR(191) NOT NULL, package_version TEXT NOT NULL, PRIMARY KEY(id))');
        $this->addSql('CREATE INDEX IDX_CA65041A4BBC2705 ON conflict_link (version_id)');
        $this->addSql('CREATE TABLE credentials (id INT NOT NULL, name VARCHAR(255) NOT NULL, description TEXT DEFAULT NULL, type VARCHAR(255) NOT NULL, username VARCHAR(255) DEFAULT NULL, password VARCHAR(255) DEFAULT NULL, token VARCHAR(255) DEFAULT NULL, PRIMARY KEY(id))');
        $this->addSql('CREATE TABLE dev_require_link (id INT NOT NULL, version_id INT NOT NULL, package_name VARCHAR(191) NOT NULL, package_version TEXT NOT NULL, PRIMARY KEY(id))');
        $this->addSql('CREATE INDEX IDX_47A9DC2B4BBC2705 ON dev_require_link (version_id)');
        $this->addSql('CREATE TABLE package (id INT NOT NULL, repository_credentials_id INT DEFAULT NULL, mirror_registry_id INT DEFAULT NULL, name VARCHAR(191) NOT NULL, vendor VARCHAR(191) NOT NULL, description TEXT DEFAULT NULL, type VARCHAR(255) DEFAULT NULL, language VARCHAR(255) DEFAULT NULL, readme TEXT DEFAULT NULL, abandoned BOOLEAN NOT NULL, replacement_package VARCHAR(255) DEFAULT NULL, repository_type VARCHAR(255) DEFAULT NULL, repository_url VARCHAR(255) DEFAULT NULL, remote_id VARCHAR(255) DEFAULT NULL, created_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL, updated_at TIMESTAMP(0) WITHOUT TIME ZONE DEFAULT NULL, update_scheduled_at TIMESTAMP(0) WITHOUT TIME ZONE DEFAULT NULL, dumped_at TIMESTAMP(0) WITHOUT TIME ZONE DEFAULT NULL, PRIMARY KEY(id))');
        $this->addSql('CREATE INDEX IDX_DE6867957E2C3553 ON package (repository_credentials_id)');
        $this->addSql('CREATE INDEX IDX_DE686795FE0A670E ON package (mirror_registry_id)');
        $this->addSql('CREATE TABLE provide_link (id INT NOT NULL, version_id INT NOT NULL, package_name VARCHAR(191) NOT NULL, package_version TEXT NOT NULL, PRIMARY KEY(id))');
        $this->addSql('CREATE INDEX IDX_1F4B1A734BBC2705 ON provide_link (version_id)');
        $this->addSql('CREATE TABLE registry (id INT NOT NULL, credentials_id INT DEFAULT NULL, name VARCHAR(255) NOT NULL, description TEXT DEFAULT NULL, url VARCHAR(1024) NOT NULL, package_mirroring VARCHAR(255) NOT NULL, mirroring_priority INT NOT NULL, dynamic_update_delay VARCHAR(255) DEFAULT NULL, PRIMARY KEY(id))');
        $this->addSql('CREATE INDEX IDX_CDA81D0A41E8B2E5 ON registry (credentials_id)');
        $this->addSql('COMMENT ON COLUMN registry.dynamic_update_delay IS \'(DC2Type:dateinterval)\'');
        $this->addSql('CREATE TABLE replace_link (id INT NOT NULL, version_id INT NOT NULL, package_name VARCHAR(191) NOT NULL, package_version TEXT NOT NULL, PRIMARY KEY(id))');
        $this->addSql('CREATE INDEX IDX_8DB462154BBC2705 ON replace_link (version_id)');
        $this->addSql('CREATE TABLE require_link (id INT NOT NULL, version_id INT NOT NULL, package_name VARCHAR(191) NOT NULL, package_version TEXT NOT NULL, PRIMARY KEY(id))');
        $this->addSql('CREATE INDEX IDX_705971CE4BBC2705 ON require_link (version_id)');
        $this->addSql('CREATE TABLE reset_password_request (id INT NOT NULL, user_id INT NOT NULL, selector VARCHAR(20) NOT NULL, hashed_token VARCHAR(100) NOT NULL, requested_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL, expires_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL, PRIMARY KEY(id))');
        $this->addSql('CREATE INDEX IDX_7CE748AA76ED395 ON reset_password_request (user_id)');
        $this->addSql('COMMENT ON COLUMN reset_password_request.requested_at IS \'(DC2Type:datetime_immutable)\'');
        $this->addSql('COMMENT ON COLUMN reset_password_request.expires_at IS \'(DC2Type:datetime_immutable)\'');
        $this->addSql('CREATE TABLE suggest_link (id INT NOT NULL, version_id INT NOT NULL, package_name VARCHAR(191) NOT NULL, package_version TEXT NOT NULL, PRIMARY KEY(id))');
        $this->addSql('CREATE INDEX IDX_D4DEADF74BBC2705 ON suggest_link (version_id)');
        $this->addSql('CREATE TABLE tag (id INT NOT NULL, name VARCHAR(191) NOT NULL, PRIMARY KEY(id))');
        $this->addSql('CREATE TABLE "user" (id INT NOT NULL, username VARCHAR(80) NOT NULL, name VARCHAR(180) DEFAULT NULL, email VARCHAR(180) DEFAULT NULL, roles JSON NOT NULL, password VARCHAR(255) NOT NULL, PRIMARY KEY(id))');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_8D93D649E7927C74 ON "user" (email)');
        $this->addSql('CREATE TABLE version (id INT NOT NULL, package_id INT DEFAULT NULL, name VARCHAR(255) NOT NULL, version VARCHAR(255) NOT NULL, normalized_version VARCHAR(191) NOT NULL, description TEXT DEFAULT NULL, readme TEXT DEFAULT NULL, homepage VARCHAR(255) DEFAULT NULL, development BOOLEAN NOT NULL, license JSON NOT NULL, type VARCHAR(255) DEFAULT NULL, target_dir VARCHAR(255) DEFAULT NULL, source JSON DEFAULT NULL, dist JSON DEFAULT NULL, autoload JSON NOT NULL, binaries JSON DEFAULT NULL, include_paths JSON DEFAULT NULL, php_ext JSON DEFAULT NULL, authors JSON DEFAULT NULL, support JSON DEFAULT NULL, funding JSON DEFAULT NULL, extra JSON DEFAULT NULL, default_branch BOOLEAN NOT NULL, created_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL, updated_at TIMESTAMP(0) WITHOUT TIME ZONE DEFAULT NULL, released_at TIMESTAMP(0) WITHOUT TIME ZONE DEFAULT NULL, PRIMARY KEY(id))');
        $this->addSql('CREATE INDEX IDX_BF1CD3C3F44CABFF ON version (package_id)');
        $this->addSql('CREATE UNIQUE INDEX pkg_ver_idx ON version (package_id, normalized_version)');
        $this->addSql('CREATE TABLE version_tag (version_id INT NOT NULL, tag_id INT NOT NULL, PRIMARY KEY(version_id, tag_id))');
        $this->addSql('CREATE INDEX IDX_187C97B84BBC2705 ON version_tag (version_id)');
        $this->addSql('CREATE INDEX IDX_187C97B8BAD26311 ON version_tag (tag_id)');
        $this->addSql('CREATE TABLE messenger_messages (id BIGSERIAL NOT NULL, body TEXT NOT NULL, headers TEXT NOT NULL, queue_name VARCHAR(190) NOT NULL, created_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL, available_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL, delivered_at TIMESTAMP(0) WITHOUT TIME ZONE DEFAULT NULL, PRIMARY KEY(id))');
        $this->addSql('CREATE INDEX IDX_75EA56E0FB7336F0 ON messenger_messages (queue_name)');
        $this->addSql('CREATE INDEX IDX_75EA56E0E3BD61CE ON messenger_messages (available_at)');
        $this->addSql('CREATE INDEX IDX_75EA56E016BA31DB ON messenger_messages (delivered_at)');
        $this->addSql('COMMENT ON COLUMN messenger_messages.created_at IS \'(DC2Type:datetime_immutable)\'');
        $this->addSql('COMMENT ON COLUMN messenger_messages.available_at IS \'(DC2Type:datetime_immutable)\'');
        $this->addSql('COMMENT ON COLUMN messenger_messages.delivered_at IS \'(DC2Type:datetime_immutable)\'');
        $this->addSql('CREATE OR REPLACE FUNCTION notify_messenger_messages() RETURNS TRIGGER AS $$
            BEGIN
                PERFORM pg_notify(\'messenger_messages\', NEW.queue_name::text);
                RETURN NEW;
            END;
        $$ LANGUAGE plpgsql;');
        $this->addSql('DROP TRIGGER IF EXISTS notify_trigger ON messenger_messages;');
        $this->addSql('CREATE TRIGGER notify_trigger AFTER INSERT OR UPDATE ON messenger_messages FOR EACH ROW EXECUTE PROCEDURE notify_messenger_messages();');
        $this->addSql('ALTER TABLE access_token ADD CONSTRAINT FK_B6A2DD68A76ED395 FOREIGN KEY (user_id) REFERENCES "user" (id) NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('ALTER TABLE conflict_link ADD CONSTRAINT FK_CA65041A4BBC2705 FOREIGN KEY (version_id) REFERENCES version (id) NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('ALTER TABLE dev_require_link ADD CONSTRAINT FK_47A9DC2B4BBC2705 FOREIGN KEY (version_id) REFERENCES version (id) NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('ALTER TABLE package ADD CONSTRAINT FK_DE6867957E2C3553 FOREIGN KEY (repository_credentials_id) REFERENCES credentials (id) NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('ALTER TABLE package ADD CONSTRAINT FK_DE686795FE0A670E FOREIGN KEY (mirror_registry_id) REFERENCES registry (id) NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('ALTER TABLE provide_link ADD CONSTRAINT FK_1F4B1A734BBC2705 FOREIGN KEY (version_id) REFERENCES version (id) NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('ALTER TABLE registry ADD CONSTRAINT FK_CDA81D0A41E8B2E5 FOREIGN KEY (credentials_id) REFERENCES credentials (id) NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('ALTER TABLE replace_link ADD CONSTRAINT FK_8DB462154BBC2705 FOREIGN KEY (version_id) REFERENCES version (id) NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('ALTER TABLE require_link ADD CONSTRAINT FK_705971CE4BBC2705 FOREIGN KEY (version_id) REFERENCES version (id) NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('ALTER TABLE reset_password_request ADD CONSTRAINT FK_7CE748AA76ED395 FOREIGN KEY (user_id) REFERENCES "user" (id) NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('ALTER TABLE suggest_link ADD CONSTRAINT FK_D4DEADF74BBC2705 FOREIGN KEY (version_id) REFERENCES version (id) NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('ALTER TABLE version ADD CONSTRAINT FK_BF1CD3C3F44CABFF FOREIGN KEY (package_id) REFERENCES package (id) NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('ALTER TABLE version_tag ADD CONSTRAINT FK_187C97B84BBC2705 FOREIGN KEY (version_id) REFERENCES version (id) ON DELETE CASCADE NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('ALTER TABLE version_tag ADD CONSTRAINT FK_187C97B8BAD26311 FOREIGN KEY (tag_id) REFERENCES tag (id) ON DELETE CASCADE NOT DEFERRABLE INITIALLY IMMEDIATE');

        $this->addSql(
            'INSERT INTO registry (id, name, description, url, package_mirroring, mirroring_priority) VALUES (nextval(\'registry_id_seq\'), ?, ?, ?, ?, ?);',
            ['Packagist', 'The PHP Package Repository', 'https://repo.packagist.org', 'manual', 1],
        );
    }

    public function down(Schema $schema): void
    {
        $this->addSql('CREATE SCHEMA public');
        $this->addSql('DROP SEQUENCE access_token_id_seq CASCADE');
        $this->addSql('DROP SEQUENCE conflict_link_id_seq CASCADE');
        $this->addSql('DROP SEQUENCE credentials_id_seq CASCADE');
        $this->addSql('DROP SEQUENCE dev_require_link_id_seq CASCADE');
        $this->addSql('DROP SEQUENCE package_id_seq CASCADE');
        $this->addSql('DROP SEQUENCE provide_link_id_seq CASCADE');
        $this->addSql('DROP SEQUENCE registry_id_seq CASCADE');
        $this->addSql('DROP SEQUENCE replace_link_id_seq CASCADE');
        $this->addSql('DROP SEQUENCE require_link_id_seq CASCADE');
        $this->addSql('DROP SEQUENCE reset_password_request_id_seq CASCADE');
        $this->addSql('DROP SEQUENCE suggest_link_id_seq CASCADE');
        $this->addSql('DROP SEQUENCE tag_id_seq CASCADE');
        $this->addSql('DROP SEQUENCE "user_id_seq" CASCADE');
        $this->addSql('DROP SEQUENCE version_id_seq CASCADE');
        $this->addSql('ALTER TABLE access_token DROP CONSTRAINT FK_B6A2DD68A76ED395');
        $this->addSql('ALTER TABLE conflict_link DROP CONSTRAINT FK_CA65041A4BBC2705');
        $this->addSql('ALTER TABLE dev_require_link DROP CONSTRAINT FK_47A9DC2B4BBC2705');
        $this->addSql('ALTER TABLE package DROP CONSTRAINT FK_DE6867957E2C3553');
        $this->addSql('ALTER TABLE package DROP CONSTRAINT FK_DE686795FE0A670E');
        $this->addSql('ALTER TABLE provide_link DROP CONSTRAINT FK_1F4B1A734BBC2705');
        $this->addSql('ALTER TABLE registry DROP CONSTRAINT FK_CDA81D0A41E8B2E5');
        $this->addSql('ALTER TABLE replace_link DROP CONSTRAINT FK_8DB462154BBC2705');
        $this->addSql('ALTER TABLE require_link DROP CONSTRAINT FK_705971CE4BBC2705');
        $this->addSql('ALTER TABLE reset_password_request DROP CONSTRAINT FK_7CE748AA76ED395');
        $this->addSql('ALTER TABLE suggest_link DROP CONSTRAINT FK_D4DEADF74BBC2705');
        $this->addSql('ALTER TABLE version DROP CONSTRAINT FK_BF1CD3C3F44CABFF');
        $this->addSql('ALTER TABLE version_tag DROP CONSTRAINT FK_187C97B84BBC2705');
        $this->addSql('ALTER TABLE version_tag DROP CONSTRAINT FK_187C97B8BAD26311');
        $this->addSql('DROP TABLE access_token');
        $this->addSql('DROP TABLE conflict_link');
        $this->addSql('DROP TABLE credentials');
        $this->addSql('DROP TABLE dev_require_link');
        $this->addSql('DROP TABLE package');
        $this->addSql('DROP TABLE provide_link');
        $this->addSql('DROP TABLE registry');
        $this->addSql('DROP TABLE replace_link');
        $this->addSql('DROP TABLE require_link');
        $this->addSql('DROP TABLE reset_password_request');
        $this->addSql('DROP TABLE suggest_link');
        $this->addSql('DROP TABLE tag');
        $this->addSql('DROP TABLE "user"');
        $this->addSql('DROP TABLE version');
        $this->addSql('DROP TABLE version_tag');
        $this->addSql('DROP TABLE messenger_messages');
    }
}
