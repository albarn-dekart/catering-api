<?php

namespace App\Repository;

use App\Entity\Delivery;
use App\Entity\Restaurant;
use App\Entity\User;
use App\Enum\DeliveryStatus;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\ORM\QueryBuilder;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Delivery>
 */
class DeliveryRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Delivery::class);
    }

    public function createDriverDeliveriesQueryBuilder(User $driver): QueryBuilder
    {
        return $this->createQueryBuilder('d')
            ->andWhere('d.driver = :driver')
            ->setParameter('driver', $driver)
            ->orderBy('d.deliveryDate', 'DESC');
    }

    /**
     * Get the count of deliveries grouped by status
     * Optionally filter by restaurant
     * Returns array like ['Pending' => 5, 'Delivered' => 20, ...]
     */
    public function getDeliveriesByStatus(?Restaurant $restaurant = null, ?\DateTimeInterface $startDate = null, ?\DateTimeInterface $endDate = null): array
    {
        $qb = $this->createQueryBuilder('d')
            ->select('d.status as status, COUNT(d.id) as count')
            ->groupBy('d.status');

        if ($restaurant) {
            $qb->where('d.restaurant = :restaurant')
                ->setParameter('restaurant', $restaurant);
        }

        if ($startDate && $endDate) {
            $qb->andWhere('d.deliveryDate >= :startDate')
                ->andWhere('d.deliveryDate <= :endDate')
                ->setParameter('startDate', $startDate)
                ->setParameter('endDate', $endDate);
        }

        $results = $qb->getQuery()->getResult();

        $counts = [];
        foreach ($results as $result) {
            $status = $result['status'];
            if ($status instanceof DeliveryStatus) {
                $counts[$status->value] = (int) $result['count'];
            }
        }

        return $counts;
    }

    /**
     * Get the production plan for a specific date (or range)
     * Returns an array like: [['mealName' => 'Keto', 'count' => 50], ...]
     */
    public function getProductionPlan(Restaurant $restaurant, \DateTimeInterface $date): array
    {
        // Set time range for the entire day (00:00:00 to 23:59:59)
        $startDate = (clone $date)->setTime(0, 0, 0);
        $endDate = (clone $date)->setTime(23, 59, 59);

        // Query to sum up quantities of meal plans for deliveries scheduled on this date
        // We join Delivery -> Order -> OrderItems -> MealPlan
        return $this->createQueryBuilder('d')
            ->select('mp.name as mealName, SUM(oi.quantity) as count')
            ->join('d.order', 'o')
            ->join('o.orderItems', 'oi')
            ->join('oi.mealPlan', 'mp')
            ->where('d.restaurant = :restaurant')
            ->andWhere('d.deliveryDate >= :startDate')
            ->andWhere('d.deliveryDate <= :endDate')
            // Exclude cancelled orders
            ->andWhere('o.status != :cancelledStatus')
            ->setParameter('restaurant', $restaurant)
            ->setParameter('startDate', $startDate)
            ->setParameter('endDate', $endDate)
            ->setParameter('cancelledStatus', \App\Enum\OrderStatus::Cancelled)
            ->groupBy('mp.id')
            ->orderBy('mp.name', 'ASC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Get the delivery success rate
     * Success rate = (Delivered / Total) * 100
     * Optionally filter by restaurant
     */
    public function getDeliverySuccessRate(?Restaurant $restaurant = null, ?\DateTimeInterface $startDate = null, ?\DateTimeInterface $endDate = null): float
    {
        // Calculate success rate based only on TERMINAL statuses (Delivered, Failed, Returned)
        // We exclude Pending, Assigned, Picked_up as they are not yet resolved.

        $qb = $this->createQueryBuilder('d')
            ->select('COUNT(d.id)')
            ->where('d.status IN (:terminalStatuses)')
            ->setParameter('terminalStatuses', [
                DeliveryStatus::Delivered,
                DeliveryStatus::Failed,
                DeliveryStatus::Returned
            ]);

        if ($startDate && $endDate) {
            $qb->andWhere('d.deliveryDate >= :startDate')
                ->andWhere('d.deliveryDate <= :endDate')
                ->setParameter('startDate', $startDate)
                ->setParameter('endDate', $endDate);
        } else {
            $today = new \DateTimeImmutable('today');
            $qb->andWhere('d.deliveryDate <= :today')
                ->setParameter('today', $today);
        }

        if ($restaurant) {
            $qb->andWhere('d.restaurant = :restaurant')
                ->setParameter('restaurant', $restaurant);
        }

        $totalCompletedDeliveries = $qb->getQuery()->getSingleScalarResult();

        if (!$totalCompletedDeliveries || $totalCompletedDeliveries == 0) {
            return 0.0;
        }

        $deliveredQb = $this->createQueryBuilder('d')
            ->select('COUNT(d.id)')
            ->where('d.status = :deliveredStatus')
            ->setParameter('deliveredStatus', DeliveryStatus::Delivered);

        if ($startDate && $endDate) {
            $deliveredQb->andWhere('d.deliveryDate >= :startDate')
                ->andWhere('d.deliveryDate <= :endDate')
                ->setParameter('startDate', $startDate)
                ->setParameter('endDate', $endDate);
        } else {
            $today = new \DateTimeImmutable('today');
            $deliveredQb->andWhere('d.deliveryDate <= :today')
                ->setParameter('today', $today);
        }

        if ($restaurant) {
            $deliveredQb->andWhere('d.restaurant = :restaurant')
                ->setParameter('restaurant', $restaurant);
        }

        $deliveredCount = $deliveredQb->getQuery()->getSingleScalarResult();

        return $deliveredCount ? ($deliveredCount / $totalCompletedDeliveries) * 100 : 0.0;
    }
}
