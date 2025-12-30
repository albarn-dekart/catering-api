<?php

namespace App\Controller;

use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Attribute\AsController;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Core\User\UserInterface;

#[AsController]
class ChangePasswordController extends AbstractController
{
    public function __construct(
        private readonly EntityManagerInterface $entityManager,
        private readonly UserPasswordHasherInterface $passwordHasher
    ) {}

    #[Route('/api/change-password', name: 'change_password', methods: ['POST'])]
    public function __invoke(Request $request): JsonResponse
    {
        /** @var User|null $currentUser */
        $currentUser = $this->getUser();
        
        if (!$currentUser instanceof UserInterface) {
            return new JsonResponse([
                'error' => 'Not authenticated'
            ], 401);
        }

        $data = json_decode($request->getContent(), true);

        $oldPassword = $data['oldPassword'] ?? null;
        $newPassword = $data['newPassword'] ?? null;

        // Validate required fields
        if (!$oldPassword || !$newPassword) {
            return new JsonResponse([
                'error' => 'Old password and new password are required'
            ], 400);
        }

        // Verify old password
        if (!$this->passwordHasher->isPasswordValid($currentUser, $oldPassword)) {
            return new JsonResponse([
                'error' => 'Current password is incorrect'
            ], 400);
        }

        // Validate new password strength
        if (strlen($newPassword) < 8) {
            return new JsonResponse([
                'error' => 'New password must be at least 8 characters'
            ], 400);
        }

        // Hash and set new password
        $hashedPassword = $this->passwordHasher->hashPassword($currentUser, $newPassword);
        $currentUser->setPassword($hashedPassword);

        $this->entityManager->persist($currentUser);
        $this->entityManager->flush();

        return new JsonResponse([
            'message' => 'Password changed successfully'
        ], 200);
    }
}
