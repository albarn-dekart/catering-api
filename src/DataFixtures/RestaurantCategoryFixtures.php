<?php

namespace App\DataFixtures;

use App\Entity\RestaurantCategory;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;

class RestaurantCategoryFixtures extends Fixture
{
    public const ITALIAN = 'restaurant_category_italian';
    public const ASIAN = 'restaurant_category_asian';
    public const AMERICAN = 'restaurant_category_american';
    public const MEDITERRANEAN = 'restaurant_category_mediterranean';
    public const FAST_FOOD = 'restaurant_category_fast_food';
    public const HEALTHY = 'restaurant_category_healthy';
    public const BBQ = 'restaurant_category_bbq';
    public const POLISH = 'restaurant_category_polish';

    public function load(ObjectManager $manager): void
    {
        $categories = [
            self::ITALIAN => 'Włoska', // Translated from Italian
            self::ASIAN => 'Azjatycka', // Translated from Asian
            self::AMERICAN => 'Amerykańska', // Translated from American
            self::MEDITERRANEAN => 'Śródziemnomorska', // Translated from Mediterranean
            self::FAST_FOOD => 'Fast Food', // Kept "Fast Food" (common in Polish)
            self::HEALTHY => 'Zdrowa', // Translated from Healthy
            self::BBQ => 'BBQ & Grill', // Kept "BBQ & Grill" (common terms)
            self::POLISH => 'Polska',
        ];

        foreach ($categories as $reference => $name) {
            $category = new RestaurantCategory();
            $category->setName($name);

            $manager->persist($category);
            $this->addReference($reference, $category);
        }

        $manager->flush();
    }
}
