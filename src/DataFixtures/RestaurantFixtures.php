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
    public const RESTAURANT_6 = 'restaurant_6';

    public function load(ObjectManager $manager): void
    {
        $restaurants = [
            [
                'reference' => self::RESTAURANT_1,
                'name' => 'Bella Italia',
                'description' => 'Autentyczna włoska kuchnia ze świeżym makaronem, pizzą z pieca opalanego drewnem i tradycyjnymi recepturami przekazywanymi z pokolenia na pokolenie.', // Translated
                'owner' => UserFixtures::RESTAURANT_OWNER_1,
                'categories' => [RestaurantCategoryFixtures::ITALIAN, RestaurantCategoryFixtures::MEDITERRANEAN],
                'drivers' => [UserFixtures::DRIVER_1, UserFixtures::DRIVER_2],
                'image' => 'bella.webp',
                'delivery_price' => 1500,
                'phone_number' => '123 456 789',
                'email' => 'bella@italia.com',
                'city' => 'Warszawa',
                'street' => 'Włoska 1',
                'zip_code' => '00-001',
                'nip' => '5213123456',
            ],
            [
                'reference' => self::RESTAURANT_2,
                'name' => 'Azjatycka Kuchnia Fusion',
                'description' => 'Mieszanka najlepszych azjatyckich tradycji kulinarnych, oferująca dania od japońskiego sushi po tajskie curry i chińskie stir-fry.', // Translated
                'owner' => UserFixtures::RESTAURANT_OWNER_2,
                'categories' => [RestaurantCategoryFixtures::ASIAN],
                'drivers' => [UserFixtures::DRIVER_3],
                'image' => 'azjatyk.webp',
                'delivery_price' => 1200,
                'phone_number' => '234 567 890',
                'email' => 'fusion@asia.com',
                'city' => 'Kraków',
                'street' => 'Azjatycka 2',
                'zip_code' => '30-002',
                'nip' => '6771234567',
            ],
            [
                'reference' => self::RESTAURANT_3,
                'name' => 'Zdrowe Miejsce',
                'description' => 'Świeże, organiczne posiłki pochodzące od lokalnych dostawców. Specjalizacja w opcjach dietetycznych, takich jak keto, wegańskie i bezglutenowe.', // Translated
                'owner' => UserFixtures::RESTAURANT_OWNER_3,
                'categories' => [RestaurantCategoryFixtures::HEALTHY, RestaurantCategoryFixtures::MEDITERRANEAN],
                'drivers' => [UserFixtures::DRIVER_4, UserFixtures::DRIVER_5],
                'image' => 'zdrowe_miejsce.webp',
                'delivery_price' => 0,
                'phone_number' => '345 678 901',
                'email' => 'info@healthy.com',
                'city' => 'Wrocław',
                'street' => 'Zielona 3',
                'zip_code' => '50-003',
                'nip' => '8981234567',
            ],
            [
                'reference' => self::RESTAURANT_4,
                'name' => 'Dym i Ogień BBQ',
                'description' => 'Tradycyjne amerykańskie BBQ, oferujące wolno wędzoną wołowinę (brisket), szarpaną wieprzowinę i żeberka odchodzące od kości.', // Translated
                'owner' => UserFixtures::RESTAURANT_OWNER_4,
                'categories' => [RestaurantCategoryFixtures::BBQ, RestaurantCategoryFixtures::AMERICAN],
                'drivers' => [UserFixtures::DRIVER_6, UserFixtures::DRIVER_7],
                'image' => 'dym_ogień.webp',
                'delivery_price' => 2000,
                'phone_number' => '456 789 012',
                'email' => 'bbq@fire.com',
                'city' => 'Poznań',
                'street' => 'Wędzona 4',
                'zip_code' => '60-004',
                'nip' => '7791234567',
            ],
            [
                'reference' => self::RESTAURANT_5,
                'name' => 'Szybki Kęs Burgery',
                'description' => 'Klasyczny amerykański fast food: soczyste burgery, chrupiące frytki i gęste koktajle mleczne. Idealne na szybki i sycący posiłek.', // Translated
                'owner' => UserFixtures::RESTAURANT_OWNER_5,
                'categories' => [RestaurantCategoryFixtures::FAST_FOOD, RestaurantCategoryFixtures::AMERICAN],
                'drivers' => [UserFixtures::DRIVER_8],
                'image' => 'szybki_kęs_burgery.webp',
                'delivery_price' => 1000,
                'phone_number' => '567 890 123',
                'email' => 'fast@burgers.com',
                'city' => 'Gdańsk',
                'street' => 'Szybka 5',
                'zip_code' => '80-005',
                'nip' => '5831234567',
            ],
            [
                'reference' => self::RESTAURANT_6,
                'name' => 'Kuchnia Polska',
                'description' => 'Tradycyjne polskie smaki, jak u babci. Pierogi, bigos i schabowy w najlepszym wydaniu.',
                'owner' => UserFixtures::RESTAURANT_OWNER_6,
                'categories' => [RestaurantCategoryFixtures::POLISH],
                'drivers' => [UserFixtures::DRIVER_9, UserFixtures::DRIVER_10],
                'image' => 'kuchnia_polska.webp',
                'delivery_price' => 1450,
                'phone_number' => '678 901 234',
                'email' => 'kontakt@polska.com',
                'city' => 'Łódź',
                'street' => 'Polska 6',
                'zip_code' => '90-006',
                'nip' => '7251234567',
            ],
        ];

        foreach ($restaurants as $data) {
            $restaurant = new Restaurant();
            $restaurant->setName($data['name']);
            $restaurant->setDescription($data['description']);
            $restaurant->setImagePath($data['image']);
            $restaurant->setDeliveryPrice($data['delivery_price']);
            $restaurant->setPhoneNumber($data['phone_number']);
            $restaurant->setEmail($data['email']);
            $restaurant->setCity($data['city']);
            $restaurant->setStreet($data['street']);
            $restaurant->setZipCode($data['zip_code']);
            $restaurant->setNip($data['nip']);

            // Set owner
            /** @var User|null $owner */
            $owner = $this->getReference($data['owner'], User::class);
            $restaurant->setOwner($owner);

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
                $restaurant->addDriver($driver);
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
