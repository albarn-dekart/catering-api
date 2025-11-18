<?php

namespace App\State;

use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProcessorInterface;
use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface; // 💡 1. Import EntityManager
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

final readonly class UserProcessor implements ProcessorInterface
{
    public function __construct(
        private UserPasswordHasherInterface $passwordHasher,
        private EntityManagerInterface $entityManager // 💡 2. Inject EntityManager
    ) {
    }

    /**
     * @param mixed $data
     * @param Operation $operation
     * @param array $uriVariables
     * @param array $context
     * @return mixed
     */
    public function process(mixed $data, Operation $operation, array $uriVariables = [], array $context = []): mixed // 💡 3. Change return type
    {
        if (!$data instanceof User) {
            return $data; // Not a user, do nothing
        }

        // --- Handle Customer Registration ---
        if ('/register' === $operation->getUriTemplate()) {
            $data->setRoles(['ROLE_CUSTOMER']);
        }

        // --- Handle Owner Registration ---
        // 💡 4. Fixed URI to match our ApiResource plan
        if ('/register/owner' === $operation->getUriTemplate()) {
            $data->setRoles(['ROLE_RESTAURANT']);
            if ($restaurant = $data->getRestaurant()) {
                $restaurant->setOwner($data);
            }
        }

        // --- Handle Password Hashing for any operation ---
        if ($data->getPlainPassword()) {
            $data->setPassword($this->passwordHasher->hashPassword($data, $data->getPlainPassword()));
            $data->eraseCredentials();
        }

        // 💡 5. Persist the data to the database!
        $this->entityManager->persist($data);
        $this->entityManager->flush();

        return $data;
    }
}