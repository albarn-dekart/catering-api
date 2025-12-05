<?php

namespace App\Controller;

use App\Entity\Restaurant;
use App\Entity\RestaurantCategory;
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
class InviteRestaurantController extends AbstractController
{
    public function __construct(
        private readonly EntityManagerInterface $entityManager,
        private readonly UserPasswordHasherInterface $passwordHasher
    ) {}

    #[Route('/api/invite-restaurant', name: 'invite_restaurant', methods: ['POST'])]
    #[IsGranted('ROLE_ADMIN')]
    public function __invoke(Request $request): JsonResponse
    {
        $data = json_decode($request->getContent(), true);

        $email = $data['email'] ?? null;
        $plainPassword = $data['plainPassword'] ?? null;
        $restaurantName = $data['restaurantName'] ?? null;
        $restaurantDescription = $data['restaurantDescription'] ?? null;
        $categoryIds = $data['categoryIds'] ?? [];

        // Validate required fields
        if (!$email || !$plainPassword || !$restaurantName) {
            return new JsonResponse([
                'error' => 'Email, password, and restaurant name are required'
            ], 400);
        }

        // Validate password strength
        if (strlen($plainPassword) < 8) {
            return new JsonResponse([
                'error' => 'Password must be at least 8 characters'
            ], 400);
        }

        // Check if user already exists
        $existingUser = $this->entityManager->getRepository(User::class)->findOneBy(['email' => $email]);
        if ($existingUser) {
            return new JsonResponse([
                'error' => 'User with this email already exists'
            ], 409);
        }

        // Create Restaurant entity
        $restaurant = new Restaurant();
        $restaurant->setName($restaurantName);
        if ($restaurantDescription) {
            $restaurant->setDescription($restaurantDescription);
        }

        // Add categories if provided
        if (!empty($categoryIds)) {
            $categoryRepository = $this->entityManager->getRepository(RestaurantCategory::class);
            foreach ($categoryIds as $categoryId) {
                // Extract numeric ID from IRI if needed (e.g., "/api/restaurant_categories/1" -> 1)
                $numericId = is_numeric($categoryId)
                    ? (int) $categoryId
                    : (int) preg_replace('/.*\//', '', $categoryId);

                $category = $categoryRepository->find($numericId);
                if ($category) {
                    $restaurant->addRestaurantCategory($category);
                }
            }
        }

        // Create User entity
        $user = new User();
        $user->setEmail($email);
        $user->setRoles(['ROLE_RESTAURANT']);
        $user->setRestaurant($restaurant);

        // Hash password
        $hashedPassword = $this->passwordHasher->hashPassword($user, $plainPassword);
        $user->setPassword($hashedPassword);

        // Persist both entities
        $this->entityManager->persist($restaurant);
        $this->entityManager->persist($user);
        $this->entityManager->flush();

        return new JsonResponse([
            'user' => [
                'id' => $user->getId(),
                'email' => $user->getEmail(),
                'roles' => $user->getRoles(),
            ],
            'restaurant' => [
                'id' => $restaurant->getId(),
                'name' => $restaurant->getName(),
                'description' => $restaurant->getDescription(),
            ],
        ], 201);
    }
}
