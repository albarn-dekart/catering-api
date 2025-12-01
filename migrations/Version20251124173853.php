<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20251124173853 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE address (id SERIAL NOT NULL, user_id INT NOT NULL, first_name VARCHAR(255) NOT NULL, last_name VARCHAR(255) NOT NULL, phone_number VARCHAR(255) NOT NULL, street VARCHAR(255) NOT NULL, apartment VARCHAR(255) DEFAULT NULL, city VARCHAR(255) NOT NULL, zip_code VARCHAR(255) NOT NULL, is_default BOOLEAN NOT NULL, PRIMARY KEY(id))');
        $this->addSql('CREATE INDEX IDX_D4E6F81A76ED395 ON address (user_id)');
        $this->addSql('ALTER TABLE address ADD CONSTRAINT FK_D4E6F81A76ED395 FOREIGN KEY (user_id) REFERENCES "user" (id) NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('ALTER TABLE "order" ADD delivery_last_name VARCHAR(255) DEFAULT NULL');
        $this->addSql('ALTER TABLE "order" ADD delivery_phone_number VARCHAR(255) DEFAULT NULL');
        $this->addSql('ALTER TABLE "order" ADD delivery_street VARCHAR(255) DEFAULT NULL');
        $this->addSql('ALTER TABLE "order" ADD delivery_apartment VARCHAR(255) DEFAULT NULL');
        $this->addSql('ALTER TABLE "order" ADD delivery_city VARCHAR(255) DEFAULT NULL');
        $this->addSql('ALTER TABLE "order" ADD delivery_zip_code VARCHAR(255) DEFAULT NULL');
        $this->addSql('ALTER TABLE "order" RENAME COLUMN delivery_address TO delivery_first_name');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE SCHEMA public');
        $this->addSql('ALTER TABLE address DROP CONSTRAINT FK_D4E6F81A76ED395');
        $this->addSql('DROP TABLE address');
        $this->addSql('ALTER TABLE "order" ADD delivery_address VARCHAR(255) DEFAULT NULL');
        $this->addSql('ALTER TABLE "order" DROP delivery_first_name');
        $this->addSql('ALTER TABLE "order" DROP delivery_last_name');
        $this->addSql('ALTER TABLE "order" DROP delivery_phone_number');
        $this->addSql('ALTER TABLE "order" DROP delivery_street');
        $this->addSql('ALTER TABLE "order" DROP delivery_apartment');
        $this->addSql('ALTER TABLE "order" DROP delivery_city');
        $this->addSql('ALTER TABLE "order" DROP delivery_zip_code');
    }
}
