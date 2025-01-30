<?php

namespace App\Controller;

use App\Entity\RecipientDetails;
use App\Entity\User;
use App\Repository\UserRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Component\Validator\Validator\ValidatorInterface;

class UserController extends AbstractController
{
    #[Route('/api/admin/users', name: 'api_users', methods: ['GET'])]
    public function getUsers(UserRepository $repository): JsonResponse
    {
        $users = $repository->findAll();
        $data = [];

        foreach ($users as $user) {
            $data[] = [
                'id' => $user->getId(),
                'email' => $user->getEmail(),
                'roles' => $user->getRoles(),
            ];
        }

        return new JsonResponse($data, Response::HTTP_OK);
    }

    #[Route('/api/admin/user/{id}', name: 'api_user_byId', methods: ['GET'])]
    public function getUserById(UserRepository $repository, int $id): JsonResponse
    {
        return $this->getUserDetails($repository, $id);
    }

    #[Route('/api/user', name: 'api_user_details', methods: ['GET'])]
    public function user(UserRepository $repository): JsonResponse
    {
        return $this->getUserDetails($repository);
    }

    public function getUserDetails(UserRepository $repository, int $id = null): JsonResponse
    {
        /** @var User $user */
        $user = $id ? $repository->find($id) : $this->getUser();

        $recipientDetails = $user->getRecipientDetails();
        $data = [
            'firstName' => $recipientDetails->getFirstName(),
            'secondName' => $recipientDetails->getSecondName(),
            'phoneNumber' => $recipientDetails->getPhoneNumber(),
            'city' => $recipientDetails->getCity(),
            'postcode' => $recipientDetails->getPostcode(),
            'address' => $recipientDetails->getAddress(),
        ];

        return new JsonResponse($data, Response::HTTP_OK);
    }

    #[Route('/api/admin/user/{id}', name: 'api_patch_details_byId', methods: ['PUT'])]
    public function updateDetailsById(Request $request, UserRepository $repository, EntityManagerInterface $entityManager, ValidatorInterface $validator,  int $id): JsonResponse
    {
        return $this->updateUserDetails($request, $repository, $entityManager, $validator, $id);
    }

    #[Route('/api/user', name: 'api_update_details', methods: ['PUT'])]
    public function updateDetails(Request $request, UserRepository $repository, EntityManagerInterface $entityManager, ValidatorInterface $validator): JsonResponse
    {
        return $this->updateUserDetails($request, $repository, $entityManager, $validator);
    }

    public function updateUserDetails(
        Request $request,
        UserRepository $repository,
        EntityManagerInterface $entityManager,
        ValidatorInterface $validator,
        int $id = null
    ): JsonResponse {
        /** @var User $user */
        $user = $id ? $repository->find($id) : $this->getUser();
        if (!$user) {
            return new JsonResponse(null, Response::HTTP_NOT_FOUND);
        }

        $recipientDetails = $user->getRecipientDetails();
        if (!$recipientDetails) {
            $recipientDetails = new RecipientDetails();
            $user->setRecipientDetails($recipientDetails);
        }

        $data = json_decode($request->getContent(), true);

        // Define validation constraints
        $constraints = new Assert\Collection([
            'firstName' => new Assert\Optional([
                new Assert\NotBlank(['message' => 'First name cannot be blank.']),
                new Assert\Length([
                    'max' => 50,
                    'maxMessage' => 'First name cannot exceed {{ limit }} characters.',
                ]),
            ]),
            'secondName' => new Assert\Optional([
                new Assert\NotBlank(['message' => 'Second name cannot be blank.']),
                new Assert\Length([
                    'max' => 50,
                    'maxMessage' => 'Second name cannot exceed {{ limit }} characters.',
                ]),
            ]),
            'city' => new Assert\Optional([
                new Assert\NotBlank(['message' => 'City cannot be blank.']),
                new Assert\Length([
                    'max' => 100,
                    'maxMessage' => 'City cannot exceed {{ limit }} characters.',
                ]),
            ]),
            'postCode' => new Assert\Optional([
                new Assert\NotBlank(['message' => 'Post code cannot be blank.']),
                new Assert\Regex([
                    'pattern' => '/^\d{2}-\d{3}$/',
                    'message' => 'Post code must follow the format XX-XXX.',
                ]),
            ]),
            'address' => new Assert\Optional([
                new Assert\NotBlank(['message' => 'Address cannot be blank.']),
                new Assert\Length([
                    'max' => 255,
                    'maxMessage' => 'Address cannot exceed {{ limit }} characters.',
                ]),
            ]),
            'phoneNumber' => new Assert\Optional([
                new Assert\NotBlank(['message' => 'Phone number cannot be blank.']),
                new Assert\Regex([
                    'pattern' => '/^\+?[0-9]{7,15}$/',
                    'message' => 'Phone number must be a valid international number.',
                ]),
            ]),
        ]);

        // Validate the input data
        $violations = $validator->validate($data, $constraints);

        if (count($violations) > 0) {
            $errors = [];
            foreach ($violations as $violation) {
                $errors[] = $violation->getMessage();
            }
            return new JsonResponse(['errors' => $errors], Response::HTTP_BAD_REQUEST);
        }

        // Update recipient details if validation passes
        if (isset($data['firstName'])) $recipientDetails->setFirstName($data['firstName']);
        if (isset($data['secondName'])) $recipientDetails->setSecondName($data['secondName']);
        if (isset($data['city'])) $recipientDetails->setCity($data['city']);
        if (isset($data['postCode'])) $recipientDetails->setPostCode($data['postCode']);
        if (isset($data['address'])) $recipientDetails->setAddress($data['address']);
        if (isset($data['phoneNumber'])) $recipientDetails->setPhoneNumber($data['phoneNumber']);

        $entityManager->flush();

        return new JsonResponse(null, Response::HTTP_NO_CONTENT);
    }

