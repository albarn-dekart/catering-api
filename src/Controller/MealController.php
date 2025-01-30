<?php

namespace App\Controller;


use App\Repository\CategoryRepository;
use App\Repository\MealRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class MealController extends AbstractController
{
    #[Route('/api/meals/{category}', name: 'api_get_meals_by_category', methods: ['GET'])]
    public function getMealsByCategory(MealRepository $repository, string $category): JsonResponse
    {
        $data = [];

        foreach ($repository->findByCategory($category) as $meal) {
            $data[] = $meal->data();
        }

        return new JsonResponse($data, Response::HTTP_OK);
    }

    #[Route('/api/meals', name: 'api_get_meals', methods: ['GET'])]
    public function getMeals(MealRepository $repository): JsonResponse
    {
        $data = [];

        foreach ($repository->findAll() as $meal) {
            $data[] = $meal->data();
        }

        return new JsonResponse($data, Response::HTTP_OK);
    }

    #[Route('/api/categories', name: 'api_get_categories', methods: ['GET'])]
    public function getCategories(CategoryRepository $repository): JsonResponse
    {
        $data = [];

        foreach ($repository->findAll() as $category) {
            $data[] = $category->getName();
        }

        return new JsonResponse($data, Response::HTTP_OK);
    }
}
