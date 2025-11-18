<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20251112201434 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE meal DROP image_file');
        $this->addSql('ALTER TABLE "user" DROP password_setup_token');
        $this->addSql('ALTER TABLE "user" DROP password_setup_token_expires_at');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE SCHEMA public');
        $this->addSql('ALTER TABLE "user" ADD password_setup_token VARCHAR(255) DEFAULT NULL');
        $this->addSql('ALTER TABLE "user" ADD password_setup_token_expires_at TIMESTAMP(0) WITHOUT TIME ZONE DEFAULT NULL');
        $this->addSql('COMMENT ON COLUMN "user".password_setup_token_expires_at IS \'(DC2Type:datetime_immutable)\'');
        $this->addSql('ALTER TABLE meal ADD image_file VARCHAR(255) DEFAULT NULL');
    }
}
