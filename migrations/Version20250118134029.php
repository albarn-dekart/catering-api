<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20250118134029 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE "order" (id SERIAL NOT NULL, made_by_id INT DEFAULT NULL, start_date DATE NOT NULL, end_date DATE NOT NULL, delivery_days JSON DEFAULT NULL, PRIMARY KEY(id))');
        $this->addSql('CREATE INDEX IDX_F529939890B9D269 ON "order" (made_by_id)');
        $this->addSql('CREATE TABLE order_meal (order_id INT NOT NULL, meal_id INT NOT NULL, PRIMARY KEY(order_id, meal_id))');
        $this->addSql('CREATE INDEX IDX_D307B48B8D9F6D38 ON order_meal (order_id)');
        $this->addSql('CREATE INDEX IDX_D307B48B639666D6 ON order_meal (meal_id)');
        $this->addSql('ALTER TABLE "order" ADD CONSTRAINT FK_F529939890B9D269 FOREIGN KEY (made_by_id) REFERENCES "user" (id) NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('ALTER TABLE order_meal ADD CONSTRAINT FK_D307B48B8D9F6D38 FOREIGN KEY (order_id) REFERENCES "order" (id) ON DELETE CASCADE NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('ALTER TABLE order_meal ADD CONSTRAINT FK_D307B48B639666D6 FOREIGN KEY (meal_id) REFERENCES meal (id) ON DELETE CASCADE NOT DEFERRABLE INITIALLY IMMEDIATE');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE SCHEMA public');
        $this->addSql('ALTER TABLE "order" DROP CONSTRAINT FK_F529939890B9D269');
        $this->addSql('ALTER TABLE order_meal DROP CONSTRAINT FK_D307B48B8D9F6D38');
        $this->addSql('ALTER TABLE order_meal DROP CONSTRAINT FK_D307B48B639666D6');
        $this->addSql('DROP TABLE "order"');
        $this->addSql('DROP TABLE order_meal');
    }
}
