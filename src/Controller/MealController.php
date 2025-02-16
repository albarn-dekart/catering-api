<?php

namespace App\Controller;


use App\Entity\Meal;
use App\Repository\MealPlanRepository;
use App\Repository\MealRepository;
use App\Repository\CategoryRepository;
use App\Repository\OrderRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Component\Validator\Validator\ValidatorInterface;

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

    #[Route('/api/admin/meal/{id?}', name: 'api_save_meal', methods: ['POST', 'PUT'])]
    public function saveMeal(
        Request                $request,
        EntityManagerInterface $entityManager,
        MealRepository         $mealRepository,
        CategoryRepository     $categoryRepository,
        ValidatorInterface     $validator,
        ?int                   $id = null
    ): JsonResponse
    {
        $data = json_decode($request->getContent(), true);

        // Input validation constraints
        $constraints = new Assert\Collection([
            'name' => [
                new Assert\NotBlank(['message' => 'Meal name is required.']),
                new Assert\Length([
                    'max' => 50,
                    'maxMessage' => 'Meal name cannot be longer than {{ limit }} characters.',
                ]),
            ],
            'price' => [
                new Assert\NotBlank(['message' => 'Price is required.']),
                new Assert\Type(['type' => 'numeric', 'message' => 'Price must be a number.']),
                new Assert\Positive(['message' => 'Price must be a positive number.']),
            ],
            'calories' => [
                new Assert\NotBlank(['message' => 'Calories are required.']),
                new Assert\Type(['type' => 'numeric', 'message' => 'Calories must be a number.']),
                new Assert\PositiveOrZero(['message' => 'Calories must not be a negative number.']),
            ],
            'carbs' => [
                new Assert\NotBlank(['message' => 'Carbs are required.']),
                new Assert\Type(['type' => 'numeric', 'message' => 'Carbs must be a number.']),
                new Assert\PositiveOrZero(['message' => 'Carbs must not be a negative number.']),
            ],
            'protein' => [
                new Assert\NotBlank(['message' => 'Protein is required.']),
                new Assert\Type(['type' => 'numeric', 'message' => 'Protein must be a number.']),
                new Assert\PositiveOrZero(['message' => 'Protein must not be a negative number.']),
            ],
            'fat' => [
                new Assert\NotBlank(['message' => 'Fat is required.']),
                new Assert\Type(['type' => 'numeric', 'message' => 'Fat must be a number.']),
                new Assert\PositiveOrZero(['message' => 'Fat must not be a negative number.']),
            ],
            'categories' => [
                new Assert\NotBlank(['message' => 'Categories are required.']),
                new Assert\Type(['type' => 'array', 'message' => 'Categories must be an array.']),
                new Assert\All([
                    new Assert\Type(['type' => 'numeric', 'message' => 'Category ID must be a number.']),
                    new Assert\PositiveOrZero(['message' => 'Category ID must not be a negative number.']),
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
            $meal = $mealRepository->find($id);
            if (!$meal) {
                return new JsonResponse(['error' => "Meal with ID $id not found"], Response::HTTP_NOT_FOUND);
            }
        } else {
            $meal = new Meal();
        }

        // Set meal properties
        $meal->setName($data['name']);
        $meal->setPrice($data['price']);
        $meal->setCalories($data['calories']);
        $meal->setCarbs($data['carbs']);
        $meal->setProtein($data['protein']);
        $meal->setFat($data['fat']);

        // Clear existing categories and add new ones
        $meal->clearCategories();
        foreach ($data['categories'] as $categoryId) {
            $meal->addCategory($categoryRepository->find($categoryId));
        }

        if ($id == null) {
            $entityManager->persist($meal);
        }
        $entityManager->flush();

        // Return appropriate response
        return new JsonResponse(null, $id == null ? Response::HTTP_CREATED : Response::HTTP_NO_CONTENT);
    }

    #[Route('/api/admin/meal/{id}/delete', name: 'api_delete_meal', methods: ['DELETE'])]
    public function deleteMeal(MealRepository $mealRepository, OrderRepository $orderRepository, MealPlanRepository $mealPlanRepository, int $id, EntityManagerInterface $entityManager): JsonResponse
    {
        $meal = $mealRepository->find($id);
        if (!$meal) {
            return new JsonResponse(['error' => "Meal with ID $id not found"], Response::HTTP_NOT_FOUND);
        }

        $orders = $orderRepository->findByMeal($meal);
        if (count($orders) > 0) {
            return new JsonResponse(['error' => 'Cannot delete meal as it is referenced by one or more orders.'], Response::HTTP_CONFLICT);
        }

        $mealPlans = $mealPlanRepository->findByMeal($meal);
        if (count($mealPlans) > 0) {
            return new JsonResponse(['error' => 'Cannot delete meal as it is referenced by one or more meal plans.'], Response::HTTP_CONFLICT);
        }

        $entityManager->remove($meal);
        $entityManager->flush();
        return new JsonResponse(null, Response::HTTP_NO_CONTENT);
    }
}
