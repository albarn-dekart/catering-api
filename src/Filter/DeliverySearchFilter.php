<?php

namespace App\Filter;

use ApiPlatform\Doctrine\Orm\Filter\AbstractFilter;
use ApiPlatform\Doctrine\Orm\Util\QueryNameGeneratorInterface;
use ApiPlatform\Metadata\Operation;
use Doctrine\ORM\QueryBuilder;

class DeliverySearchFilter extends AbstractFilter
{
    protected function filterProperty(string $property, $value, QueryBuilder $queryBuilder, QueryNameGeneratorInterface $queryNameGenerator, string $resourceClass, Operation $operation = null, array $context = []): void
    {
        if ($property !== 'search') {
            return;
        }

        $alias = $queryBuilder->getRootAliases()[0];
        $orderAlias = $queryNameGenerator->generateJoinAlias('order');

        $queryBuilder
            ->leftJoin(sprintf('%s.order', $alias), $orderAlias)
            ->andWhere(sprintf(
                'CONCAT(%1$s.id, \'\') LIKE :search OR LOWER(%1$s.deliveryFirstName) LIKE LOWER(:search) OR LOWER(%1$s.deliveryLastName) LIKE LOWER(:search) OR LOWER(%1$s.deliveryCity) LIKE LOWER(:search) OR LOWER(%1$s.deliveryStreet) LIKE LOWER(:search)',
                $orderAlias
            ))
            ->setParameter('search', '%' . $value . '%');
    }

    public function getDescription(string $resourceClass): array
    {
        return [
            'search' => [
                'property' => 'search',
                'type' => 'string',
                'required' => false,
                'description' => 'Search across multiple fields (Order ID, Name, City, Street) in Deliveries',
            ],
        ];
    }
}
