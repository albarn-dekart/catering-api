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

    // Driver users
    public const DRIVER_1 = 'user_driver_1';
    public const DRIVER_2 = 'user_driver_2';
    public const DRIVER_3 = 'user_driver_3';
    public const DRIVER_4 = 'user_driver_4';
    public const DRIVER_5 = 'user_driver_5';
    public const DRIVER_6 = 'user_driver_6';
    public const DRIVER_7 = 'user_driver_7';
    public const DRIVER_8 = 'user_driver_8';

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
        ];

        foreach ($restaurantOwners as $reference => $email) {
            $owner = new User();
            $owner->setEmail($email);
            $owner->setRoles(['ROLE_RESTAURANT']);
            $owner->setPassword($this->passwordHasher->hashPassword($owner, 'password'));
            $manager->persist($owner);
            $this->addReference($reference, $owner);
        }

        // Create 8 drivers (we'll assign them to restaurants later)
        $drivers = [
            self::DRIVER_1 => 'driver1@example.com',
            self::DRIVER_2 => 'driver2@example.com',
            self::DRIVER_3 => 'driver3@example.com',
            self::DRIVER_4 => 'driver4@example.com',
            self::DRIVER_5 => 'driver5@example.com',
            self::DRIVER_6 => 'driver6@example.com',
            self::DRIVER_7 => 'driver7@example.com',
            self::DRIVER_8 => 'driver8@example.com',
        ];

        foreach ($drivers as $reference => $email) {
            $driver = new User();
            $driver->setEmail($email);
            $driver->setRoles(['ROLE_DRIVER']);
            $driver->setPassword($this->passwordHasher->hashPassword($driver, 'password'));
            $manager->persist($driver);
            $this->addReference($reference, $driver);
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
