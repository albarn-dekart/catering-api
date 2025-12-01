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
     * @return User[] Returns an array of User objects with ROLE_DRIVER for a specific restaurant
     */
    public function findDriversByRestaurant(int $restaurantId): array
    {
        $users = $this->createQueryBuilder('u')
            ->andWhere('u.restaurant = :restaurantId')
            ->setParameter('restaurantId', $restaurantId)
            ->getQuery()
            ->getResult();

        // Filter in PHP to avoid database-specific JSON handling issues (PostgreSQL JSON vs Text)
        return array_values(array_filter($users, function (User $user) {
            return in_array('ROLE_DRIVER', $user->getRoles());
        }));
    }
}
