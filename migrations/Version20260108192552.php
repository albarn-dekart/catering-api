<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20260108192552 extends AbstractMigration
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
        $this->addSql('CREATE TABLE delivery (id SERIAL NOT NULL, courier_id INT DEFAULT NULL, order_id INT DEFAULT NULL, status VARCHAR(255) NOT NULL, delivery_date DATE NOT NULL, status_updated_at TIMESTAMP(0) WITHOUT TIME ZONE DEFAULT NULL, PRIMARY KEY(id))');
        $this->addSql('CREATE INDEX IDX_3781EC10E3D8151C ON delivery (courier_id)');
        $this->addSql('CREATE INDEX IDX_3781EC108D9F6D38 ON delivery (order_id)');
        $this->addSql('COMMENT ON COLUMN delivery.delivery_date IS \'(DC2Type:date_immutable)\'');
        $this->addSql('CREATE TABLE diet_category (id SERIAL NOT NULL, name VARCHAR(50) NOT NULL, PRIMARY KEY(id))');
        $this->addSql('CREATE TABLE meal (id SERIAL NOT NULL, restaurant_id INT NOT NULL, name VARCHAR(50) NOT NULL, description TEXT DEFAULT NULL, calories DOUBLE PRECISION NOT NULL, protein DOUBLE PRECISION NOT NULL, fat DOUBLE PRECISION NOT NULL, carbs DOUBLE PRECISION NOT NULL, price INT NOT NULL, image_path VARCHAR(255) DEFAULT NULL, updated_at TIMESTAMP(0) WITHOUT TIME ZONE DEFAULT NULL, PRIMARY KEY(id))');
        $this->addSql('CREATE INDEX IDX_9EF68E9CB1E7706E ON meal (restaurant_id)');
        $this->addSql('CREATE TABLE meal_plan (id SERIAL NOT NULL, restaurant_id INT DEFAULT NULL, owner_id INT DEFAULT NULL, name VARCHAR(50) NOT NULL, description TEXT DEFAULT NULL, price INT DEFAULT NULL, calories DOUBLE PRECISION DEFAULT NULL, protein DOUBLE PRECISION DEFAULT NULL, fat DOUBLE PRECISION DEFAULT NULL, carbs DOUBLE PRECISION DEFAULT NULL, image_path VARCHAR(255) DEFAULT NULL, updated_at TIMESTAMP(0) WITHOUT TIME ZONE DEFAULT NULL, PRIMARY KEY(id))');
        $this->addSql('CREATE INDEX IDX_C7848889B1E7706E ON meal_plan (restaurant_id)');
        $this->addSql('CREATE INDEX IDX_C78488897E3C61F9 ON meal_plan (owner_id)');
        $this->addSql('CREATE TABLE meal_plan_meal (meal_plan_id INT NOT NULL, meal_id INT NOT NULL, PRIMARY KEY(meal_plan_id, meal_id))');
        $this->addSql('CREATE INDEX IDX_354F4065912AB082 ON meal_plan_meal (meal_plan_id)');
        $this->addSql('CREATE INDEX IDX_354F4065639666D6 ON meal_plan_meal (meal_id)');
        $this->addSql('CREATE TABLE meal_plan_diet_category (meal_plan_id INT NOT NULL, diet_category_id INT NOT NULL, PRIMARY KEY(meal_plan_id, diet_category_id))');
        $this->addSql('CREATE INDEX IDX_F72E3CDE912AB082 ON meal_plan_diet_category (meal_plan_id)');
        $this->addSql('CREATE INDEX IDX_F72E3CDE709AC6FC ON meal_plan_diet_category (diet_category_id)');
        $this->addSql('CREATE TABLE "order" (id SERIAL NOT NULL, customer_id INT DEFAULT NULL, restaurant_id INT DEFAULT NULL, status VARCHAR(255) NOT NULL, total INT NOT NULL, payment_intent_id VARCHAR(255) DEFAULT NULL, delivery_first_name VARCHAR(255) DEFAULT NULL, delivery_last_name VARCHAR(255) DEFAULT NULL, delivery_phone_number VARCHAR(255) DEFAULT NULL, delivery_street VARCHAR(255) DEFAULT NULL, delivery_apartment VARCHAR(255) DEFAULT NULL, delivery_city VARCHAR(255) DEFAULT NULL, delivery_zip_code VARCHAR(255) DEFAULT NULL, delivery_fee INT DEFAULT NULL, created_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL, PRIMARY KEY(id))');
        $this->addSql('CREATE INDEX IDX_F52993989395C3F3 ON "order" (customer_id)');
        $this->addSql('CREATE INDEX IDX_F5299398B1E7706E ON "order" (restaurant_id)');
        $this->addSql('CREATE INDEX IDX_F52993987B00651C ON "order" (status)');
        $this->addSql('CREATE TABLE order_item (id SERIAL NOT NULL, order_id INT DEFAULT NULL, meal_plan_id INT DEFAULT NULL, quantity INT NOT NULL, meal_plan_name VARCHAR(100) DEFAULT NULL, meal_plan_price INT DEFAULT NULL, meal_plan_calories DOUBLE PRECISION DEFAULT NULL, meal_plan_protein DOUBLE PRECISION DEFAULT NULL, meal_plan_fat DOUBLE PRECISION DEFAULT NULL, meal_plan_carbs DOUBLE PRECISION DEFAULT NULL, PRIMARY KEY(id))');
        $this->addSql('CREATE INDEX IDX_52EA1F098D9F6D38 ON order_item (order_id)');
        $this->addSql('CREATE INDEX IDX_52EA1F09912AB082 ON order_item (meal_plan_id)');
        $this->addSql('CREATE TABLE refresh_tokens (id SERIAL NOT NULL, refresh_token VARCHAR(128) NOT NULL, username VARCHAR(255) NOT NULL, valid TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL, PRIMARY KEY(id))');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_9BACE7E1C74F2195 ON refresh_tokens (refresh_token)');
        $this->addSql('CREATE TABLE restaurant (id SERIAL NOT NULL, owner_id INT DEFAULT NULL, name VARCHAR(50) NOT NULL, image_path VARCHAR(255) DEFAULT NULL, description TEXT DEFAULT NULL, delivery_price INT DEFAULT 0 NOT NULL, phone_number VARCHAR(20) DEFAULT NULL, email VARCHAR(255) DEFAULT NULL, city VARCHAR(255) DEFAULT NULL, street VARCHAR(255) DEFAULT NULL, zip_code VARCHAR(10) DEFAULT NULL, nip VARCHAR(10) DEFAULT NULL, updated_at TIMESTAMP(0) WITHOUT TIME ZONE DEFAULT NULL, PRIMARY KEY(id))');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_EB95123F7E3C61F9 ON restaurant (owner_id)');
        $this->addSql('CREATE TABLE restaurant_couriers (restaurant_id INT NOT NULL, user_id INT NOT NULL, PRIMARY KEY(restaurant_id, user_id))');
        $this->addSql('CREATE INDEX IDX_62D72FBFB1E7706E ON restaurant_couriers (restaurant_id)');
        $this->addSql('CREATE INDEX IDX_62D72FBFA76ED395 ON restaurant_couriers (user_id)');
        $this->addSql('CREATE TABLE restaurant_restaurant_category (restaurant_id INT NOT NULL, restaurant_category_id INT NOT NULL, PRIMARY KEY(restaurant_id, restaurant_category_id))');
        $this->addSql('CREATE INDEX IDX_A3171BA8B1E7706E ON restaurant_restaurant_category (restaurant_id)');
        $this->addSql('CREATE INDEX IDX_A3171BA8433DA7F8 ON restaurant_restaurant_category (restaurant_category_id)');
        $this->addSql('CREATE TABLE restaurant_category (id SERIAL NOT NULL, name VARCHAR(50) NOT NULL, PRIMARY KEY(id))');
        $this->addSql('CREATE TABLE "user" (id SERIAL NOT NULL, email VARCHAR(180) NOT NULL, password VARCHAR(255) NOT NULL, roles JSON NOT NULL, PRIMARY KEY(id))');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_8D93D649E7927C74 ON "user" (email)');
        $this->addSql('ALTER TABLE address ADD CONSTRAINT FK_D4E6F81A76ED395 FOREIGN KEY (user_id) REFERENCES "user" (id) ON DELETE CASCADE NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('ALTER TABLE delivery ADD CONSTRAINT FK_3781EC10E3D8151C FOREIGN KEY (courier_id) REFERENCES "user" (id) ON DELETE SET NULL NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('ALTER TABLE delivery ADD CONSTRAINT FK_3781EC108D9F6D38 FOREIGN KEY (order_id) REFERENCES "order" (id) ON DELETE CASCADE NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('ALTER TABLE meal ADD CONSTRAINT FK_9EF68E9CB1E7706E FOREIGN KEY (restaurant_id) REFERENCES restaurant (id) ON DELETE CASCADE NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('ALTER TABLE meal_plan ADD CONSTRAINT FK_C7848889B1E7706E FOREIGN KEY (restaurant_id) REFERENCES restaurant (id) ON DELETE CASCADE NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('ALTER TABLE meal_plan ADD CONSTRAINT FK_C78488897E3C61F9 FOREIGN KEY (owner_id) REFERENCES "user" (id) ON DELETE CASCADE NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('ALTER TABLE meal_plan_meal ADD CONSTRAINT FK_354F4065912AB082 FOREIGN KEY (meal_plan_id) REFERENCES meal_plan (id) ON DELETE CASCADE NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('ALTER TABLE meal_plan_meal ADD CONSTRAINT FK_354F4065639666D6 FOREIGN KEY (meal_id) REFERENCES meal (id) ON DELETE CASCADE NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('ALTER TABLE meal_plan_diet_category ADD CONSTRAINT FK_F72E3CDE912AB082 FOREIGN KEY (meal_plan_id) REFERENCES meal_plan (id) ON DELETE CASCADE NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('ALTER TABLE meal_plan_diet_category ADD CONSTRAINT FK_F72E3CDE709AC6FC FOREIGN KEY (diet_category_id) REFERENCES diet_category (id) ON DELETE CASCADE NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('ALTER TABLE "order" ADD CONSTRAINT FK_F52993989395C3F3 FOREIGN KEY (customer_id) REFERENCES "user" (id) ON DELETE SET NULL NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('ALTER TABLE "order" ADD CONSTRAINT FK_F5299398B1E7706E FOREIGN KEY (restaurant_id) REFERENCES restaurant (id) ON DELETE SET NULL NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('ALTER TABLE order_item ADD CONSTRAINT FK_52EA1F098D9F6D38 FOREIGN KEY (order_id) REFERENCES "order" (id) ON DELETE CASCADE NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('ALTER TABLE order_item ADD CONSTRAINT FK_52EA1F09912AB082 FOREIGN KEY (meal_plan_id) REFERENCES meal_plan (id) ON DELETE SET NULL NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('ALTER TABLE restaurant ADD CONSTRAINT FK_EB95123F7E3C61F9 FOREIGN KEY (owner_id) REFERENCES "user" (id) ON DELETE SET NULL NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('ALTER TABLE restaurant_couriers ADD CONSTRAINT FK_62D72FBFB1E7706E FOREIGN KEY (restaurant_id) REFERENCES restaurant (id) ON DELETE CASCADE NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('ALTER TABLE restaurant_couriers ADD CONSTRAINT FK_62D72FBFA76ED395 FOREIGN KEY (user_id) REFERENCES "user" (id) ON DELETE CASCADE NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('ALTER TABLE restaurant_restaurant_category ADD CONSTRAINT FK_A3171BA8B1E7706E FOREIGN KEY (restaurant_id) REFERENCES restaurant (id) ON DELETE CASCADE NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('ALTER TABLE restaurant_restaurant_category ADD CONSTRAINT FK_A3171BA8433DA7F8 FOREIGN KEY (restaurant_category_id) REFERENCES restaurant_category (id) ON DELETE CASCADE NOT DEFERRABLE INITIALLY IMMEDIATE');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE SCHEMA public');
        $this->addSql('ALTER TABLE address DROP CONSTRAINT FK_D4E6F81A76ED395');
        $this->addSql('ALTER TABLE delivery DROP CONSTRAINT FK_3781EC10E3D8151C');
        $this->addSql('ALTER TABLE delivery DROP CONSTRAINT FK_3781EC108D9F6D38');
        $this->addSql('ALTER TABLE meal DROP CONSTRAINT FK_9EF68E9CB1E7706E');
        $this->addSql('ALTER TABLE meal_plan DROP CONSTRAINT FK_C7848889B1E7706E');
        $this->addSql('ALTER TABLE meal_plan DROP CONSTRAINT FK_C78488897E3C61F9');
        $this->addSql('ALTER TABLE meal_plan_meal DROP CONSTRAINT FK_354F4065912AB082');
        $this->addSql('ALTER TABLE meal_plan_meal DROP CONSTRAINT FK_354F4065639666D6');
        $this->addSql('ALTER TABLE meal_plan_diet_category DROP CONSTRAINT FK_F72E3CDE912AB082');
        $this->addSql('ALTER TABLE meal_plan_diet_category DROP CONSTRAINT FK_F72E3CDE709AC6FC');
        $this->addSql('ALTER TABLE "order" DROP CONSTRAINT FK_F52993989395C3F3');
        $this->addSql('ALTER TABLE "order" DROP CONSTRAINT FK_F5299398B1E7706E');
        $this->addSql('ALTER TABLE order_item DROP CONSTRAINT FK_52EA1F098D9F6D38');
        $this->addSql('ALTER TABLE order_item DROP CONSTRAINT FK_52EA1F09912AB082');
        $this->addSql('ALTER TABLE restaurant DROP CONSTRAINT FK_EB95123F7E3C61F9');
        $this->addSql('ALTER TABLE restaurant_couriers DROP CONSTRAINT FK_62D72FBFB1E7706E');
        $this->addSql('ALTER TABLE restaurant_couriers DROP CONSTRAINT FK_62D72FBFA76ED395');
        $this->addSql('ALTER TABLE restaurant_restaurant_category DROP CONSTRAINT FK_A3171BA8B1E7706E');
        $this->addSql('ALTER TABLE restaurant_restaurant_category DROP CONSTRAINT FK_A3171BA8433DA7F8');
        $this->addSql('DROP TABLE address');
        $this->addSql('DROP TABLE delivery');
        $this->addSql('DROP TABLE diet_category');
        $this->addSql('DROP TABLE meal');
        $this->addSql('DROP TABLE meal_plan');
        $this->addSql('DROP TABLE meal_plan_meal');
        $this->addSql('DROP TABLE meal_plan_diet_category');
        $this->addSql('DROP TABLE "order"');
        $this->addSql('DROP TABLE order_item');
        $this->addSql('DROP TABLE refresh_tokens');
        $this->addSql('DROP TABLE restaurant');
        $this->addSql('DROP TABLE restaurant_couriers');
        $this->addSql('DROP TABLE restaurant_restaurant_category');
        $this->addSql('DROP TABLE restaurant_category');
        $this->addSql('DROP TABLE "user"');
    }
}
