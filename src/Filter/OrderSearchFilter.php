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

        $value = trim($value);
        if ($value === '') {
            return;
        }

        $alias = $queryBuilder->getRootAliases()[0];
        $customerAlias = $queryNameGenerator->generateJoinAlias('customer');

        if (preg_match('/^#?\d+$/', $value)) {
            // ID search (exact)
            $idValue = ltrim($value, '#');
            $queryBuilder
                ->andWhere(sprintf('%s.id = :search', $alias))
                ->setParameter('search', $idValue);
        } else {
            // Delivery name or partial customer email search
            $queryBuilder
                ->leftJoin(sprintf('%s.customer', $alias), $customerAlias)
                ->andWhere(sprintf(
                    'LOWER(%1$s.deliveryFirstName) LIKE LOWER(:search) OR LOWER(%1$s.deliveryLastName) LIKE LOWER(:search) OR LOWER(%2$s.email) LIKE LOWER(:search)',
                    $alias,
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
                'description' => 'Search by Order ID',
            ],
        ];
    }
}
