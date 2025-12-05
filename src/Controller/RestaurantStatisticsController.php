<?php

namespace App\Controller;

use App\Entity\User;
use App\Repository\DeliveryRepository;
use App\Repository\MealPlanRepository;
use App\Repository\OrderRepository;
use App\Repository\RestaurantRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpKernel\Attribute\AsController;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[AsController]
class RestaurantStatisticsController extends AbstractController
{
    public function __construct(
        private readonly RestaurantRepository $restaurantRepository,
        private readonly OrderRepository $orderRepository,
        private readonly DeliveryRepository $deliveryRepository,
        private readonly MealPlanRepository $mealPlanRepository,
    ) {}

    #[Route('/api/restaurants/{restaurantId}/statistics', name: 'get_restaurant_statistics', methods: ['GET'])]
    #[IsGranted('IS_AUTHENTICATED_FULLY')]
    public function getRestaurantStatistics(string $restaurantId): JsonResponse
    {
        // Get the restaurant entity
        $restaurant = $this->restaurantRepository->find($restaurantId);

        if (!$restaurant) {
            throw new NotFoundHttpException('Restaurant not found');
        }

        // Check authorization: admin or restaurant owner
        /** @var User|null $user */
        $user = $this->getUser();

        if (!$this->isGranted('ROLE_ADMIN')) {
            // Restaurant users can only access their own restaurant's statistics
            if (!$this->isGranted('ROLE_RESTAURANT') || $user->getRestaurant()?->getId() !== (int)$restaurantId) {
                throw new AccessDeniedHttpException('You do not have permission to view these statistics');
            }
        }

        // Get restaurant-specific statistics
        $totalRevenue = $this->orderRepository->getTotalRevenueByRestaurant($restaurant);
        $totalOrders = $this->orderRepository->getOrderCountByRestaurant($restaurant);
        $activeOrders = $this->orderRepository->getActiveOrdersByRestaurant($restaurant);

        // Calculate completed orders (all non-unpaid orders minus active orders)
        $completedOrders = $totalOrders - $activeOrders;

        // Get delivery statistics
        $deliveriesByStatus = $this->deliveryRepository->getDeliveriesByStatus($restaurant);
        $totalDeliveries = array_sum($deliveriesByStatus);
        $deliverySuccessRate = $this->deliveryRepository->getDeliverySuccessRate($restaurant);

        // Get popular meal plans
        $popularMealPlansData = $this->mealPlanRepository->getPopularMealPlans(5, $restaurant);
        $popularMealPlans = array_map(function ($item) {
            return [
                'name' => $item['name'],
                'orderCount' => $item['orderCount']
            ];
        }, $popularMealPlansData);

        // Get revenue time series for charts (last 30 days)
        $revenueTimeSeries = $this->orderRepository->getRevenueTimeSeries(30, $restaurant);

        return $this->json([
            'totalRevenue' => $totalRevenue,
            'totalOrders' => $totalOrders,
            'activeOrders' => $activeOrders,
            'completedOrders' => $completedOrders,
            'totalDeliveries' => $totalDeliveries,
            'deliverySuccessRate' => $deliverySuccessRate,
            'popularMealPlans' => $popularMealPlans,
            'revenueTimeSeries' => $revenueTimeSeries,
        ]);
    }
}
