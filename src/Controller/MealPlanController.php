<?php

namespace App\Controller;

use App\Entity\MealPlan;
use App\Repository\MealPlanRepository;
use App\Repository\MealRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class MealPlanController extends AbstractController
{
    #[Route('/api/meal_plans', name: 'api_get_meals_plans', methods: ['GET'])]
    public function getMealPlans(MealPlanRepository $repository): JsonResponse
    {
        $data = [];

        foreach ($repository->findAll() as $mealPlan) {
            $meals = [];

            foreach ($mealPlan->getMeals() as $meal) {
                $meals[] = $meal->data();
            }

            $data[] = [
                'id' => $mealPlan->getId(),
                'name' => $mealPlan->getName(),
                'description' => $mealPlan->getDescription(),
                'imageUrl' => $mealPlan->getImageUrl(),
                'meals' => $meals,
            ];
        }

        return new JsonResponse($data, Response::HTTP_OK);
    }

    #[Route('/api/admin/meal_plans/{id}/update', name: 'api_update_meal_plan', methods: ['PUT'])]
    public function updateMealPlan(
        Request                $request,
        EntityManagerInterface $entityManager,
        MealPlanRepository     $mealPlanRepository,
        MealRepository         $mealRepository,
        int                    $id
    ): JsonResponse
    {
        /** @var MealPlan $mealPlan */
        $mealPlan = $mealPlanRepository->find($id);
        $data = json_decode($request->getContent(), true);

        $mealPlan->clearMeals();
        foreach ($data['meals'] as $mealId) {
            $mealPlan->addMeal($mealRepository->find($mealId));
        }

        $mealPlan->setName($data['name']);
        $mealPlan->setDescription($data['description']);
        $mealPlan->setImageUrl($data['imageUrl']);

        $entityManager->flush();

        return new JsonResponse(null, Response::HTTP_NO_CONTENT);
    }

    #[Route('/api/admin/meal_plans/new', name: 'api_new_meal_plan', methods: ['POST'])]
    public function newMealPlan(
        Request                $request,
        EntityManagerInterface $entityManager,
        MealRepository         $mealRepository
    ): JsonResponse
    {
        $mealPlan = new MealPlan();
        $data = json_decode($request->getContent(), true);

        foreach ($data['meals'] as $mealId) {
            $mealPlan->addMeal($mealRepository->find($mealId));
        }

        $mealPlan->setName($data['name']);
        $mealPlan->setDescription($data['description']);
        $mealPlan->setImageUrl($data['imageUrl']);

        $entityManager->persist($mealPlan);
        $entityManager->flush();

        return new JsonResponse(null, Response::HTTP_CREATED);
    }

    #[Route('/api/admin/meal_plans/{id}/delete', name: 'api_delete_meal_plan', methods: ['DELETE'])]
    public function deleteMealPlan(MealPlanRepository $repository, int $id, EntityManagerInterface $entityManager): JsonResponse
    {
        $mealPlan = $repository->find($id);
        if (!$mealPlan) return new JsonResponse(null, Response::HTTP_NOT_FOUND);

        $entityManager->remove($mealPlan);
        $entityManager->flush();

        return new JsonResponse(null, Response::HTTP_NO_CONTENT);
    }
}
