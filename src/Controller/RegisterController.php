<?php

namespace App\Controller;

use App\Entity\User;
use App\Repository\UserRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Serializer\SerializerInterface;
use Symfony\Component\Validator\Validator\ValidatorInterface;

class RegisterController extends AbstractController
{
    #[Route('/api/register', name: 'app_register', methods: ['POST'])]
    public function register(
        Request                     $request,
        UserPasswordHasherInterface $passwordHasher,
        EntityManagerInterface      $entityManager,
        SerializerInterface         $serializer,
        ValidatorInterface          $validator,
        UserRepository              $userRepository
    ): JsonResponse
    {
        // Check if user is already authenticated
        if ($this->getUser()) {
            return $this->json([
                'message' => 'You are already logged in'
            ], Response::HTTP_BAD_REQUEST);
        }

        try {
            /** @var User $user */
            $user = $serializer->deserialize($request->getContent(), User::class, 'json', [
                'groups' => ['user:create', 'user:write']
            ]);
        } catch (\Exception $e) {
            return $this->json([
                'message' => 'Invalid request data',
                'error' => $e->getMessage()
            ], Response::HTTP_BAD_REQUEST);
        }

        // Check if email already exists
        if ($userRepository->findOneBy(['email' => $user->getEmail()])) {
            return $this->json([
                'message' => 'Email already exists'
            ], Response::HTTP_CONFLICT);
        }

        // Validate the user data with user:create validation group
        $errors = $validator->validate($user, null, ['user:create']);
        if (count($errors) > 0) {
            $errorMessages = [];
            foreach ($errors as $error) {
                $errorMessages[$error->getPropertyPath()] = $error->getMessage();
            }

            return $this->json([
                'message' => 'Validation failed',
                'errors' => $errorMessages
            ], Response::HTTP_BAD_REQUEST);
        }

        // Hash the password
        $hashedPassword = $passwordHasher->hashPassword(
            $user,
            $user->getPassword()
        );
        $user->setPassword($hashedPassword);

        // Persist the user
        $entityManager->persist($user);
        $entityManager->flush();

        return $this->json([
            'message' => 'User registered successfully',
            'user' => $user
        ], Response::HTTP_CREATED, [], [
            'groups' => ['user:read']
        ]);
    }
}