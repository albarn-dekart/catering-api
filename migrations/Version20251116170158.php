<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20251116170158 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('DROP INDEX idx_9ef68e9c5e237e06');
        $this->addSql('ALTER TABLE meal ADD image_path VARCHAR(255) DEFAULT NULL');
        $this->addSql('ALTER TABLE meal ADD updated_at TIMESTAMP(0) WITHOUT TIME ZONE DEFAULT NULL');
        $this->addSql('ALTER TABLE restaurant ALTER owner_id SET NOT NULL');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE SCHEMA public');
        $this->addSql('ALTER TABLE meal DROP image_path');
        $this->addSql('ALTER TABLE meal DROP updated_at');
        $this->addSql('CREATE INDEX idx_9ef68e9c5e237e06 ON meal (name)');
        $this->addSql('ALTER TABLE restaurant ALTER owner_id DROP NOT NULL');
    }
}
