<?php

namespace App\Repository;

use App\Entity\Delivery;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
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

    public function findByFilters(?int $status = null, ?int $driver_id = null): array
    {
        $qb = $this->createQueryBuilder('o');

        if ($status !== null) {
            $qb->andWhere('o.status = :status')
                ->setParameter('status', $status);
        }

        if ($driver_id !== null) {
            $qb->andWhere('o.driver = :driver_id')
                ->setParameter('driver_id', $driver_id);
        }

        return $qb->getQuery()->getResult();
    }
}
