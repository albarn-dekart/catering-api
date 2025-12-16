<?php

namespace App\Doctrine;

use ApiPlatform\Doctrine\Orm\Extension\QueryCollectionExtensionInterface;
use ApiPlatform\Doctrine\Orm\Util\QueryNameGeneratorInterface;
use ApiPlatform\Metadata\Operation;
use App\Entity\MealPlan;
use Doctrine\ORM\QueryBuilder;
use Symfony\Bundle\SecurityBundle\Security;
use App\Entity\User;

class MealPlanExtension implements QueryCollectionExtensionInterface
{
    public function __construct(private Security $security) {}

    public function applyToCollection(
        QueryBuilder $queryBuilder,
        QueryNameGeneratorInterface $queryNameGenerator,
        string $resourceClass,
        ?Operation $operation = null,
        array $context = []
    ): void {
        if (MealPlan::class !== $resourceClass) {
            return;
        }

        $user = $this->security->getUser();

        // If admin, show everything
        if ($user instanceof User && in_array('ROLE_ADMIN', $user->getRoles())) {
            return;
        }

        $rootAlias = $queryBuilder->getRootAliases()[0];

        // Default: Show where owner is NULL (Public)
        $whereOr = "$rootAlias.owner IS NULL";

        // If user logged in, also show their own plans
        if ($user instanceof User) {
            $whereOr .= " OR $rootAlias.owner = :current_user";
            $queryBuilder->setParameter('current_user', $user);
        }

        $queryBuilder->andWhere($whereOr);
    }
}
