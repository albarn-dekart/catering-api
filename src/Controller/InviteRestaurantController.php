<?php

namespace App\Controller;

use App\Entity\User;
use App\Service\MailerService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Attribute\AsController;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Symfony\Component\Validator\Validator\ValidatorInterface;

#[AsController]
class InviteRestaurantController extends AbstractController
{
    public function __construct(
        private readonly EntityManagerInterface $entityManager,
        private readonly UserPasswordHasherInterface $passwordHasher,
        private readonly ValidatorInterface $validator,
        private readonly MailerService $mailerService
    ) {}

    /**
     * Invites a new restaurant owner by creating a user with ROLE_RESTAURANT.
     * The actual restaurant creation should be done via the GraphQL createRestaurant mutation,
     * passing the returned user IRI as the owner.
     */
    #[Route('/api/invite-restaurant-owner', name: 'invite_restaurant_owner', methods: ['POST'])]
    #[IsGranted('ROLE_ADMIN')]
    public function __invoke(Request $request): JsonResponse
    {
        $data = json_decode($request->getContent(), true);

        $email = $data['email'] ?? null;
        $restaurantName = $data['restaurantName'] ?? null; // Used only for email

        // Validate required fields
        if (!$email) {
            return new JsonResponse([
                'error' => 'Email is required'
            ], 400);
        }

        // Check if user already exists
        $existingUser = $this->entityManager->getRepository(User::class)->findOneBy(['email' => $email]);
        if ($existingUser) {
            return new JsonResponse([
                'error' => 'A user with this email already exists'
            ], 400);
        }

        $plainPassword = bin2hex(random_bytes(6)); // Generates a 12-char random password

        // Create User entity with ROLE_RESTAURANT
        $user = new User();
        $user->setEmail($email);
        $user->setRoles(['ROLE_RESTAURANT']);

        // Validate User
        $errors = $this->validator->validate($user);

        $errorMessages = [];
        foreach ($errors as $error) {
            $errorMessages[] = $error->getMessage();
        }

        if (count($errorMessages) > 0) {
            return new JsonResponse(['error' => implode(', ', array_unique($errorMessages))], 400);
        }

        // Hash password
        $hashedPassword = $this->passwordHasher->hashPassword($user, $plainPassword);
        $user->setPassword($hashedPassword);

        // Persist user
        $this->entityManager->persist($user);
        $this->entityManager->flush();

        // Send invitation email
        $this->mailerService->sendRestaurantInvitation($email, $plainPassword, $restaurantName ?? 'Your Restaurant');

        return new JsonResponse([
            'user' => [
                'id' => $user->getId(),
                'iri' => '/api/users/' . $user->getId(),
                'email' => $user->getEmail(),
                'roles' => $user->getRoles(),
            ],
        ], 201);
    }
}
