<?php

namespace App\DataFixtures;

use App\Entity\Restaurant;
use App\Entity\RestaurantCategory;
use App\Entity\User;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use Doctrine\Persistence\ObjectManager;

class RestaurantFixtures extends Fixture implements DependentFixtureInterface
{
    public const RESTAURANT_1 = 'restaurant_1';
    public const RESTAURANT_2 = 'restaurant_2';
    public const RESTAURANT_3 = 'restaurant_3';
    public const RESTAURANT_4 = 'restaurant_4';
    public const RESTAURANT_5 = 'restaurant_5';

    public function load(ObjectManager $manager): void
    {
        $restaurants = [
            [
                'reference' => self::RESTAURANT_1,
                'name' => 'Bella Italia',
                'description' => 'Authentic Italian cuisine with fresh pasta, wood-fired pizzas, and traditional recipes passed down through generations.',
                'owner' => UserFixtures::RESTAURANT_OWNER_1,
                'categories' => [RestaurantCategoryFixtures::ITALIAN, RestaurantCategoryFixtures::MEDITERRANEAN],
                'drivers' => [UserFixtures::DRIVER_1, UserFixtures::DRIVER_2],
            ],
            [
                'reference' => self::RESTAURANT_2,
                'name' => 'Asian Fusion Kitchen',
                'description' => 'A modern take on classic Asian dishes, blending flavors from Thailand, Japan, and China with locally sourced ingredients.',
                'owner' => UserFixtures::RESTAURANT_OWNER_2,
                'categories' => [RestaurantCategoryFixtures::ASIAN, RestaurantCategoryFixtures::HEALTHY],
                'drivers' => [UserFixtures::DRIVER_3, UserFixtures::DRIVER_4],
            ],
            [
                'reference' => self::RESTAURANT_3,
                'name' => 'The Healthy Bowl',
                'description' => 'Fresh, nutritious meals designed for active lifestyles. Featuring organic ingredients, balanced macros, and delicious flavors.',
                'owner' => UserFixtures::RESTAURANT_OWNER_3,
                'categories' => [RestaurantCategoryFixtures::HEALTHY, RestaurantCategoryFixtures::MEDITERRANEAN],
                'drivers' => [UserFixtures::DRIVER_5],
            ],
            [
                'reference' => self::RESTAURANT_4,
                'name' => 'BBQ Legends',
                'description' => 'Slow-smoked meats, tangy sauces, and classic American BBQ favorites. Perfect for meat lovers and comfort food enthusiasts.',
                'owner' => UserFixtures::RESTAURANT_OWNER_4,
                'categories' => [RestaurantCategoryFixtures::BBQ, RestaurantCategoryFixtures::AMERICAN],
                'drivers' => [UserFixtures::DRIVER_6, UserFixtures::DRIVER_7],
            ],
            [
                'reference' => self::RESTAURANT_5,
                'name' => 'Quick Bites Express',
                'description' => 'Fast, delicious meals for busy people. Quality ingredients and quick service without compromising on taste.',
                'owner' => UserFixtures::RESTAURANT_OWNER_5,
                'categories' => [RestaurantCategoryFixtures::FAST_FOOD, RestaurantCategoryFixtures::AMERICAN],
                'drivers' => [UserFixtures::DRIVER_8],
            ],
        ];

        foreach ($restaurants as $data) {
            $restaurant = new Restaurant();
            $restaurant->setName($data['name']);
            $restaurant->setDescription($data['description']);
            
            // Set owner
            /** @var User|null $owner */
            $owner = $this->getReference($data['owner'], User::class);
            $owner->setRestaurant($restaurant);

            // Add restaurant categories
            foreach ($data['categories'] as $categoryRef) {
                /** @var RestaurantCategory $category */
                $category = $this->getReference($categoryRef, RestaurantCategory::class);
                $restaurant->addRestaurantCategory($category);
            }

            // Assign drivers to restaurant
            foreach ($data['drivers'] as $driverRef) {
                /** @var User|null $driver */
                $driver = $this->getReference($driverRef, User::class);
                $driver->setRestaurant($restaurant);
            }

            $manager->persist($restaurant);
            $this->addReference($data['reference'], $restaurant);
        }

        $manager->flush();
    }

    public function getDependencies(): array
    {
        return [
            UserFixtures::class,
            RestaurantCategoryFixtures::class,
        ];
    }
}
