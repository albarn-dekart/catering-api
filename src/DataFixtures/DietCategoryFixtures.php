<?php

namespace App\DataFixtures;

use App\Entity\DietCategory;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;

class DietCategoryFixtures extends Fixture
{
    public const VEGETARIAN = 'diet_category_vegetarian';
    public const VEGAN = 'diet_category_vegan';
    public const GLUTEN_FREE = 'diet_category_gluten_free';
    public const KETO = 'diet_category_keto';
    public const PALEO = 'diet_category_paleo';
    public const LOW_CARB = 'diet_category_low_carb';
    public const DAIRY_FREE = 'diet_category_dairy_free';
    public const HIGH_PROTEIN = 'diet_category_high_protein';

    public function load(ObjectManager $manager): void
    {
        $categories = [
            self::VEGETARIAN => 'Vegetarian',
            self::VEGAN => 'Vegan',
            self::GLUTEN_FREE => 'Gluten-Free',
            self::KETO => 'Keto',
            self::PALEO => 'Paleo',
            self::LOW_CARB => 'Low-Carb',
            self::DAIRY_FREE => 'Dairy-Free',
            self::HIGH_PROTEIN => 'High-Protein',
        ];

        foreach ($categories as $reference => $name) {
            $category = new DietCategory();
            $category->setName($name);

            $manager->persist($category);
            $this->addReference($reference, $category);
        }

        $manager->flush();
    }
}
