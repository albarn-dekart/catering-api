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

        $value = trim($value);
        if ($value === '') {
            return;
        }

        $alias = $queryBuilder->getRootAliases()[0];
        $orderAlias = $queryNameGenerator->generateJoinAlias('order');
        $customerAlias = $queryNameGenerator->generateJoinAlias('customer');

        if (preg_match('/^#?\d+$/', $value)) {
            // ID search (exact) - Check both Delivery ID and Order ID
            $idValue = ltrim($value, '#');
            $queryBuilder
                ->leftJoin(sprintf('%s.order', $alias), $orderAlias)
                ->andWhere(sprintf('%s.id = :search OR %s.id = :search', $alias, $orderAlias))
                ->setParameter('search', $idValue);
        } else {
            // Partial search on name, city, street and customer email
            $queryBuilder
                ->leftJoin(sprintf('%s.order', $alias), $orderAlias)
                ->leftJoin(sprintf('%s.customer', $orderAlias), $customerAlias)
                ->andWhere(sprintf(
                    'LOWER(%2$s.deliveryFirstName) LIKE LOWER(:search) OR LOWER(%2$s.deliveryLastName) LIKE LOWER(:search) OR LOWER(%2$s.deliveryCity) LIKE LOWER(:search) OR LOWER(%2$s.deliveryStreet) LIKE LOWER(:search) OR LOWER(%3$s.email) LIKE LOWER(:search)',
                    $alias,
                    $orderAlias,
                    $customerAlias
                ))
                ->setParameter('search', '%' . $value . '%');
        }
    }

    public function getDescription(string $resourceClass): array
    {
        return [
            'search' => [
                'property' => 'search',
                'type' => 'string',
                'required' => false,
                'description' => 'Search across multiple fields (Delivery ID, Order ID, Name, City, Street) in Deliveries',
            ],
        ];
    }
}
