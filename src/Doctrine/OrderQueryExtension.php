<?php

namespace App\Doctrine;

use ApiPlatform\Doctrine\Orm\Extension\QueryCollectionExtensionInterface;
use ApiPlatform\Doctrine\Orm\Util\QueryNameGeneratorInterface;
use ApiPlatform\Metadata\Operation;
use App\Entity\Order;
use App\Entity\User;
use Doctrine\ORM\QueryBuilder;
use Symfony\Bundle\SecurityBundle\Security;

final readonly class OrderQueryExtension implements QueryCollectionExtensionInterface
{
    public function __construct(private Security $security)
    {
    }

    public function applyToCollection(
        QueryBuilder                $queryBuilder,
        QueryNameGeneratorInterface $queryNameGenerator,
        string                      $resourceClass,
        ?Operation                  $operation = null,
        array                       $context = []
    ): void
    {
        // Only apply this extension to the Order resource
        if (Order::class !== $resourceClass) {
            return;
        }

        $user = $this->security->getUser();
        if (!$user instanceof User) {
            // Should not happen if security is set up, but good to check
            return;
        }

        // Admins can see everything.
        if ($this->security->isGranted('ROLE_ADMIN')) {
            return;
        }

        $rootAlias = $queryBuilder->getRootAliases()[0];

        // --- ROLE_RESTAURANT ---
        // Owners see all orders for their restaurant.
        if ($this->security->isGranted('ROLE_RESTAURANT')) {
            $restaurant = $user->getRestaurant();
            if ($restaurant) {
                $queryBuilder->andWhere(sprintf('%s.restaurant = :restaurant', $rootAlias))
                    ->setParameter('restaurant', $restaurant);
            }

            // --- ROLE_DRIVER ---
            // Drivers see orders they are assigned to deliver.
        } elseif ($this->security->isGranted('ROLE_DRIVER')) {
            $queryBuilder->innerJoin(sprintf('%s.deliveries', $rootAlias), 'd')
                ->andWhere('d.driver = :driver')
                ->setParameter('driver', $user);

            // --- ROLE_CUSTOMER (Customer) ---
            // A regular user only sees their own orders.
        } elseif ($this->security->isGranted('ROLE_CUSTOMER')) {
            $queryBuilder->andWhere(sprintf('%s.customer = :customer', $rootAlias))
                ->setParameter('customer', $user);
        }
    }
}