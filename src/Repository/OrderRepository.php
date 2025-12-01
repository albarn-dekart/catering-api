<?php

namespace App\Repository;

use App\Entity\Meal;
use App\Entity\Order;
use App\Entity\User;
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

    public function createCustomerOrdersQueryBuilder(User $user): \Doctrine\ORM\QueryBuilder
    {
        return $this->createQueryBuilder('o')
            ->andWhere('o.customer = :user')
            ->setParameter('user', $user)
            ->orderBy('o.id', 'DESC');
    }
}
