<?php

namespace App\Controller;

use App\Enum\OrderStatus;
use App\Repository\OrderRepository;
use App\Repository\UserRepository;
use DateTime;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
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

    /**
     * @throws \Exception
     */
    #[Route('/api/statistics', name: 'get_statistics', methods: ['GET'])]
    #[IsGranted('ROLE_ADMIN')]
    public function getStatistics(Request $request): JsonResponse
    {
        $startDateStr = $request->query->get('startDate');
        $endDateStr = $request->query->get('endDate');

        if ($startDateStr) {
            $startDate = new DateTime($startDateStr);
            $startDate->setTime(0, 0, 0);
        } else {
            $startDate = (new DateTime('today'))->modify('-30 days');
        }

        if ($endDateStr) {
            $endDate = new DateTime($endDateStr);
            $endDate->setTime(23, 59, 59);
        } else {
            $endDate = new DateTime('now');
        }

        // Get total revenue
        $totalRevenue = $this->orderRepository->getTotalRevenue($startDate, $endDate);

        // Get total orders count
        $orderCountsByStatus = $this->orderRepository->getOrderCountByStatus($startDate, $endDate);
        $totalOrders = array_sum($orderCountsByStatus);

        // Get active orders (Paid + Active status)
        $activeOrders = ($orderCountsByStatus[OrderStatus::Paid->value] ?? 0) +
            ($orderCountsByStatus[OrderStatus::Active->value] ?? 0);

        // Get average order value
        $averageOrderValue = $this->orderRepository->getAverageOrderValue($startDate, $endDate);

        // Get total users (User filtering by date not requested and might not be relevant for general stats like "total users registered EVER", but usually dashboard stats respect the date range. But UserRepository doesn't accept date range here. I will leave it as is for now unless I want to implement getUsersRegisteredInPeriod.)
        // The requirement is "modify ... statistics tabs ... to allow user to specify start and end date". Usually this applies to transactional data (orders, revenue).
        $usersByRole = $this->userRepository->getTotalUsersByRole();
        $totalUsers = array_sum($usersByRole);

        // Get revenue time series for charts (last 30 days or filtered range)
        // If date range is provided, use it. If not, default to 30 days inside repository is handled if null passed, but wait.
        // My repo method sig: getRevenueTimeSeries(int $days = 30, ?Restaurant $restaurant = null, ... dates)
        // I should stick to repo logic. If dates are passed, they override $days.
        // Get daily orders time series for charts
        $dailyOrdersTimeSeries = $this->orderRepository->getDailyOrdersTimeSeries(30, null, $startDate, $endDate);

        $revenueTimeSeries = $this->orderRepository->getRevenueTimeSeries(30, null, $startDate, $endDate);

        // Get orders by status for pie chart (Same as $orderCountsByStatus but maybe variable naming)
        $ordersByStatus = $orderCountsByStatus;

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
            'dailyOrdersTimeSeries' => $dailyOrdersTimeSeries,
            'ordersByStatus' => $ordersByStatus,
        ]);
    }
}
