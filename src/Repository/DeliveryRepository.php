<?php

namespace App\Repository;

use App\Entity\Delivery;
use App\Entity\User;
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
}
