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
        $faker = Factory::create('pl_PL');
        $ordersToUpdate = []; // Array to hold orders that need a final total calculation

        for ($i = 0; $i < 80; $i++) {
            /** @var Order $order */
            $order = $this->getReference("order_$i", Order::class);
            $restaurant = $order->getRestaurant();

            $orderDate = $order->getCreatedAt() ? DateTimeImmutable::createFromMutable($order->getCreatedAt()) : new DateTimeImmutable();

            /** @var User[] $availableCouriers */
            $availableCouriers = $restaurant->getCouriers()->toArray();
            $numDeliveries = $faker->numberBetween(5, 12);

            $daysAgo = (new \DateTime())->diff($order->getCreatedAt())->days;

            if ($order->getStatus() === OrderStatus::Completed) {
                // For Completed orders, all deliveries MUST be in the past.
                // We ensure the last delivery is at least 1 day ago.
                $maxStart = max(0, $daysAgo - $numDeliveries);
                $deliveryPeriodDaysStart = $faker->numberBetween(0, $maxStart);

                // If it's impossible to fit all deliveries in the past, downgrade to Active
                if ($deliveryPeriodDaysStart + $numDeliveries > $daysAgo) {
                    $order->setStatus(OrderStatus::Active);
                }
            }

            if ($order->getStatus() === OrderStatus::Active) {
                // For Active orders, we want deliveries to overlap with today/future
                $minStart = max(0, $daysAgo - $numDeliveries + 1);
                $maxStart = max($minStart, min(5, $daysAgo));
                $deliveryPeriodDaysStart = $faker->numberBetween($minStart, $maxStart);
            } else if ($order->getStatus() !== OrderStatus::Completed) {
                $deliveryPeriodDaysStart = $faker->numberBetween(1, 7);
            }

            for ($j = 0; $j < $numDeliveries; $j++) {
                $delivery = new Delivery();
                $deliveryDate = $orderDate->modify('+' . ($deliveryPeriodDaysStart + $j) . ' days');
                $delivery->setDeliveryDate($deliveryDate);
                // Assign a random courier
                /** @var User|null $courier */
                $courier = $faker->randomElement($availableCouriers);
                $delivery->setCourier($courier);

                // Determine status based on order status and date
                $delivery->setStatus($this->getDeliveryStatus($order->getStatus(), $deliveryDate, $faker));

                $order->addDelivery($delivery);
                $manager->persist($delivery);
            }

            $ordersToUpdate[] = $order;
        }
        $manager->flush();

        foreach ($ordersToUpdate as $order) {
            $order->calculateTotal();
        }
        $manager->flush();
    }

    private function getDeliveryStatus(OrderStatus $orderStatus, DateTimeImmutable $deliveryDate, Generator $faker): DeliveryStatus
    {
        $now = new DateTimeImmutable();
        $isFuture = $deliveryDate > $now && $deliveryDate->format('Y-m-d') !== $now->format('Y-m-d');

        // For Completed orders: all delivered (but cap at today)
        if ($orderStatus === OrderStatus::Completed) {
            return $isFuture ? DeliveryStatus::Assigned : DeliveryStatus::Delivered;
        }

        // For Cancelled orders: mostly failed or returned if generated
        if ($orderStatus === OrderStatus::Cancelled) {
            return DeliveryStatus::Failed;
        }

        // For Active orders: varied statuses based on delivery date
        if ($orderStatus === OrderStatus::Active) {
            if ($deliveryDate < $now && $deliveryDate->format('Y-m-d') !== $now->format('Y-m-d')) {
                // Past deliveries (strictly before today)
                $rand = $faker->numberBetween(1, 100);
                if ($rand <= 85) {
                    return DeliveryStatus::Delivered;
                } elseif ($rand <= 90) {
                    return DeliveryStatus::Picked_up;
                } elseif ($rand <= 95) {
                    return DeliveryStatus::Failed;
                } else {
                    return DeliveryStatus::Returned;
                }
            } elseif ($deliveryDate->format('Y-m-d') === $now->format('Y-m-d')) {
                // Today's deliveries: mix of statuses including issues
                $rand = $faker->numberBetween(1, 100);
                if ($rand <= 30) {
                    return DeliveryStatus::Delivered;
                } elseif ($rand <= 50) {
                    return DeliveryStatus::Picked_up;
                } elseif ($rand <= 70) {
                    return DeliveryStatus::Assigned;
                } elseif ($rand <= 80) {
                    return DeliveryStatus::Pending;
                } elseif ($rand <= 90) {
                    return DeliveryStatus::Failed;
                } else {
                    return DeliveryStatus::Returned;
                }
            } else {
                // Future deliveries: assigned or pending
                return $faker->boolean(70) ? DeliveryStatus::Assigned : DeliveryStatus::Pending;
            }
        }

        // For Paid, Unpaid orders: all pending
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
