<?php

namespace App\State;

use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProcessorInterface;
use App\Entity\Restaurant;
use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use LogicException;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;
use Symfony\Component\Security\Core\Exception\AuthenticationException;

/**
 * A state processor that sets the restaurant on a new entity (like MealPlan or Meal)
 * based on the currently authenticated user.
 */
final readonly class RestaurantOwnedStateProcessor implements ProcessorInterface
{
    public function __construct(
        private EntityManagerInterface $entityManager,
        private Security               $security
    )
    {
    }

    /**
     * @param mixed $data The entity being processed
     */
    public function process(mixed $data, Operation $operation, array $uriVariables = [], array $context = [])
    {
        // 1. Get the current user
        $user = $this->security->getUser();

        if (!$user instanceof User) {
            throw new AuthenticationException('User is not authenticated.');
        }

        // 2. Get the restaurant from the user
        $restaurant = $user->getRestaurant();

        if (!$restaurant instanceof Restaurant) {
            throw new AccessDeniedException('The current user does not own a restaurant.');
        }

        // 3. Check if the entity has a `setRestaurant` method (defensive check)
        if (!method_exists($data, 'setRestaurant')) {
            throw new LogicException(sprintf('The class "%s" does not have a "setRestaurant" method.', get_class($data)));
        }

        // 4. Set the restaurant on the new entity
        $data->setRestaurant($restaurant);

        // 5. Persist the new entity
        $this->entityManager->persist($data);
        $this->entityManager->flush();

        return $data;
    }
}