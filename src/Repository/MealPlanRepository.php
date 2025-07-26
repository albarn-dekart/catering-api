<?php

namespace App\Repository;

use App\Entity\Meal;
use App\Entity\MealPlan;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<MealPlan>
 */
class MealPlanRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, MealPlan::class);
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

    public function findByFilters(?int $restaurantId = null): array
    {
        $qb = $this->createQueryBuilder('mp');

        if ($restaurantId !== null) {
            $qb->andWhere('mp.restaurant = :restaurantId')
                ->setParameter('restaurantId', $restaurantId);
        }

        return $qb->getQuery()->getResult();
    }
}
