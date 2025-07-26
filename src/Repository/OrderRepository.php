<?php

namespace App\Repository;

use App\Entity\Meal;
use App\Entity\Order;
use App\Enum\OrderStatus;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Order>
 */
class OrderRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Order::class);
    }

    public function findByMeal(Meal $meal): array
    {
        return $this->createQueryBuilder('o')
            ->innerJoin('o.meals', 'm')
            ->where('m = :meal')
            ->setParameter('meal', $meal)
            ->getQuery()
            ->getResult();
    }

    public function findByFilters(?int $userId = null, ?int $restaurantId = null, ?string $status = null): array
    {
        $qb = $this->createQueryBuilder('o');

        if ($userId !== null) {
            $qb->andWhere('o.customer = :userId')
                ->setParameter('userId', $userId);
        }

        if ($restaurantId !== null) {
            $qb->andWhere('o.restaurant = :restaurantId')
                ->setParameter('restaurantId', $restaurantId);
        }

        if ($status !== null) {
            $qb->andWhere('o.status = :status')
                ->setParameter('status', OrderStatus::from($status));
        }

        return $qb->getQuery()->getResult();
    }
}
