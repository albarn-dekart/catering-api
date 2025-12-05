<?php

namespace App\Repository;

use App\Entity\Meal;
use App\Entity\MealPlan;
use App\Entity\Restaurant;
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

    /**
     * Get the most popular meal plans based on order count
     * Optionally filter by restaurant
     * Returns array of meal plan details including restaurant info
     */
    public function getPopularMealPlans(int $limit = 5, ?Restaurant $restaurant = null): array
    {
        $qb = $this->createQueryBuilder('mp')
            ->select('mp', 'COUNT(oi.id) as orderCount')
            ->leftJoin('mp.restaurant', 'r')
            ->addSelect('r')
            ->leftJoin('App\Entity\OrderItem', 'oi', 'WITH', 'oi.mealPlan = mp')
            ->groupBy('mp.id', 'r.id')
            ->orderBy('orderCount', 'DESC')
            ->setMaxResults($limit);

        if ($restaurant) {
            $qb->where('mp.restaurant = :restaurant')
                ->setParameter('restaurant', $restaurant);
        }

        $results = $qb->getQuery()->getResult();

        return array_map(function ($result) {
            /** @var MealPlan $mp */
            $mp = $result[0];
            $orderCount = $result['orderCount'];

            return [
                'id' => $mp->getId(),
                'name' => $mp->getName(),
                'description' => $mp->getDescription(),
                'imagePath' => $mp->getImagePath(),
                'restaurantId' => $mp->getRestaurant()->getId(),
                'restaurantName' => $mp->getRestaurant()->getName(),
                'price' => $mp->getPrice(),
                'orderCount' => $orderCount,
            ];
        }, $results);
    }
}
