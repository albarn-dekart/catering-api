<?php

namespace App\DataFixtures;

use App\Entity\Address;
use App\Entity\Order;
use App\Entity\OrderItem;
use App\Entity\MealPlan;
use App\Entity\Restaurant;
use App\Entity\User;
use App\Enum\OrderStatus;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use Doctrine\Persistence\ObjectManager;
use Faker\Factory;

class OrderFixtures extends Fixture implements DependentFixtureInterface
{
    public function load(ObjectManager $manager): void
    {
        $faker = Factory::create('en_US');

        $restaurants = [
            RestaurantFixtures::RESTAURANT_1,
            RestaurantFixtures::RESTAURANT_2,
            RestaurantFixtures::RESTAURANT_3,
            RestaurantFixtures::RESTAURANT_4,
            RestaurantFixtures::RESTAURANT_5,
        ];

        $mealPlansByRestaurant = [
            RestaurantFixtures::RESTAURANT_1 => ['italian_plan_0', 'italian_plan_1', 'italian_plan_2'],
            RestaurantFixtures::RESTAURANT_2 => ['asian_plan_0', 'asian_plan_1', 'asian_plan_2'],
            RestaurantFixtures::RESTAURANT_3 => ['healthy_plan_0', 'healthy_plan_1', 'healthy_plan_2', 'healthy_plan_3'],
            RestaurantFixtures::RESTAURANT_4 => ['bbq_plan_0', 'bbq_plan_1'],
            RestaurantFixtures::RESTAURANT_5 => ['fastfood_plan_0', 'fastfood_plan_1', 'fastfood_plan_2'],
        ];

        // Create 80 orders spanning the past 90 days
        for ($i = 0; $i < 80; $i++) {
            $customerId = $faker->numberBetween(1, 25);
            $restaurantRef = $faker->randomElement($restaurants);

            /** @var User|null $customer */
            $customer = $this->getReference("user_customer_$customerId", User::class);

            /** @var Restaurant $restaurant */
            $restaurant = $this->getReference($restaurantRef, Restaurant::class);

            // Get a random address for this customer
            $addressRef = "address_customer_{$customerId}_1";
            if ($this->hasReference("address_customer_{$customerId}_2", Address::class) && $faker->boolean()) {
                $addressRef = "address_customer_{$customerId}_2";
            }
            /** @var Address $address */
            $address = $this->getReference($addressRef, Address::class);

            $order = new Order();
            $order->setCustomer($customer);
            $order->setRestaurant($restaurant);

            // Copy address data to order
            $order->setDeliveryFirstName($address->getFirstName());
            $order->setDeliveryLastName($address->getLastName());
            $order->setDeliveryPhoneNumber($address->getPhoneNumber());
            $order->setDeliveryStreet($address->getStreet());
            $order->setDeliveryApartment($address->getApartment());
            $order->setDeliveryCity($address->getCity());
            $order->setDeliveryZipCode($address->getZipCode());

            // Set status with distribution: 60% Completed, 20% Active, 10% Paid, 10% Cancelled
            $statusRand = $faker->numberBetween(1, 100);
            if ($statusRand <= 60) {
                $status = OrderStatus::Completed;
            } elseif ($statusRand <= 80) {
                $status = OrderStatus::Active;
            } elseif ($statusRand <= 90) {
                $status = OrderStatus::Paid;
            } else {
                $status = OrderStatus::Cancelled;
            }
            $order->setStatus($status);

            // Generate fake payment intent ID
            $order->setPaymentIntentId('pi_test_' . $faker->uuid());

            // Add 1-3 order items (meal plans)
            $numItems = $faker->numberBetween(1, 3);
            $totalPrice = 0;
            $availablePlans = $mealPlansByRestaurant[$restaurantRef];

            for ($j = 0; $j < $numItems; $j++) {
                $planRef = $faker->randomElement($availablePlans);
                /** @var MealPlan $mealPlan */
                $mealPlan = $this->getReference($planRef, MealPlan::class);

                $orderItem = new OrderItem();
                $orderItem->setMealPlan($mealPlan);
                $orderItem->setQuantity($faker->numberBetween(1, 3));
                $orderItem->setOrder($order);

                $totalPrice += $mealPlan->getPrice() * $orderItem->getQuantity();

                $manager->persist($orderItem);
            }

            // Set created date - distribute orders over the past 90 days
            $daysAgo = $faker->numberBetween(0, 90);
            $createdAt = (new \DateTime())->modify("-$daysAgo days");
            $order->setCreatedAt($createdAt);

            $order->setTotal($totalPrice);

            $manager->persist($order);
            $this->addReference("order_$i", $order);
        }

        $manager->flush();
    }

    public function getDependencies(): array
    {
        return [
            UserFixtures::class,
            RestaurantFixtures::class,
            MealPlanFixtures::class,
            AddressFixtures::class,
        ];
    }
}
