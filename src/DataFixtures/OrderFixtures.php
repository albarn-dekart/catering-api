<?php

namespace App\DataFixtures;

use App\Entity\Address;
use App\Entity\Order;
use App\Entity\OrderItem;
use App\Entity\MealPlan;
use App\Entity\Restaurant;
use App\Entity\User;
use App\Enum\OrderStatus;
use DateTime;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use Doctrine\Persistence\ObjectManager;
use Faker\Factory;
use Faker\Generator; // Import for the corrected type hint

class OrderFixtures extends Fixture implements DependentFixtureInterface
{
    public function load(ObjectManager $manager): void
    {
        $faker = Factory::create('pl_PL'); // Changed to pl_PL

        $restaurants = [
            RestaurantFixtures::RESTAURANT_1,
            RestaurantFixtures::RESTAURANT_2,
            RestaurantFixtures::RESTAURANT_3,
            RestaurantFixtures::RESTAURANT_4,
            RestaurantFixtures::RESTAURANT_5,
            RestaurantFixtures::RESTAURANT_6,
        ];

        $mealPlansByRestaurant = [
            RestaurantFixtures::RESTAURANT_1 => ['italian_plan_0', 'italian_plan_1', 'italian_plan_2'],
            RestaurantFixtures::RESTAURANT_2 => ['asian_plan_0', 'asian_plan_1', 'asian_plan_2'],
            RestaurantFixtures::RESTAURANT_3 => ['healthy_plan_0', 'healthy_plan_1', 'healthy_plan_2', 'healthy_plan_3'],
            RestaurantFixtures::RESTAURANT_4 => ['bbq_plan_0', 'bbq_plan_1'],
            RestaurantFixtures::RESTAURANT_5 => ['fastfood_plan_0', 'fastfood_plan_1', 'fastfood_plan_2'],
            RestaurantFixtures::RESTAURANT_6 => ['polish_plan_0', 'polish_plan_1', 'polish_plan_2'],
        ];

        // Create 80 random orders
        for ($i = 0; $i < 80; $i++) {
            $order = new Order();

            // FIX: Use the fixture index (1-25) to find the customer and address
            $customerIndex = $faker->numberBetween(1, 25);

            // Fetch customer using the index
            /** @var User|null $customer */
            $customer = $this->getReference('user_customer_' . $customerIndex, User::class);
            $order->setCustomer($customer);

            // Fetch the customer's address to copy details from
            /** @var Address|null $address */
            // FIX: Use the customer index to fetch the address reference
            $address = $this->getReference('address_customer_' . $customerIndex . '_1', Address::class);

            // Copy Address fields to Order entity's delivery fields
            if ($address) {
                $order->setDeliveryFirstName($address->getFirstName());
                $order->setDeliveryLastName($address->getLastName());
                $order->setDeliveryPhoneNumber($address->getPhoneNumber());
                $order->setDeliveryStreet($address->getStreet());
                $order->setDeliveryApartment($address->getApartment());
                $order->setDeliveryCity($address->getCity());
                $order->setDeliveryZipCode($address->getZipCode());
            } else {
                // Fallback using faker if address fails
                $order->setDeliveryFirstName($faker->firstName());
                $order->setDeliveryLastName($faker->lastName());
                $order->setDeliveryPhoneNumber($faker->phoneNumber());
                $order->setDeliveryStreet($faker->streetAddress());
                $order->setDeliveryApartment($faker->optional(0.4)->buildingNumber());
                $order->setDeliveryCity($faker->city());
                $order->setDeliveryZipCode($faker->postcode());
            }

            // Select a random restaurant for the order
            $restaurantRef = $faker->randomElement($restaurants);
            /** @var Restaurant|null $restaurant */
            $restaurant = $this->getReference($restaurantRef, Restaurant::class);
            $order->setRestaurant($restaurant);

            // Assign a random status (Weighted distribution for realism)
            $status = $this->getWeightedStatus($faker);
            $order->setStatus($status);

            // Add 1-3 order items (meal plans)
            $numItems = $faker->numberBetween(1, 3);
            $availablePlans = $mealPlansByRestaurant[$restaurantRef];

            for ($j = 0; $j < $numItems; $j++) {
                $planRef = $faker->randomElement($availablePlans);
                /** @var MealPlan $mealPlan */
                $mealPlan = $this->getReference($planRef, MealPlan::class);

                $orderItem = new OrderItem();
                $orderItem->setMealPlan($mealPlan);
                $orderItem->setQuantity($faker->numberBetween(1, 3));
                $order->addOrderItem($orderItem);

                $manager->persist($orderItem);
            }

            // Set created date - distribute orders over the past 90 days
            $daysAgo = $faker->numberBetween(0, 90);
            $createdAt = (new DateTime())->modify("-$daysAgo days");
            $order->setCreatedAt($createdAt);

            $manager->persist($order);
            $this->addReference("order_$i", $order);
        }

        $manager->flush();
    }

    /**
     * FIX: Changed type hint from Faker\Factory to Faker\Generator
     */
    private function getWeightedStatus(Generator $faker): OrderStatus
    {
        $rand = $faker->numberBetween(1, 100);

        if ($rand <= 50) {
            return OrderStatus::Completed; // 50% completed
        } elseif ($rand <= 80) {
            return OrderStatus::Active; // 30% active
        } elseif ($rand <= 90) {
            return OrderStatus::Paid; // 10% paid, awaiting processing
        } elseif ($rand <= 95) {
            return OrderStatus::Unpaid; // 5% unpaid
        } else {
            return OrderStatus::Cancelled; // 5% cancelled
        }
    }

    public function getDependencies(): array
    {
        return [
            UserFixtures::class,
            AddressFixtures::class,
            RestaurantFixtures::class,
            MealPlanFixtures::class,
        ];
    }
}
