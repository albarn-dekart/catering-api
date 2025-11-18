<?php

namespace App\Doctrine;

use ApiPlatform\Doctrine\Orm\Extension\QueryCollectionExtensionInterface;
use ApiPlatform\Doctrine\Orm\Util\QueryNameGeneratorInterface;
use ApiPlatform\Metadata\Operation;
use App\Entity\Delivery;
use App\Entity\User;
use Doctrine\ORM\QueryBuilder;
use Symfony\Bundle\SecurityBundle\Security;

final readonly class DeliveryQueryExtension implements QueryCollectionExtensionInterface
{
    public function __construct(private Security $security)
    {
    }

    public function applyToCollection(
        QueryBuilder $queryBuilder,
        QueryNameGeneratorInterface $queryNameGenerator,
        string $resourceClass,
        ?Operation $operation = null,
        array $context = []
    ): void {
        if (Delivery::class !== $resourceClass || !$this->security->getUser() instanceof User) {
            return;
        }

        $user = $this->security->getUser();
        $rootAlias = $queryBuilder->getRootAliases()[0];

        // 1. Admins see everything.
        if ($this->security->isGranted('ROLE_ADMIN')) {
            return;
        }

        // 2. Drivers see deliveries assigned to them.
        if ($this->security->isGranted('ROLE_DRIVER')) {
            $queryBuilder->andWhere(sprintf('%s.driver = :user', $rootAlias))
                ->setParameter('user', $user);
        }

        // 3. Restaurant Owners see deliveries for their restaurants.
        elseif ($this->security->isGranted('ROLE_RESTAURANT')) {
            if ($restaurant = $user->getRestaurant()) {
                $queryBuilder->innerJoin(sprintf('%s.order', $rootAlias), 'o')
                    ->andWhere('o.restaurant = :restaurant_id')
                    ->setParameter('restaurant_id', $restaurant);
            }
        }

        // 4. Customers should not see this collection, but if they hit it, they see nothing (no filter applied).
        // Since the collection operation is secured with "isGranted('ROLE_ADMIN') or isGranted('ROLE_RESTAURANT') or isGranted('ROLE_DRIVER')", 
        // this final case is only hit if a user somehow slips through or the user is not a driver/owner/admin.
    }
}