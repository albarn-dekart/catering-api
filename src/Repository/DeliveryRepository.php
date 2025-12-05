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
    public function getDeliveriesByStatus(?Restaurant $restaurant = null): array
    {
        $qb = $this->createQueryBuilder('d')
            ->select('d.status as status, COUNT(d.id) as count')
            ->groupBy('d.status');

        if ($restaurant) {
            $qb->where('d.restaurant = :restaurant')
                ->setParameter('restaurant', $restaurant);
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
     * Get the delivery success rate
     * Success rate = (Delivered / Total) * 100
     * Optionally filter by restaurant
     */
    public function getDeliverySuccessRate(?Restaurant $restaurant = null): float
    {
        $qb = $this->createQueryBuilder('d')
            ->select('COUNT(d.id)');

        if ($restaurant) {
            $qb->where('d.restaurant = :restaurant')
                ->setParameter('restaurant', $restaurant);
        }

        $totalDeliveries = $qb->getQuery()->getSingleScalarResult();

        if (!$totalDeliveries || $totalDeliveries == 0) {
            return 0.0;
        }

        $deliveredQb = $this->createQueryBuilder('d')
            ->select('COUNT(d.id)')
            ->where('d.status = :deliveredStatus')
            ->setParameter('deliveredStatus', DeliveryStatus::Delivered);

        if ($restaurant) {
            $deliveredQb->andWhere('d.restaurant = :restaurant')
                ->setParameter('restaurant', $restaurant);
        }

        $deliveredCount = $deliveredQb->getQuery()->getSingleScalarResult();

        return $deliveredCount ? ($deliveredCount / $totalDeliveries) * 100 : 0.0;
    }
}
