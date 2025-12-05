<?php

namespace App\DataFixtures;

use App\Entity\Delivery;
use App\Entity\Order;
use App\Entity\User;
use App\Enum\DeliveryStatus;
use App\Enum\OrderStatus;
use DateTimeImmutable;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use Doctrine\Persistence\ObjectManager;
use Faker\Factory;
use Faker\Generator;

class DeliveryFixtures extends Fixture implements DependentFixtureInterface
{
    public function load(ObjectManager $manager): void
    {
        $faker = Factory::create('en_US');

        // Process each order and create deliveries
        for ($i = 0; $i < 80; $i++) {
            /** @var Order $order */
            $order = $this->getReference("order_$i", Order::class);
            $restaurant = $order->getRestaurant();

            // Determine order creation date (within last 90 days)
            $daysAgo = $faker->numberBetween(0, 90);
            $orderDate = new DateTimeImmutable("-$daysAgo days");

            // Create 5-12 deliveries per order
            $numDeliveries = $faker->numberBetween(5, 12);

            // Determine delivery period (7-21 days from order date)
            $deliveryPeriodDays = $faker->numberBetween(7, 21);

            // Get available drivers for this restaurant
            $drivers = iterator_to_array($restaurant->getDrivers());

            for ($d = 0; $d < $numDeliveries; $d++) {
                $delivery = new Delivery();
                $delivery->setOrder($order);
                $delivery->setRestaurant($restaurant);

                // Set delivery date (distributed across the delivery period)
                $dayOffset = (int) floor(($d / $numDeliveries) * $deliveryPeriodDays);
                $deliveryDate = $orderDate->modify("+$dayOffset days");
                $delivery->setDeliveryDate($deliveryDate);

                // Set status based on order status and delivery date
                $status = $this->determineDeliveryStatus(
                    $order->getStatus(),
                    $deliveryDate,
                    $faker
                );
                $delivery->setStatus($status);

                // Assign driver for non-pending deliveries
                if ($status !== DeliveryStatus::Pending && count($drivers) > 0) {
                    /** @var User|null $driver */
                    $driver = $faker->randomElement($drivers);
                    $delivery->setDriver($driver);
                }

                $manager->persist($delivery);
            }
        }

        $manager->flush();
    }

    private function determineDeliveryStatus(
        OrderStatus $orderStatus,
        DateTimeImmutable $deliveryDate,
        Generator $faker
    ): DeliveryStatus {
        $now = new DateTimeImmutable();

        // For Completed orders: all deliveries are delivered
        if ($orderStatus === OrderStatus::Completed) {
            return DeliveryStatus::Delivered;
        }

        // For Active orders: varied statuses based on delivery date
        if ($orderStatus === OrderStatus::Active) {
            if ($deliveryDate < $now) {
                // Past deliveries: mostly delivered, some picked up
                return $faker->boolean(90) ? DeliveryStatus::Delivered : DeliveryStatus::Picked_up;
            } elseif ($deliveryDate->format('Y-m-d') === $now->format('Y-m-d')) {
                // Today's deliveries: mix of statuses
                $rand = $faker->numberBetween(1, 100);
                if ($rand <= 40) {
                    return DeliveryStatus::Delivered;
                } elseif ($rand <= 70) {
                    return DeliveryStatus::Picked_up;
                } elseif ($rand <= 90) {
                    return DeliveryStatus::Assigned;
                } else {
                    return DeliveryStatus::Pending;
                }
            } else {
                // Future deliveries: assigned or pending
                return $faker->boolean(70) ? DeliveryStatus::Assigned : DeliveryStatus::Pending;
            }
        }

        // For Paid, Unpaid, or Cancelled orders: all pending
        return DeliveryStatus::Pending;
    }

    public function getDependencies(): array
    {
        return [
            OrderFixtures::class,
            RestaurantFixtures::class,
            UserFixtures::class,
        ];
    }
}
