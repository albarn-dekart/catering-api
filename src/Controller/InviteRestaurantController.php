<?php

namespace App\Controller;

use App\Entity\Restaurant;
use App\Entity\RestaurantCategory;
use App\Entity\User;
use App\Service\MailerService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Attribute\AsController;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Routing\Annotation\Route;
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

    #[Route('/api/invite-restaurant', name: 'invite_restaurant', methods: ['POST'])]
    #[IsGranted('ROLE_ADMIN')]
    public function __invoke(Request $request): JsonResponse
    {
        $data = json_decode($request->getContent(), true);

        $email = $data['email'] ?? null;
        $restaurantName = $data['restaurantName'] ?? null;
        $restaurantDescription = $data['restaurantDescription'] ?? null;
        $categoryIds = $data['categoryIds'] ?? [];

        // Validate required fields
        if (!$email || !$restaurantName) {
            return new JsonResponse([
                'error' => 'Email and restaurant name are required'
            ], 400);
        }

        $plainPassword = bin2hex(random_bytes(6)); // Generates a 12-char random password

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
                // Extract numeric ID from IRI if needed
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

        // Validate Entities
        $errors = $this->validator->validate($restaurant);
        $userErrors = $this->validator->validate($user);

        $errorMessages = [];
        foreach ($errors as $error) {
            $errorMessages[] = $error->getMessage();
        }
        foreach ($userErrors as $error) {
            $errorMessages[] = $error->getMessage();
        }

        if (count($errorMessages) > 0) {
            return new JsonResponse(['error' => implode(', ', array_unique($errorMessages))], 400);
        }

        // Hash password
        $hashedPassword = $this->passwordHasher->hashPassword($user, $plainPassword);
        $user->setPassword($hashedPassword);

        // Persist both entities
        $this->entityManager->persist($restaurant);
        $this->entityManager->persist($user);
        $this->entityManager->flush();

        // Send invitation email
        $this->mailerService->sendRestaurantInvitation($email, $plainPassword, $restaurantName);

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
