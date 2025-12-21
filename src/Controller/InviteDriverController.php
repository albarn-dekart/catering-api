<?php

namespace App\Controller;

use App\Entity\Restaurant;
use App\Entity\User;
use App\Service\MailerService;
use Doctrine\ORM\EntityManagerInterface;
use Random\RandomException;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\ExpressionLanguage\Expression;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Attribute\AsController;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Symfony\Component\Validator\Validator\ValidatorInterface;

#[AsController]
class InviteDriverController extends AbstractController
{
    public function __construct(
        private EntityManagerInterface $entityManager,
        private UserPasswordHasherInterface $passwordHasher,
        private ValidatorInterface $validator,
        private MailerService $mailerService
    ) {}

    /**
     * @throws RandomException
     */
    #[Route('/api/invite-driver', name: 'invite_driver', methods: ['POST'])]
    #[IsGranted(new Expression("is_granted('ROLE_RESTAURANT') or is_granted('ROLE_ADMIN')"))]
    public function __invoke(Request $request): JsonResponse
    {
        $data = json_decode($request->getContent(), true);

        /** @var User|null $currentUser */
        $currentUser = $this->getUser();
        $restaurant = $currentUser->getOwnedRestaurant();

        if ($this->isGranted('ROLE_ADMIN') && isset($data['restaurantId'])) {
            $restaurant = $this->entityManager->getRepository(Restaurant::class)->find($data['restaurantId']);

            if (!$restaurant) {
                return new JsonResponse(['error' => 'Restaurant not found'], 404);
            }
        }

        if (!$restaurant) {
            return new JsonResponse(['error' => 'You do not have a restaurant associated with your account'], 403);
        }

        $email = $data['email'] ?? null;

        if (!$email) {
            return new JsonResponse(['error' => 'Email is required'], 400);
        }

        $plainPassword = bin2hex(random_bytes(6)); // Generates a 12-char random password

        $user = new User();
        $user->setEmail($email);
        $user->setRoles(['ROLE_DRIVER']);
        // Add driver to restaurant's drivers collection
        $restaurant->addDriver($user);

        // Validate User
        $errors = $this->validator->validate($user);
        if (count($errors) > 0) {
            $errorMessages = [];
            foreach ($errors as $error) {
                $errorMessages[] = $error->getMessage();
            }
            return new JsonResponse(['error' => implode(', ', $errorMessages)], 400);
        }

        $hashedPassword = $this->passwordHasher->hashPassword($user, $plainPassword);
        $user->setPassword($hashedPassword);

        $this->entityManager->persist($user);
        $this->entityManager->flush();

        // Send invitation email
        $this->mailerService->sendDriverInvitation($email, $plainPassword);

        return new JsonResponse([
            'id' => $user->getId(),
            'email' => $user->getEmail(),
            'roles' => $user->getRoles(),
        ], 201);
    }
}
