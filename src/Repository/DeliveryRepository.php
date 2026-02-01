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
            $qb->join('d.order', 'o')
                ->andWhere('o.restaurant = :restaurant')
                ->setParameter('restaurant', $restaurant);
        }

        if ($startDate) {
            $qb->andWhere('d.deliveryDate >= :startDate')
                ->setParameter('startDate', $startDate);
        }
        if ($endDate) {
            $qb->andWhere('d.deliveryDate <= :endDate')
                ->setParameter('endDate', $endDate);
        }

        $results = $qb->getQuery()->getResult();

        $counts = [];
        foreach ($results as $result) {
            $status = $result['status'];
            if ($status instanceof DeliveryStatus) {
                $counts[$status->value] = (int)$result['count'];
            }
        }

        return $counts;
    }

    public function getProductionPlan(Restaurant $restaurant, \DateTimeInterface $startDate, \DateTimeInterface $endDate): array
    {
        // Set time range for the period
        $start = \DateTime::createFromInterface($startDate)->setTime(0, 0, 0);
        $end = \DateTime::createFromInterface($endDate)->setTime(23, 59, 59);

        // Query to sum up quantities of MEALS derived from meal plans for deliveries scheduled on this date
        // We join Delivery -> Order -> OrderItems -> MealPlan -> Meals
        return $this->createQueryBuilder('d')
            ->select('m.name as mealName, SUM(oi.quantity) as count')
            ->join('d.order', 'o')
            ->join('o.orderItems', 'oi')
            ->join('oi.mealPlan', 'mp')
            ->join('mp.meals', 'm')
            ->where('o.restaurant = :restaurant')
            ->andWhere('d.deliveryDate >= :startDate')
            ->andWhere('d.deliveryDate <= :endDate')
            // Exclude cancelled orders
            ->andWhere('o.status != :cancelledStatus')
            ->setParameter('restaurant', $restaurant)
            ->setParameter('startDate', $start)
            ->setParameter('endDate', $end)
            ->setParameter('cancelledStatus', \App\Enum\OrderStatus::Cancelled)
            ->groupBy('m.id')
            ->orderBy('m.name', 'ASC')
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
                DeliveryStatus::Failed
            ]);

        if ($startDate && $endDate) {
            $qb->andWhere('d.deliveryDate >= :startDate')
                ->andWhere('d.deliveryDate <= :endDate')
                ->setParameter('startDate', $startDate)
                ->setParameter('endDate', $endDate);
        } else {
            $now = new \DateTimeImmutable('now');
            $qb->andWhere('d.deliveryDate <= :now')
                ->setParameter('now', $now);
        }

        if ($restaurant) {
            $qb->join('d.order', 'ord')
                ->andWhere('ord.restaurant = :restaurant')
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

        if ($startDate) {
            $deliveredQb->andWhere('d.deliveryDate >= :startDate')
                ->setParameter('startDate', $startDate);
        }
        if ($endDate) {
            $deliveredQb->andWhere('d.deliveryDate <= :endDate')
                ->setParameter('endDate', $endDate);
        }
        // If neither startDate nor endDate is provided, filter by deliveries up to now
        if (!$startDate && !$endDate) {
            $now = new \DateTimeImmutable('now');
            $deliveredQb->andWhere('d.deliveryDate <= :now')
                ->setParameter('now', $now);
        }

        if ($restaurant) {
            $deliveredQb->join('d.order', 'ord')
                ->andWhere('ord.restaurant = :restaurant')
                ->setParameter('restaurant', $restaurant);
        }

        $deliveredCount = $deliveredQb->getQuery()->getSingleScalarResult();

        return $deliveredCount ? ($deliveredCount / $totalCompletedDeliveries) * 100 : 0.0;
    }

    public function getCourierDeliveryStats(User $courier, ?\DateTimeInterface $startDate = null, ?\DateTimeInterface $endDate = null, ?DeliveryStatus $statusFilter = null): array
    {
        $qb = $this->createQueryBuilder('d')
            ->select([
                'COUNT(d.id) as total',
                'SUM(CASE WHEN d.status = :delivered THEN 1 ELSE 0 END) as completed',
                'SUM(CASE WHEN d.status IN (:inProgressStatuses) THEN 1 ELSE 0 END) as inProgress',
                'SUM(CASE WHEN d.status = :failed THEN 1 ELSE 0 END) as failed',
            ])
            ->where('d.courier = :courier')
            ->setParameter('courier', $courier)
            ->setParameter('delivered', DeliveryStatus::Delivered)
            ->setParameter('inProgressStatuses', [DeliveryStatus::Assigned, DeliveryStatus::Picked_up])
            ->setParameter('failed', DeliveryStatus::Failed);

        // Add date filters if provided
        if ($startDate) {
            $qb->andWhere('d.deliveryDate >= :startDate')
                ->setParameter('startDate', $startDate);
        }
        if ($endDate) {
            $qb->andWhere('d.deliveryDate <= :endDate')
                ->setParameter('endDate', $endDate);
        }

        // Optional status filter
        if ($statusFilter) {
            $qb->andWhere('d.status = :statusFilter')
                ->setParameter('statusFilter', $statusFilter);
        }

        $result = $qb->getQuery()->getSingleResult();

        return [
            'total' => (int)($result['total'] ?? 0),
            'completed' => (int)($result['completed'] ?? 0),
            'inProgress' => (int)($result['inProgress'] ?? 0),
            'failed' => (int)($result['failed'] ?? 0),
        ];
    }

    public function getRestaurantDeliveryStats(Restaurant $restaurant, ?\DateTimeInterface $startDate = null, ?\DateTimeInterface $endDate = null, ?DeliveryStatus $statusFilter = null): array
    {
        $qb = $this->createQueryBuilder('d')
            ->select([
                'COUNT(d.id) as total',
                'SUM(CASE WHEN d.status = :delivered THEN 1 ELSE 0 END) as completed',
                'SUM(CASE WHEN d.status IN (:inProgressStatuses) THEN 1 ELSE 0 END) as inProgress',
                'SUM(CASE WHEN d.status = :failed THEN 1 ELSE 0 END) as failed',
            ])
            ->join('d.order', 'o')
            ->where('o.restaurant = :restaurant')
            ->setParameter('restaurant', $restaurant)
            ->setParameter('delivered', DeliveryStatus::Delivered)
            ->setParameter('inProgressStatuses', [DeliveryStatus::Assigned, DeliveryStatus::Picked_up])
            ->setParameter('failed', DeliveryStatus::Failed);

        // Add date filters if provided
        if ($startDate) {
            $qb->andWhere('d.deliveryDate >= :startDate')
                ->setParameter('startDate', $startDate);
        }
        if ($endDate) {
            $qb->andWhere('d.deliveryDate <= :endDate')
                ->setParameter('endDate', $endDate);
        }

        // Optional status filter
        if ($statusFilter) {
            $qb->andWhere('d.status = :statusFilter')
                ->setParameter('statusFilter', $statusFilter);
        }

        $result = $qb->getQuery()->getSingleResult();

        return [
            'total' => (int)($result['total'] ?? 0),
            'completed' => (int)($result['completed'] ?? 0),
            'inProgress' => (int)($result['inProgress'] ?? 0),
            'failed' => (int)($result['failed'] ?? 0),
        ];
    }
}
