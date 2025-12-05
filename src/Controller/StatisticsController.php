<?php

namespace App\Controller;

use App\Enum\OrderStatus;
use App\Repository\OrderRepository;
use App\Repository\UserRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpKernel\Attribute\AsController;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[AsController]
class StatisticsController extends AbstractController
{
    public function __construct(
        private readonly OrderRepository $orderRepository,
        private readonly UserRepository $userRepository,
    ) {}

    #[Route('/api/statistics', name: 'get_statistics', methods: ['GET'])]
    #[IsGranted('ROLE_ADMIN')]
    public function getStatistics(): JsonResponse
    {
        // Get total revenue
        $totalRevenue = $this->orderRepository->getTotalRevenue();

        // Get total orders count
        $orderCountsByStatus = $this->orderRepository->getOrderCountByStatus();
        $totalOrders = array_sum($orderCountsByStatus);

        // Get active orders (Paid + Active status)
        $activeOrders = ($orderCountsByStatus[OrderStatus::Paid->value] ?? 0) +
            ($orderCountsByStatus[OrderStatus::Active->value] ?? 0);

        // Get average order value
        $averageOrderValue = $this->orderRepository->getAverageOrderValue();

        // Get total users
        $usersByRole = $this->userRepository->getTotalUsersByRole();
        $totalUsers = array_sum($usersByRole);

        // Get revenue time series for charts (last 30 days)
        $revenueTimeSeries = $this->orderRepository->getRevenueTimeSeries(30);

        // Get orders by status for pie chart
        $ordersByStatus = $this->orderRepository->getOrderCountByStatus();

        return $this->json([
            'totalRevenue' => $totalRevenue,
            'totalOrders' => $totalOrders,
            'totalUsers' => $totalUsers,
            'activeOrders' => $activeOrders,
            'averageOrderValue' => $averageOrderValue,
            'customerCount' => $usersByRole['ROLE_CUSTOMER'] ?? 0,
            'restaurantCount' => $usersByRole['ROLE_RESTAURANT'] ?? 0,
            'driverCount' => $usersByRole['ROLE_DRIVER'] ?? 0,
            'adminCount' => $usersByRole['ROLE_ADMIN'] ?? 0,
            'revenueTimeSeries' => $revenueTimeSeries,
            'ordersByStatus' => $ordersByStatus,
        ]);
    }
}
