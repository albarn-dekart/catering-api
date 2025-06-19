<?php

namespace App\Controller;

use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use Ramsey\Uuid\Uuid;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Validator\Validator\ValidatorInterface;
use Symfony\Component\Validator\Constraints as Assert;

class RegisterController extends AbstractController
{
    #[Route('/api/register', name: 'register', methods: ['POST'])]
    public function register(
        Request $request,
        EntityManagerInterface $entityManager,
        UserPasswordHasherInterface $passwordHasher,
        ValidatorInterface $validator,
    ): JsonResponse {
        $data = json_decode($request->getContent(), true);

        $constraints = new Assert\Collection([
            'email' => [
                new Assert\NotBlank(['message' => 'Email is required.']),
                new Assert\Email(['message' => 'Invalid email format.']),
            ],
            'password' => [
                new Assert\NotBlank(['message' => 'Password is required.']),
                new Assert\Length([
                    'min' => 8,
                    'minMessage' => 'Password must be at least {{ limit }} characters long.',
                ]),
                new Assert\Regex([
                    'pattern' => '/[A-Z]/',
                    'message' => 'Password must contain at least one uppercase letter.',
                ]),
                new Assert\Regex([
                    'pattern' => '/\d/',
                    'message' => 'Password must contain at least one digit.',
                ]),
                new Assert\Regex([
                    'pattern' => '/[\W_]/',
                    'message' => 'Password must contain at least one special character.',
                ]),
            ],
        ]);

        // Validate input
        $violations = $validator->validate($data, $constraints);

        if (count($violations) > 0) {
            $errors = [];
            foreach ($violations as $violation) {
                $errors[] = $violation->getMessage();
            }
            return new JsonResponse(['error' => $errors], Response::HTTP_BAD_REQUEST);
        }

        // Check if user already exists
        if (!$entityManager->getRepository(User::class)->findOneBy(['email' => $data['email']])) {
            $user = new User();
            $user->setEmail($data['email']);

            // Create and set verification token
            $verificationToken = Uuid::uuid4()->toString();
            $user->setVerificationToken($verificationToken);

            // Hash the password and set it
            $hashedPassword = $passwordHasher->hashPassword($user, $data['password']);
            $user->setPassword($hashedPassword);

            // Save the user to the database
            $entityManager->persist($user);
            $entityManager->flush();
        }

        return new JsonResponse(null, Response::HTTP_NO_CONTENT);
    }
}
