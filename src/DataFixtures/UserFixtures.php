<?php

namespace App\DataFixtures;

use App\Entity\User;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

class UserFixtures extends Fixture
{
    public const RESTAURANT_OWNER_1 = 'user_restaurant_owner_1';
    public const RESTAURANT_OWNER_2 = 'user_restaurant_owner_2';
    public const RESTAURANT_OWNER_3 = 'user_restaurant_owner_3';
    public const RESTAURANT_OWNER_4 = 'user_restaurant_owner_4';
    public const RESTAURANT_OWNER_5 = 'user_restaurant_owner_5';
    public const RESTAURANT_OWNER_6 = 'user_restaurant_owner_6';

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

    private UserPasswordHasherInterface $passwordHasher;

    public function __construct(UserPasswordHasherInterface $passwordHasher)
    {
        $this->passwordHasher = $passwordHasher;
    }

    public function load(ObjectManager $manager): void
    {
        // 1. Define User Configurations
        $userGroups = [
            ['role' => 'ROLE_ADMIN', 'prefix' => 'admin', 'count' => 1, 'ref' => 'user_admin_'],
            ['role' => 'ROLE_RESTAURANT', 'prefix' => 'restaurant', 'count' => 6, 'ref' => 'user_restaurant_owner_'],
            ['role' => 'ROLE_COURIER', 'prefix' => 'courier', 'count' => 10, 'ref' => 'user_courier_'],
            ['role' => 'ROLE_CUSTOMER', 'prefix' => 'customer', 'count' => 25, 'ref' => 'user_customer_'],
        ];

        // 2. Loop through groups and persist
        foreach ($userGroups as $group) {
            for ($i = 1; $i <= $group['count']; $i++) {
                $user = new User();

                // Handle single admin vs numbered users
                $email = ($group['count'] === 1)
                    ? "{$group['prefix']}@example.com"
                    : "{$group['prefix']}{$i}@example.com";

                $user->setEmail($email);
                $user->setRoles([$group['role']]);
                $user->setPassword($this->passwordHasher->hashPassword($user, 'password'));

                $manager->persist($user);

                // Add reference for use in other fixtures
                $this->addReference($group['ref'] . $i, $user);
            }
        }

        $manager->flush();
    }
}