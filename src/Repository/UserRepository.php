<?php

namespace App\Repository;

use App\Entity\User;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;
use Symfony\Component\Security\Core\Exception\UnsupportedUserException;
use Symfony\Component\Security\Core\User\PasswordAuthenticatedUserInterface;
use Symfony\Component\Security\Core\User\PasswordUpgraderInterface;

/**
 * @extends ServiceEntityRepository<User>
 */
class UserRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, User::class);
    }

    /**
     * @return User[] Returns an array of User objects with ROLE_COURIER for a specific restaurant
     */
    public function findCouriersByRestaurant(int $restaurantId): array
    {
        $restaurant = $this->getEntityManager()
            ->getRepository(\App\Entity\Restaurant::class)
            ->find($restaurantId);

        if (!$restaurant) {
            return [];
        }

        return $restaurant->getCouriers()->filter(function (User $user) {
            return in_array('ROLE_COURIER', $user->getRoles());
        })->values();
    }

    /**
     * Get the total number of users grouped by role
     * Returns an array like ['ROLE_CUSTOMER' => 50, 'ROLE_RESTAURANT' => 10, ...]
     */
    public function getTotalUsersByRole(): array
    {
        $users = $this->findAll();

        $rolesCounts = [
            'ROLE_CUSTOMER' => 0,
            'ROLE_RESTAURANT' => 0,
            'ROLE_COURIER' => 0,
            'ROLE_ADMIN' => 0,
        ];

        foreach ($users as $user) {
            $roles = $user->getRoles();
            // Count primary role (most specific role)
            if (in_array('ROLE_ADMIN', $roles)) {
                $rolesCounts['ROLE_ADMIN']++;
            } elseif (in_array('ROLE_RESTAURANT', $roles)) {
                $rolesCounts['ROLE_RESTAURANT']++;
            } elseif (in_array('ROLE_COURIER', $roles)) {
                $rolesCounts['ROLE_COURIER']++;
            } elseif (in_array('ROLE_CUSTOMER', $roles)) {
                $rolesCounts['ROLE_CUSTOMER']++;
            }
        }

        return $rolesCounts;
    }

    /**
     * Get the number of new users created within a specific time period
     */
    public function getNewUsersInPeriod(\DateTimeInterface $start, \DateTimeInterface $end): int
    {
        $result = $this->createQueryBuilder('u')
            ->select('COUNT(u.id)')
            ->where('u.id >= :start')
            ->andWhere('u.id <= :end')
            ->setParameter('start', $start)
            ->setParameter('end', $end)
            ->getQuery()
            ->getSingleScalarResult();

        return $result ? (int) $result : 0;
    }

    /**
     * Get the count of active customers (customers who have placed at least one order)
     */
    public function getActiveCustomersCount(): int
    {
        $result = $this->createQueryBuilder('u')
            ->select('COUNT(DISTINCT u.id)')
            ->innerJoin('u.orders', 'o')
            ->getQuery()
            ->getSingleScalarResult();

        return $result ? (int) $result : 0;
    }
}
