<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20251109165130 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE order_item (id SERIAL NOT NULL, order_id INT NOT NULL, meal_plan_id INT NOT NULL, quantity INT NOT NULL, PRIMARY KEY(id))');
        $this->addSql('CREATE INDEX IDX_52EA1F098D9F6D38 ON order_item (order_id)');
        $this->addSql('CREATE INDEX IDX_52EA1F09912AB082 ON order_item (meal_plan_id)');
        $this->addSql('ALTER TABLE order_item ADD CONSTRAINT FK_52EA1F098D9F6D38 FOREIGN KEY (order_id) REFERENCES "order" (id) NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('ALTER TABLE order_item ADD CONSTRAINT FK_52EA1F09912AB082 FOREIGN KEY (meal_plan_id) REFERENCES meal_plan (id) NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('ALTER TABLE order_meal_plan DROP CONSTRAINT fk_7b03f3718d9f6d38');
        $this->addSql('ALTER TABLE order_meal_plan DROP CONSTRAINT fk_7b03f371912ab082');
        $this->addSql('DROP TABLE order_meal_plan');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE SCHEMA public');
        $this->addSql('CREATE TABLE order_meal_plan (order_id INT NOT NULL, meal_plan_id INT NOT NULL, PRIMARY KEY(order_id, meal_plan_id))');
        $this->addSql('CREATE INDEX idx_7b03f3718d9f6d38 ON order_meal_plan (order_id)');
        $this->addSql('CREATE INDEX idx_7b03f371912ab082 ON order_meal_plan (meal_plan_id)');
        $this->addSql('ALTER TABLE order_meal_plan ADD CONSTRAINT fk_7b03f3718d9f6d38 FOREIGN KEY (order_id) REFERENCES "order" (id) ON DELETE CASCADE NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('ALTER TABLE order_meal_plan ADD CONSTRAINT fk_7b03f371912ab082 FOREIGN KEY (meal_plan_id) REFERENCES meal_plan (id) ON DELETE CASCADE NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('ALTER TABLE order_item DROP CONSTRAINT FK_52EA1F098D9F6D38');
        $this->addSql('ALTER TABLE order_item DROP CONSTRAINT FK_52EA1F09912AB082');
        $this->addSql('DROP TABLE order_item');
    }
}
