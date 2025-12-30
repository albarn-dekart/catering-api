<?php

namespace App\Controller;

use App\Repository\MealPlanRepository;
use App\Repository\RestaurantRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpKernel\Attribute\AsController;
use Symfony\Component\Routing\Attribute\Route;

#[AsController]
class HomeController extends AbstractController
{
    public function __construct(
        private readonly MealPlanRepository $mealPlanRepository,
        private readonly RestaurantRepository $restaurantRepository,
    ) {}

    #[Route('/api/home', name: 'get_home_data', methods: ['GET'])]
    public function getHomeData(): JsonResponse
    {
        // Get popular meal plans (top 8 across all restaurants)
        $popularMealPlansData = $this->mealPlanRepository->getPopularMealPlans(8);

        // Transform imagePath to imageUrl for meal plans
        $popularMealPlans = array_map(function ($item) {
            $item['imageUrl'] = $item['imagePath'] ? '/images/meal_plans/' . $item['imagePath'] : null;
            unset($item['imagePath']);
            return $item;
        }, $popularMealPlansData);

        // Get featured restaurants (top 8 by order count)
        $featuredRestaurantsData = $this->restaurantRepository->getFeaturedRestaurants(6);

        // Transform imagePath to imageUrl for restaurants
        $featuredRestaurants = array_map(function ($item) {
            $item['imageUrl'] = $item['imagePath'] ? '/images/restaurants/' . $item['imagePath'] : null;
            unset($item['imagePath']);
            return $item;
        }, $featuredRestaurantsData);

        return $this->json([
            'popularMealPlans' => $popularMealPlans,
            'featuredRestaurants' => $featuredRestaurants,
        ]);
    }
}
