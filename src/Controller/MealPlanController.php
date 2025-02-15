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
use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Component\Validator\Validator\ValidatorInterface;

class MealPlanController extends AbstractController
{
    #[Route('/api/meal_plan', name: 'api_get_meals_plans', methods: ['GET'])]
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
                'imageFile' => $mealPlan->getImageFile(),
                'meals' => $meals,
            ];
        }

        return new JsonResponse($data, Response::HTTP_OK);
    }


    #[Route('/api/admin/meal_plan/{id?}', name: 'api_save_meal_plan', methods: ['POST', 'PUT'])]
    public function saveMealPlan(
        Request                $request,
        EntityManagerInterface $entityManager,
        MealPlanRepository     $mealPlanRepository,
        MealRepository         $mealRepository,
        ValidatorInterface     $validator,
        ?int                   $id = null
    ): JsonResponse
    {
        $data = json_decode($request->getContent(), true);

        // Input validation constraints
        $constraints = new Assert\Collection([
            'name' => [
                new Assert\NotBlank(['message' => 'Meal Plan name is required.']),
                new Assert\Length([
                    'max' => 50,
                    'maxMessage' => 'Meal Plan name cannot be longer than {{ limit }} characters.',
                ]),
            ],
            'description' => [
                new Assert\NotBlank(['message' => 'Description is required.']),
                new Assert\Length([
                    'max' => 500,
                    'maxMessage' => 'Description cannot be longer than {{ limit }} characters.',
                ]),
            ],
            'imageFile' => [
                new Assert\Optional(),
            ],
            'meals' => [
                new Assert\NotBlank(['message' => 'Meals are required.']),
                new Assert\Type(['type' => 'array', 'message' => 'Meals must be an array.']),
                new Assert\All([
                    new Assert\Type(['type' => 'numeric', 'message' => 'Meal ID must be a number.']),
                    new Assert\PositiveOrZero(['message' => 'Meal ID must not be a negative number.']),
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
            $mealPlan = $mealPlanRepository->find($id);
            if (!$mealPlan) {
                return new JsonResponse(['error' => "Meal Plan with ID $id not found"], Response::HTTP_NOT_FOUND);
            }
        } else {
            $mealPlan = new MealPlan();
        }

        // Clear existing meals and add new ones
        $mealPlan->clearMeals();
        foreach ($data['meals'] as $mealId) {
            $meal = $mealRepository->find($mealId);
            if (!$meal) {
                return new JsonResponse(['error' => "Meal with ID $id not found"], Response::HTTP_NOT_FOUND);
            }

            $mealPlan->addMeal($meal);
        }

        // Set meal plan properties
        $mealPlan->setName($data['name']);
        $mealPlan->setDescription($data['description']);
        if ($data['imageFile']) $mealPlan->setImageFile($data['imageFile']);

        // Persist new meal plan or update existing one
        if ($id == null) {
            $entityManager->persist($mealPlan);
        }
        $entityManager->flush();

        // Return appropriate response
        return new JsonResponse(null, $id == null ? Response::HTTP_CREATED : Response::HTTP_NO_CONTENT);
    }

    #[Route('/api/admin/meal_plan/{id}/delete', name: 'api_delete_meal_plan', methods: ['DELETE'])]
    public function deleteMealPlan(MealPlanRepository $repository, int $id, EntityManagerInterface $entityManager): JsonResponse
    {
        $mealPlan = $repository->find($id);
        if (!$mealPlan) {
            return new JsonResponse(['error' => "Meal Plan with ID $id not found"], Response::HTTP_NOT_FOUND);
        }

        $entityManager->remove($mealPlan);
        $entityManager->flush();

        return new JsonResponse(null, Response::HTTP_NO_CONTENT);
    }
}
