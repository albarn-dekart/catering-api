<?php

namespace App\Repository;

use App\Entity\Meal;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Meal>
 */
class MealRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Meal::class);
    }

    /**
     * @return Meal[] Returns an array of Meal objects
     */
    public function findByFilters(?int $restaurantId = null, ?string $category = null): array
    {
        $qb = $this->createQueryBuilder('m');

        if ($restaurantId !== null) {
            $qb->andWhere('m.restaurant = :restaurantId')
                ->setParameter('restaurantId', $restaurantId);
        }

        if ($category !== null) {
            $qb->join('m.categories', 'c')
                ->andWhere('c.name = :category')
                ->setParameter('category', $category);
        }

        return $qb->getQuery()->getResult();
    }
}
