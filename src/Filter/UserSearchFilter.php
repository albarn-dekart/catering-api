<?php

namespace App\Filter;

use ApiPlatform\Doctrine\Orm\Filter\AbstractFilter;
use ApiPlatform\Doctrine\Orm\Util\QueryNameGeneratorInterface;
use ApiPlatform\Metadata\Operation;
use Doctrine\ORM\QueryBuilder;

class UserSearchFilter extends AbstractFilter
{
    protected function filterProperty(string $property, $value, QueryBuilder $queryBuilder, QueryNameGeneratorInterface $queryNameGenerator, string $resourceClass, Operation $operation = null, array $context = []): void
    {
        $alias = $queryBuilder->getRootAliases()[0];

        if ($property === 'search') {
            $value = trim($value);
            if ($value === '') {
                return;
            }

            if (preg_match('/^#?\d+$/', $value)) {
                // ID search (exact)
                $idValue = ltrim($value, '#');
                $queryBuilder
                    ->andWhere(sprintf('%s.id = :search', $alias))
                    ->setParameter('search', $idValue);
            } else {
                // General text / Partial email search
                $queryBuilder
                    ->andWhere(sprintf('LOWER(%s.email) LIKE LOWER(:search)', $alias))
                    ->setParameter('search', '%' . $value . '%');
            }
            return;
        }

        if ($property === 'role') {
            $value = trim($value);
            if ($value === '') {
                return;
            }
            // Using LIKE to search within the JSON array string representation
            // Cast to string using CONCAT for PostgreSQL JSON compatibility
            $queryBuilder
                ->andWhere(sprintf('CONCAT(%s.roles, \'\') LIKE :role', $alias))
                ->setParameter('role', '%' . $value . '%');
            return;
        }
    }

    public function getDescription(string $resourceClass): array
    {
        return [
            'search' => [
                'property' => 'search',
                'type' => 'string',
                'required' => false,
                'description' => 'Search by User ID (exact) or Email (partial)',
            ],
            'role' => [
                'property' => 'role',
                'type' => 'string',
                'required' => false,
                'description' => 'Filter by User Role',
            ],
        ];
    }
}
