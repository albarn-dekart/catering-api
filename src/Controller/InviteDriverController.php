<?php

namespace App\Controller;

use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Attribute\AsController;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[AsController]
class InviteDriverController extends AbstractController
{
    public function __construct(
        private EntityManagerInterface $entityManager,
        private UserPasswordHasherInterface $passwordHasher
    ) {}

    #[Route('/api/invite-driver', name: 'invite_driver', methods: ['POST'])]
    #[IsGranted('ROLE_RESTAURANT')]
    public function __invoke(Request $request): JsonResponse
    {
        $data = json_decode($request->getContent(), true);

        $email = $data['email'] ?? null;
        $plainPassword = $data['plainPassword'] ?? null;

        if (!$email || !$plainPassword) {
            return new JsonResponse(['error' => 'Email and password are required'], 400);
        }

        if (strlen($plainPassword) < 8) {
            return new JsonResponse(['error' => 'Password must be at least 8 characters'], 400);
        }

        // Check if user already exists
        $existingUser = $this->entityManager->getRepository(User::class)->findOneBy(['email' => $email]);
        if ($existingUser) {
            return new JsonResponse(['error' => 'User with this email already exists'], 409);
        }

        /** @var User $currentUser */
        $currentUser = $this->getUser();
        $restaurant = $currentUser->getRestaurant();

        if (!$restaurant) {
            return new JsonResponse(['error' => 'You do not have a restaurant associated with your account'], 403);
        }

        $user = new User();
        $user->setEmail($email);
        $user->setRoles(['ROLE_DRIVER']);
        $user->setRestaurant($restaurant);

        $hashedPassword = $this->passwordHasher->hashPassword($user, $plainPassword);
        $user->setPassword($hashedPassword);

        $this->entityManager->persist($user);
        $this->entityManager->flush();

        return new JsonResponse([
            'id' => $user->getId(),
            'email' => $user->getEmail(),
            'roles' => $user->getRoles(),
        ], 201);
    }
}
