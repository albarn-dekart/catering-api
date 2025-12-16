<?php

namespace App\Filter;

use ApiPlatform\Doctrine\Orm\Filter\AbstractFilter;
use ApiPlatform\Doctrine\Orm\Util\QueryNameGeneratorInterface;
use ApiPlatform\Metadata\Operation;
use Doctrine\ORM\QueryBuilder;

class OrderSearchFilter extends AbstractFilter
{
    protected function filterProperty(string $property, $value, QueryBuilder $queryBuilder, QueryNameGeneratorInterface $queryNameGenerator, string $resourceClass, Operation $operation = null, array $context = []): void
    {
        if ($property !== 'search') {
            return;
        }

        $alias = $queryBuilder->getRootAliases()[0];
        $customerAlias = $queryNameGenerator->generateJoinAlias('customer');

        $queryBuilder
            ->leftJoin(sprintf('%s.customer', $alias), $customerAlias)
            ->andWhere(sprintf(
                'CONCAT(%1$s.id, \'\') LIKE :search OR LOWER(%1$s.deliveryFirstName) LIKE LOWER(:search) OR LOWER(%1$s.deliveryLastName) LIKE LOWER(:search) OR LOWER(%1$s.deliveryCity) LIKE LOWER(:search) OR LOWER(%1$s.deliveryStreet) LIKE LOWER(:search) OR LOWER(%2$s.email) LIKE LOWER(:search)',
                $alias,
                $customerAlias
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
                'description' => 'Search across multiple fields (Order ID, Name, City, Street, Customer Email) in Orders',
            ],
        ];
    }
}
