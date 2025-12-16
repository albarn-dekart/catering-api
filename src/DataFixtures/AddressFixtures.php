<?php

namespace App\DataFixtures;

use App\Entity\Address;
use App\Entity\User;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use Doctrine\Persistence\ObjectManager;
use Faker\Factory;

class AddressFixtures extends Fixture implements DependentFixtureInterface
{
    public function load(ObjectManager $manager): void
    {
        $faker = Factory::create('pl_PL'); // Changed to pl_PL

        // Create 1-2 addresses for each customer
        for ($i = 1; $i <= 25; $i++) {
            /** @var User|null $customer */
            $customer = $this->getReference("user_customer_$i", User::class);

            // First address (default)
            $address1 = new Address();
            $address1->setUser($customer);
            $address1->setFirstName($faker->firstName());
            $address1->setLastName($faker->lastName());
            $address1->setPhoneNumber($faker->phoneNumber());
            $address1->setStreet($faker->streetAddress());
            $address1->setApartment($faker->optional(0.4)->buildingNumber());
            $address1->setCity($faker->city());
            $address1->setZipCode($faker->postcode());
            $address1->setIsDefault(true);
            $manager->persist($address1);
            $this->addReference("address_customer_{$i}_1", $address1);

            // Second address (optional, 60% chance)
            if ($faker->boolean(60)) {
                $address2 = new Address();
                $address2->setUser($customer);
                $address2->setFirstName($faker->firstName());
                $address2->setLastName($faker->lastName());
                $address2->setPhoneNumber($faker->phoneNumber());
                $address2->setStreet($faker->streetAddress());
                $address2->setApartment($faker->optional(0.3)->buildingNumber());
                $address2->setCity($faker->city());
                $address2->setZipCode($faker->postcode());
                $address2->setIsDefault(false);
                $manager->persist($address2);
                $this->addReference("address_customer_{$i}_2", $address2);
            }
        }

        $manager->flush();
    }

    public function getDependencies(): array
    {
        return [
            UserFixtures::class,
        ];
    }
}