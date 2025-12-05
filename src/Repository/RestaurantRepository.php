<?php

namespace App\Repository;

use App\Entity\Restaurant;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Restaurant>
 */
class RestaurantRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Restaurant::class);
    }

    /**
     * Get featured restaurants based on total order count
     * Returns array of restaurant details with order count
     */
    public function getFeaturedRestaurants(int $limit = 6): array
    {
        $qb = $this->createQueryBuilder('r')
            ->select('r.id, r.name, r.description, r.imagePath, COUNT(DISTINCT o.id) as orderCount')
            ->leftJoin('r.mealPlans', 'mp')
            ->leftJoin('App\Entity\OrderItem', 'oi', 'WITH', 'oi.mealPlan = mp')
            ->leftJoin('oi.order', 'o')
            ->groupBy('r.id, r.name, r.description, r.imagePath')
            ->orderBy('orderCount', 'DESC')
            ->setMaxResults($limit);

        return $qb->getQuery()->getResult();
    }
}
