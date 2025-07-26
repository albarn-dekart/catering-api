<?php

namespace App\Controller;

use App\Entity\User;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Component\Validator\Validator\ValidatorInterface;
use Symfony\Component\Security\Http\Attribute\IsGranted;

class UserPasswordController extends AbstractController
{
    #[Route('/users/{id}/change-password', name: 'user_change_password', methods: ['PUT'])]
    #[IsGranted('ROLE_USER')]
    public function changePassword(Request $request, User $user, UserPasswordHasherInterface $passwordHasher, ValidatorInterface $validator): JsonResponse
    {
        // Check if the authenticated user is changing their own password
        if ($this->getUser() !== $user) {
            return new JsonResponse(['message' => 'You can only change your own password.'], Response::HTTP_FORBIDDEN);
        }

        $content = json_decode($request->getContent(), true);

        // Validate input
        $constraints = new Assert\Regex([
            'pattern' => '/^(?=.*[A-Z])(?=.*\d).{8,}$/',
            'message' => 'Password must be 8+ chars with 1 uppercase letter and 1 digit.'
        ]);

        $violations = $validator->validate($content, $constraints);
        if ($violations->count() > 0) {
            $errors = [];
            foreach ($violations as $violation) {
                $errors[$violation->getPropertyPath()] = $violation->getMessage();
            }
            return new JsonResponse(['errors' => $errors], Response::HTTP_BAD_REQUEST);
        }

        // Verify current password
        if (!$passwordHasher->isPasswordValid($user, trim($content['currentPassword']))) {
            return new JsonResponse(['message' => 'Current password is incorrect.'], Response::HTTP_UNAUTHORIZED);
        }

        // Update password
        $user->setPassword(
            $passwordHasher->hashPassword($user, trim($content['newPassword']))
        );

        // Persist changes
        $entityManager = $this->getDoctrine()->getManager();
        $entityManager->persist($user);
        $entityManager->flush();

        return new JsonResponse(['message' => 'Password changed successfully.'], Response::HTTP_OK);
    }
}