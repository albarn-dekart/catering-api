<?php

namespace App\Controller;

use App\Entity\User;
use App\Repository\DeliveryRepository;
use App\Repository\MealPlanRepository;
use App\Repository\OrderRepository;
use App\Repository\RestaurantRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
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

    /**
     * @throws \Exception
     */
    #[Route('/api/restaurants/{restaurantId}/statistics', name: 'get_restaurant_statistics', methods: ['GET'])]
    #[IsGranted('IS_AUTHENTICATED_FULLY')]
    public function getRestaurantStatistics(string $restaurantId, Request $request): JsonResponse
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

        $startDateStr = $request->query->get('startDate');
        $endDateStr = $request->query->get('endDate');

        $startDate = $startDateStr ? new \DateTime($startDateStr) : null;
        $endDate = $endDateStr ? new \DateTime($endDateStr) : null;

        // Set times to cover full days
        if ($startDate) {
            $startDate->setTime(0, 0, 0);
        }
        if ($endDate) {
            $endDate->setTime(23, 59, 59);
        }

        // Get restaurant-specific statistics
        $totalRevenue = $this->orderRepository->getTotalRevenueByRestaurant($restaurant, $startDate, $endDate);
        $totalOrders = $this->orderRepository->getOrderCountByRestaurant($restaurant, $startDate, $endDate);
        $activeOrders = $this->orderRepository->getActiveOrdersByRestaurant($restaurant, $startDate, $endDate);

        $completedOrders = $totalOrders - $activeOrders;

        // Get delivery statistics
        $deliveriesByStatus = $this->deliveryRepository->getDeliveriesByStatus($restaurant, $startDate, $endDate);
        $totalDeliveries = array_sum($deliveriesByStatus);
        $deliverySuccessRate = $this->deliveryRepository->getDeliverySuccessRate($restaurant, $startDate, $endDate);

        // Get popular meal plans
        $popularMealPlansData = $this->mealPlanRepository->getPopularMealPlans(5, $restaurant, $startDate, $endDate);
        $popularMealPlans = array_map(function ($item) {
            return [
                'name' => $item['name'],
                'orderCount' => $item['orderCount']
            ];
        }, $popularMealPlansData);

        // Get revenue time series for charts (last 30 days or range)
        $revenueTimeSeries = $this->orderRepository->getRevenueTimeSeries(30, $restaurant, $startDate, $endDate);

        // Get daily orders time series
        $dailyOrdersTimeSeries = $this->orderRepository->getDailyOrdersTimeSeries(30, $restaurant, $startDate, $endDate);

        return $this->json([
            'totalRevenue' => $totalRevenue,
            'totalOrders' => $totalOrders,
            'activeOrders' => $activeOrders,
            'completedOrders' => $completedOrders,
            'totalDeliveries' => $totalDeliveries,
            'deliverySuccessRate' => $deliverySuccessRate,
            'popularMealPlans' => $popularMealPlans,
            'revenueTimeSeries' => $revenueTimeSeries,
            'dailyOrdersTimeSeries' => $dailyOrdersTimeSeries,
        ]);
    }
}
