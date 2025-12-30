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
        QueryBuilder                $queryBuilder,
        QueryNameGeneratorInterface $queryNameGenerator,
        string                      $resourceClass,
        ?Operation                  $operation = null,
        array                       $context = []
    ): void {
        if (MealPlan::class !== $resourceClass) {
            return;
        }

        $rootAlias = $queryBuilder->getRootAliases()[0];
        $dql = $queryBuilder->getDQL();

        // 1. If we are explicitly querying for an owner's plans (e.g., GetMyCustomMealPlans)
        // API Platform/Doctrine adds a filter like "mp.owner = :id" or joins it.
        if (str_contains($dql, "$rootAlias.owner")) {
            return;
        }

        // 2. If we are in a Restaurant context (e.g., Restaurant Menu / GetMealPlansByRestaurant)
        // API Platform adds a filter like "mp.restaurant = :id" or joins it.
        if (str_contains($dql, "$rootAlias.restaurant")) {
            $queryBuilder->andWhere("$rootAlias.owner IS NULL");
            return;
        }

        // 3. Default behavior (Global Search / Others): Show ONLY official plans.
        // Custom meal plans are only visible when explicitly requested via the user's collection (Step 1).
        $queryBuilder->andWhere("$rootAlias.owner IS NULL");
    }
}