    #[Route('/api/admin/user/{id}/update_roles', name: 'api_update_user_roles', methods: ['PATCH'])]
    public function updateUserRoles(
        Request $request,
        UserRepository $repository,
        EntityManagerInterface $entityManager,
        int $id
    ): JsonResponse
    {
        /** @var User $user */
        $user = $repository->find($id);
        if (!$user) {
            return new JsonResponse(null, Response::HTTP_NOT_FOUND);
        }

        $data = json_decode($request->getContent(), true);
        $user->setRoles($data['roles']);

        $entityManager->flush();

        return new JsonResponse(null, Response::HTTP_NO_CONTENT);
    }

    #[Route('/api/admin/user/{id}/delete', name: 'api_delete_user', methods: ['DELETE'])]
    public function delete(UserRepository $repository, int $id, EntityManagerInterface $entityManager): JsonResponse
    {
        /** @var User|null $user */
        $user = $repository->find($id);
        if (!$user) return new JsonResponse(null, Response::HTTP_NOT_FOUND);
        $orders = $user->getOrders();

        try {
            foreach ($orders as $order) {
                $entityManager->remove($order);
            }
            $entityManager->remove($user);
            $entityManager->flush();

            return new JsonResponse(null, Response::HTTP_NO_CONTENT);
        } catch (\Exception $e) {
            return new JsonResponse(
                ['message' => $e->getMessage()],
                Response::HTTP_INTERNAL_SERVER_ERROR
            );
        }
    }

    #[Route('/api/user/change_password', name: 'api_change_password', methods: ['PATCH'])]
    public function changePassword(
        Request                     $request,
        EntityManagerInterface      $entityManager,
        UserPasswordHasherInterface $passwordHasher
    ): JsonResponse
    {
        /** @var User $user */
        $user = $this->getUser();

        $data = json_decode($request->getContent(), true);

        $currentPassword = $data['currentPassword'] ?? null;
        $newPassword = $data['newPassword'] ?? null;

        if (!$currentPassword || !$newPassword) {
            return new JsonResponse(['message' => 'Old and new passwords are required.'], Response::HTTP_BAD_REQUEST);
        }

        if (!$passwordHasher->isPasswordValid($user, $currentPassword)) {
            return new JsonResponse(['message' => 'Old password is incorrect.'], Response::HTTP_BAD_REQUEST);
        }

        $hashedNewPassword = $passwordHasher->hashPassword($user, $newPassword);
        $user->setPassword($hashedNewPassword);
        $entityManager->flush();

        return new JsonResponse(null, Response::HTTP_NO_CONTENT);
    }
}