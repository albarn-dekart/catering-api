<?php

namespace App\Controller;

use App\Entity\Category;
use App\Repository\CategoryRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Component\Validator\Validator\ValidatorInterface;

class CategoryController extends AbstractController
{
    #[Route('/categories', name: 'get_categories', methods: ['GET'])]
    public function getCategories(CategoryRepository $repository): JsonResponse
    {
        $data = [];

        foreach ($repository->findAll() as $category) {
            $data[] = ["id" => $category->getId(), "name" => $category->getName()];
        }

        return new JsonResponse($data, Response::HTTP_OK);
    }


    #[Route('/admin/category/{id?}', name: 'save_category', methods: ['POST', 'PUT'])]
    public function saveCategory(
        Request                $request,
        EntityManagerInterface $entityManager,
        CategoryRepository     $categoryRepository,
        ValidatorInterface     $validator,
        ?int                   $id = null
    ): JsonResponse
    {
        $data = json_decode($request->getContent(), true);

        // Input validation constraints
        $constraints = new Assert\Collection([
            'name' => [
                new Assert\NotBlank(['message' => 'Category name is required.']),
                new Assert\Length([
                    'max' => 50,
                    'maxMessage' => 'Category name cannot be longer than {{ limit }} characters.',
                ]),
                new Assert\Regex([
                    'pattern' => '/^[a-zA-Z0-9\s\-_]+$/',
                    'message' => 'Category name can only contain letters, numbers, spaces, hyphens, and underscores.',
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

        if ($id != null) {
            $category = $categoryRepository->find($id);
            if (!$category) {
                return new JsonResponse(['error' => "Category with ID $id not found"], Response::HTTP_NOT_FOUND);
            }
        } else {
            $category = new Category();
        }

        // Set category properties
        $category->setName($data['name']);

        if ($id == null) {
            $entityManager->persist($category);
        }
        $entityManager->flush();


        return new JsonResponse(null, $id == null ? Response::HTTP_CREATED : Response::HTTP_NO_CONTENT);
    }

    #[Route('/admin/category/{id}/delete', name: 'delete_category', methods: ['DELETE'])]
    public function deleteCategory(CategoryRepository $categoryRepository, int $id, EntityManagerInterface $entityManager): JsonResponse
    {
        $category = $categoryRepository->find($id);

        if (!$category) {
            return new JsonResponse(['error' => "Category with ID $id not found"], Response::HTTP_NOT_FOUND);
        }

        $entityManager->remove($category);
        $entityManager->flush();
        return new JsonResponse(null, Response::HTTP_NO_CONTENT);
    }
}