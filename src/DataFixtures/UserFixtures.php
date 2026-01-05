<?php

namespace App\DataFixtures;

use App\Entity\User;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

class UserFixtures extends Fixture
{
    // Admin users
    public const ADMIN_1 = 'user_admin_1';

    // Restaurant owner users
    public const RESTAURANT_OWNER_1 = 'user_restaurant_owner_1';
    public const RESTAURANT_OWNER_2 = 'user_restaurant_owner_2';
    public const RESTAURANT_OWNER_3 = 'user_restaurant_owner_3';
    public const RESTAURANT_OWNER_4 = 'user_restaurant_owner_4';
    public const RESTAURANT_OWNER_5 = 'user_restaurant_owner_5';
    public const RESTAURANT_OWNER_6 = 'user_restaurant_owner_6';

    // Courier users
    public const COURIER_1 = 'user_courier_1';
    public const COURIER_2 = 'user_courier_2';
    public const COURIER_3 = 'user_courier_3';
    public const COURIER_4 = 'user_courier_4';
    public const COURIER_5 = 'user_courier_5';
    public const COURIER_6 = 'user_courier_6';
    public const COURIER_7 = 'user_courier_7';
    public const COURIER_8 = 'user_courier_8';
    public const COURIER_9 = 'user_courier_9';
    public const COURIER_10 = 'user_courier_10';

    // Customer users
    public const CUSTOMER_1 = 'user_customer_1';

    private UserPasswordHasherInterface $passwordHasher;

    public function __construct(UserPasswordHasherInterface $passwordHasher)
    {
        $this->passwordHasher = $passwordHasher;
    }

    public function load(ObjectManager $manager): void
    {
        // Create 1 admin
        $admin = new User();
        $admin->setEmail('admin@example.com');
        $admin->setRoles(['ROLE_ADMIN']);
        $admin->setPassword($this->passwordHasher->hashPassword($admin, 'password'));
        $manager->persist($admin);
        $this->addReference(self::ADMIN_1, $admin);

        // Create 5 restaurant owners
        $restaurantOwners = [
            self::RESTAURANT_OWNER_1 => 'restaurant1@example.com',
            self::RESTAURANT_OWNER_2 => 'restaurant2@example.com',
            self::RESTAURANT_OWNER_3 => 'restaurant3@example.com',
            self::RESTAURANT_OWNER_4 => 'restaurant4@example.com',
            self::RESTAURANT_OWNER_5 => 'restaurant5@example.com',
            self::RESTAURANT_OWNER_6 => 'restaurant6@example.com',
        ];

        foreach ($restaurantOwners as $reference => $email) {
            $owner = new User();
            $owner->setEmail($email);
            $owner->setRoles(['ROLE_RESTAURANT']);
            $owner->setPassword($this->passwordHasher->hashPassword($owner, 'password'));
            $manager->persist($owner);
            $this->addReference($reference, $owner);
        }

        // Create 8 couriers (we'll assign them to restaurants later)
        $couriers = [
            self::COURIER_1 => 'courier1@example.com',
            self::COURIER_2 => 'courier2@example.com',
            self::COURIER_3 => 'courier3@example.com',
            self::COURIER_4 => 'courier4@example.com',
            self::COURIER_5 => 'courier5@example.com',
            self::COURIER_6 => 'courier6@example.com',
            self::COURIER_7 => 'courier7@example.com',
            self::COURIER_8 => 'courier8@example.com',
            self::COURIER_9 => 'courier9@example.com',
            self::COURIER_10 => 'courier10@example.com',
        ];

        foreach ($couriers as $reference => $email) {
            $courier = new User();
            $courier->setEmail($email);
            $courier->setRoles(['ROLE_COURIER']);
            $courier->setPassword($this->passwordHasher->hashPassword($courier, 'password'));
            $manager->persist($courier);
            $this->addReference($reference, $courier);
        }

        // Create 25 customers
        for ($i = 1; $i <= 25; $i++) {
            $customer = new User();
            $customer->setEmail("customer$i@example.com");
            $customer->setRoles(['ROLE_CUSTOMER']);
            $customer->setPassword($this->passwordHasher->hashPassword($customer, 'password'));
            $manager->persist($customer);
            $this->addReference("user_customer_$i", $customer);
        }

        $manager->flush();
    }
}
