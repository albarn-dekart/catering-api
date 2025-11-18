<?php

namespace App\State;

use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProviderInterface;
use App\Entity\User;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;

/**
 * A provider to retrieve the currently authenticated user for the /me endpoint.
 */
final readonly class MeStateProvider implements ProviderInterface
{
    public function __construct(
        private Security $security
    )
    {
    }

    /**
     * Provides the User object for the /me endpoint.
     */
    public function provide(Operation $operation, array $uriVariables = [], array $context = []): ?User
    {
        $user = $this->security->getUser();

        if (!$user instanceof User) {
            throw new AccessDeniedException('User is not authenticated.');
        }

        return $user;
    }
}