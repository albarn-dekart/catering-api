<?php

namespace App\Controller;

use App\Entity\User;
use App\Repository\DeliveryRepository;
use App\Repository\OrderRepository;
use App\Repository\RestaurantRepository;
use App\Repository\UserRepository;
use DateTime;
use Exception;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\StreamedResponse;
use Symfony\Component\HttpKernel\Attribute\AsController;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[AsController]
class ExportController extends AbstractController
{
    public function __construct(
        private readonly OrderRepository      $orderRepository,
        private readonly UserRepository       $userRepository,
        private readonly DeliveryRepository   $deliveryRepository,
        private readonly RestaurantRepository $restaurantRepository
    ) {}

    #[Route('/api/export/orders', name: 'export_orders', methods: ['POST'])]
    #[Route('/api/restaurants/{restaurantId}/export/orders', name: 'export_restaurant_orders', methods: ['POST'])]
    #[IsGranted('IS_AUTHENTICATED_FULLY')]
    public function exportOrders(Request $request, ?string $restaurantId = null): Response
    {
        $data = json_decode($request->getContent(), true) ?? [];
        /** @var User|null $user */
        $user = $this->getUser();

        // Build the query based on user role and filters
        $qb = $this->orderRepository->createQueryBuilder('o')
            ->leftJoin('o.customer', 'customer')
            ->leftJoin('o.restaurant', 'restaurant')
            ->leftJoin('o.deliveries', 'deliveries')
            ->orderBy('o.id', 'DESC');

        if ($restaurantId) {
            $restaurant = $this->restaurantRepository->find($restaurantId);
            if (!$restaurant) {
                throw $this->createNotFoundException('Restaurant not found');
            }

            if (!$this->isGranted('ROLE_ADMIN') && ($user->getOwnedRestaurant()?->getId() !== (int)$restaurantId)) {
                throw $this->createAccessDeniedException('You do not have permission to export orders for this restaurant.');
            }

            $qb->andWhere('o.restaurant = :restaurantId')
                ->setParameter('restaurantId', $restaurant);
        } else {
            // Customers see only their own orders
            $qb->andWhere('o.customer = :customer')
                ->setParameter('customer', $user);
        }

        // Apply filters from request
        if (!empty($data['status'])) {
            $qb->andWhere('o.status = :status')
                ->setParameter('status', $data['status']);
        }

        $orders = $qb->getQuery()->getResult();

        // Create CSV response
        $response = new StreamedResponse(function () use ($orders) {
            $handle = fopen('php://output', 'w');

            // CSV Header
            fputcsv($handle, [
                'Order ID',
                'Order Date',
                'Customer Name',
                'Customer Email',
                'Phone Number',
                'Restaurant Name',
                'Status',
                'Total (PLN)',
                'Payment ID',
                'Delivery Start Date',
                'Delivery End Date',
                'Delivery Days',
                'Delivery Address',
                'Order Items',
            ]);

            // CSV Data
            foreach ($orders as $order) {
                $deliveryDays = implode(', ', $order->getDeliveryDays());
                $deliveryAddress = implode(', ', array_filter([
                    $order->getDeliveryStreet(),
                    $order->getDeliveryApartment(),
                    $order->getDeliveryCity(),
                    $order->getDeliveryZipCode(),
                ]));

                $orderItemsDetails = [];
                foreach ($order->getOrderItems() as $orderItem) {
                    $mealPlanName = $orderItem->getMealPlan() ? $orderItem->getMealPlan()->getName() : 'Unknown';
                    $orderItemsDetails[] = sprintf('%s x%d', $mealPlanName, $orderItem->getQuantity());
                }
                $orderDetailsString = implode(' | ', $orderItemsDetails);

                fputcsv($handle, [
                    $order->getId(),
                    $order->getCreatedAt()?->format('Y-m-d H:i:s') ?? 'N/A',
                    trim(($order->getDeliveryFirstName() ?? '') . ' ' . ($order->getDeliveryLastName() ?? '')) ?: 'N/A',
                    $order->getCustomer()?->getEmail() ?? 'N/A',
                    $order->getDeliveryPhoneNumber() ?? 'N/A',
                    $order->getRestaurant()?->getName() ?? 'N/A',
                    $order->getStatus()->value,
                    $order->getTotal(),
                    $order->getPaymentIntentId() ?? 'N/A',
                    $order->getDeliveryStartDate()?->format('Y-m-d') ?? 'N/A',
                    $order->getDeliveryEndDate()?->format('Y-m-d') ?? 'N/A',
                    $deliveryDays ?: 'N/A',
                    $deliveryAddress ?: 'N/A',
                    $orderDetailsString,
                ]);
            }

            fclose($handle);
        });

        $filename = 'orders_' . date('Y-m-d_His') . '.csv';
        $response->headers->set('Content-Type', 'text/csv');
        $response->headers->set('Content-Disposition', "attachment; filename=\"$filename\"");

        return $response;
    }

    #[Route('/api/export/users', name: 'export_users', methods: ['POST'])]
    #[IsGranted('ROLE_ADMIN')]
    public function exportUsers(Request $request): Response
    {
        $data = json_decode($request->getContent(), true) ?? [];

        // Get all users
        $users = $this->userRepository->findAll();

        // Apply role filter if provided
        if (!empty($data['role'])) {
            $users = array_filter($users, function ($user) use ($data) {
                return in_array($data['role'], $user->getRoles());
            });
        }

        // Create CSV response
        $response = new StreamedResponse(function () use ($users) {
            $handle = fopen('php://output', 'w');

            // CSV Header
            fputcsv($handle, [
                'User ID',
                'Email',
                'Roles',
                'Restaurant',
            ]);

            // CSV Data
            foreach ($users as $user) {
                $roles = implode(', ', $user->getRoles());
                $restaurant = $user->getOwnedRestaurant()?->getName() ?? 'N/A';

                fputcsv($handle, [
                    $user->getId(),
                    $user->getEmail(),
                    $roles,
                    $restaurant,
                ]);
            }

            fclose($handle);
        });

        $filename = 'users_' . date('Y-m-d_His') . '.csv';
        $response->headers->set('Content-Type', 'text/csv');
        $response->headers->set('Content-Disposition', "attachment; filename=\"$filename\"");

        return $response;
    }

    #[Route('/api/export/deliveries', name: 'export_deliveries', methods: ['POST'])]
    #[IsGranted('ROLE_RESTAURANT')]
    public function exportDeliveries(Request $request): Response
    {
        $data = json_decode($request->getContent(), true) ?? [];
        /** @var User|null $user */
        $user = $this->getUser();

        // Build the query based on user role and filters
        $qb = $this->deliveryRepository->createQueryBuilder('d')
            ->leftJoin('d.order', 'o')
            ->leftJoin('o.restaurant', 'restaurant')
            ->leftJoin('d.courier', 'courier')
            ->orderBy('d.deliveryDate', 'DESC');

        if ($this->isGranted('ROLE_RESTAURANT')) {
            // Restaurant owners see only their restaurant's deliveries
            $qb->andWhere('o.restaurant = :restaurant')
                ->setParameter('restaurant', $user->getOwnedRestaurant());
        }

        // Apply filters from request
        if (!empty($data['status'])) {
            $qb->andWhere('d.status = :status')
                ->setParameter('status', $data['status']);
        }

        if (!empty($data['startDate'])) {
            $qb->andWhere('d.deliveryDate >= :startDate')
                ->setParameter('startDate', new DateTime($data['startDate']));
        }

        if (!empty($data['endDate'])) {
            $qb->andWhere('d.deliveryDate <= :endDate')
                ->setParameter('endDate', new DateTime($data['endDate']));
        }

        $deliveries = $qb->getQuery()->getResult();

        // Create CSV response
        $response = new StreamedResponse(function () use ($deliveries) {
            $handle = fopen('php://output', 'w');

            // CSV Header
            fputcsv($handle, [
                'Delivery ID',
                'Order ID',
                'Restaurant',
                'Delivery Date',
                'Status',
                'Courier Email',
                'Delivery Address',
            ]);

            // CSV Data
            foreach ($deliveries as $delivery) {
                $order = $delivery->getOrder();
                $deliveryAddress = $order ? implode(', ', array_filter([
                    $order->getDeliveryStreet(),
                    $order->getDeliveryApartment(),
                    $order->getDeliveryCity(),
                    $order->getDeliveryZipCode(),
                ])) : 'N/A';

                fputcsv($handle, [
                    $delivery->getId(),
                    $delivery->getOrder()?->getId() ?? 'N/A',
                    $delivery->getOrder()?->getRestaurant()?->getName() ?? 'N/A',
                    $delivery->getDeliveryDate()->format('Y-m-d'),
                    $delivery->getStatus()->value,
                    $delivery->getCourier()?->getEmail() ?? 'Unassigned',
                    $deliveryAddress,
                ]);
            }

            fclose($handle);
        });

        $filename = 'deliveries_' . date('Y-m-d_His') . '.csv';
        $response->headers->set('Content-Type', 'text/csv');
        $response->headers->set('Content-Disposition', "attachment; filename=\"$filename\"");

        return $response;
    }

    /**
     * @throws Exception
     */
    #[Route('/api/export/statistics', name: 'export_statistics', methods: ['POST'])]
    #[IsGranted('ROLE_ADMIN')]
    public function exportStatistics(Request $request): Response
    {
        $data = json_decode($request->getContent(), true) ?? [];
        if (!empty($data['startDate'])) {
            $startDate = new DateTime($data['startDate']);
            $startDate->setTime(0, 0, 0);
        } else {
            $startDate = (new DateTime('today'))->modify('-30 days');
        }

        if (!empty($data['endDate'])) {
            $endDate = new DateTime($data['endDate']);
            $endDate->setTime(23, 59, 59);
        } else {
            $endDate = new DateTime('now');
        }

        // Get statistics using repository methods
        $totalRevenue = $this->orderRepository->getTotalRevenue($startDate, $endDate);
        $orderCountsByStatus = $this->orderRepository->getOrderCountByStatus($startDate, $endDate);
        $totalOrders = array_sum($orderCountsByStatus);
        $averageOrderValue = $this->orderRepository->getAverageOrderValue($startDate, $endDate);
        // User stats usually don't filter by date range of stats view
        $usersByRole = $this->userRepository->getTotalUsersByRole();
        $totalUsers = array_sum($usersByRole);

        // Create CSV response
        $response = new StreamedResponse(function () use (
            $totalRevenue,
            $totalOrders,
            $averageOrderValue,
            $totalUsers,
            $orderCountsByStatus,
            $usersByRole,
            $startDate,
            $endDate
        ) {
            $handle = fopen('php://output', 'w');

            // CSV Header
            fputcsv($handle, ['Report Period', $startDate && $endDate ? $startDate->format('Y-m-d') . ' to ' . $endDate->format('Y-m-d') : 'All Time']);
            fputcsv($handle, []);
            fputcsv($handle, ['Metric', 'Value']);

            // Overall Statistics
            fputcsv($handle, ['Total Revenue (PLN)', number_format($totalRevenue, 2, '.', '')]);
            fputcsv($handle, ['Total Orders', $totalOrders]);
            fputcsv($handle, ['Average Order Value (PLN)', number_format($averageOrderValue, 2, '.', '')]);
            fputcsv($handle, ['Total Users', $totalUsers]);

            // Empty row for separation
            fputcsv($handle, []);

            // Orders by Status
            fputcsv($handle, ['Orders by Status', '']);
            foreach ($orderCountsByStatus as $status => $count) {
                fputcsv($handle, [$status, $count]);
            }

            // Empty row for separation
            fputcsv($handle, []);

            // Users by Role
            fputcsv($handle, ['Users by Role', '']);
            foreach ($usersByRole as $role => $count) {
                fputcsv($handle, [$role, $count]);
            }

            fclose($handle);
        });

        $filename = 'statistics_' . date('Y-m-d_His') . '.csv';
        $response->headers->set('Content-Type', 'text/csv');
        $response->headers->set('Content-Disposition', "attachment; filename=\"$filename\"");

        return $response;
    }

    /**
     * @throws Exception
     */
    #[Route('/api/export/restaurants/{restaurantId}/statistics', name: 'export_restaurant_statistics', methods: ['POST'])]
    #[IsGranted('IS_AUTHENTICATED_FULLY')]
    public function exportRestaurantStatistics(string $restaurantId, Request $request): Response
    {
        return $this->processRestaurantExport($restaurantId, $request);
    }

    /**
     * @throws Exception
     */
    private function processRestaurantExport(string $restaurantId, Request $request): Response
    {
        $restaurant = $this->restaurantRepository->find($restaurantId);

        if (!$restaurant) {
            throw $this->createNotFoundException('Restaurant not found');
        }

        /** @var User|null $user */
        $user = $this->getUser();
        if (!$this->isGranted('ROLE_ADMIN') && ($user->getOwnedRestaurant()?->getId() !== (int)$restaurantId)) {
            throw $this->createAccessDeniedException('You do not have permission to export statistics for this restaurant.');
        }

        $data = json_decode($request->getContent(), true) ?? [];
        if (!empty($data['startDate'])) {
            $startDate = new DateTime($data['startDate']);
            $startDate->setTime(0, 0, 0);
        } else {
            $startDate = (new DateTime('today'))->modify('-30 days');
        }

        if (!empty($data['endDate'])) {
            $endDate = new DateTime($data['endDate']);
            $endDate->setTime(23, 59, 59);
        } else {
            $endDate = new DateTime('now');
        }

        // Get restaurant-specific statistics
        $totalRevenue = $this->orderRepository->getTotalRevenueByRestaurant($restaurant, $startDate, $endDate);
        $totalOrders = $this->orderRepository->getOrderCountByRestaurant($restaurant, $startDate, $endDate);
        $activeOrders = $this->orderRepository->getActiveOrdersByRestaurant($restaurant, $startDate, $endDate);
        $completedOrders = $totalOrders - $activeOrders; // Conceptual approximation

        // Get delivery statistics
        $deliveriesByStatus = $this->deliveryRepository->getDeliveriesByStatus($restaurant, $startDate, $endDate);
        $totalDeliveries = array_sum($deliveriesByStatus);
        $deliverySuccessRate = $this->deliveryRepository->getDeliverySuccessRate($restaurant, $startDate, $endDate);

        $averageOrderValue = $totalOrders > 0 ? $totalRevenue / $totalOrders : 0;

        // Create CSV response
        $response = new StreamedResponse(function () use (
            $restaurant,
            $totalRevenue,
            $totalOrders,
            $averageOrderValue,
            $activeOrders,
            $completedOrders,
            $totalDeliveries,
            $deliverySuccessRate,
            $startDate,
            $endDate
        ) {
            $handle = fopen('php://output', 'w');

            // CSV Header
            fputcsv($handle, ['Restaurant Statistics Report']);
            fputcsv($handle, ['Restaurant', $restaurant->getName()]);
            fputcsv($handle, ['Report Period', $startDate && $endDate ? $startDate->format('Y-m-d') . ' to ' . $endDate->format('Y-m-d') : 'All Time']);
            fputcsv($handle, []);
            fputcsv($handle, ['Metric', 'Value']);

            // Statistics
            fputcsv($handle, ['Total Revenue (PLN)', number_format($totalRevenue, 2, '.', '')]);
            fputcsv($handle, ['Total Orders', $totalOrders]);
            fputcsv($handle, ['Average Order Value (PLN)', number_format($averageOrderValue, 2, '.', '')]);
            fputcsv($handle, ['Active Orders', $activeOrders]);
            fputcsv($handle, ['Completed Orders', $completedOrders]);
            fputcsv($handle, ['Total Deliveries', $totalDeliveries]);
            fputcsv($handle, ['Delivery Success Rate (%)', number_format($deliverySuccessRate, 1, '.', '')]);

            fclose($handle);
        });

        $filename = 'restaurant_statistics_' . $restaurantId . '_' . date('Y-m-d_His') . '.csv';
        $response->headers->set('Content-Type', 'text/csv');
        $response->headers->set('Content-Disposition', "attachment; filename=\"$filename\"");

        return $response;
    }

    /**
     * @throws Exception
     */
    #[Route('/api/export/restaurants/{restaurantId}/production-plan', name: 'export_production_plan', methods: ['POST'])]
    #[IsGranted('IS_AUTHENTICATED_FULLY')]
    public function exportProductionPlan(string $restaurantId, Request $request): Response
    {
        $restaurant = $this->restaurantRepository->find($restaurantId);

        if (!$restaurant) {
            throw $this->createNotFoundException('Restaurant not found');
        }

        /** @var User|null $user */
        $user = $this->getUser();
        if (!$this->isGranted('ROLE_ADMIN') && ($user->getOwnedRestaurant()?->getId() !== (int)$restaurantId)) {
            throw $this->createAccessDeniedException('You do not have permission to export production plan for this restaurant.');
        }

        $data = json_decode($request->getContent(), true) ?? [];
        $dateStr = $data['date'] ?? 'today';
        $date = new DateTime($dateStr);

        $productionPlan = $this->deliveryRepository->getProductionPlan($restaurant, $date);

        // Create CSV response
        $response = new StreamedResponse(function () use ($restaurant, $productionPlan, $date) {
            $handle = fopen('php://output', 'w');

            // CSV Header
            fputcsv($handle, ['Kitchen Production Plan']);
            fputcsv($handle, ['Restaurant', $restaurant->getName()]);
            fputcsv($handle, ['Production Date', $date->format('Y-m-d')]);
            fputcsv($handle, []);
            fputcsv($handle, ['Meal Name', 'Quantity to Cook']);

            // Data
            foreach ($productionPlan as $item) {
                fputcsv($handle, [
                    $item['mealName'],
                    $item['count']
                ]);
            }

            // Summary
            fputcsv($handle, []);
            fputcsv($handle, ['Total Meals', array_sum(array_column($productionPlan, 'count'))]);

            fclose($handle);
        });

        $filename = 'production_plan_' . $restaurantId . '_' . $date->format('Y-m-d') . '.csv';
        $response->headers->set('Content-Type', 'text/csv');
        $response->headers->set('Content-Disposition', "attachment; filename=\"$filename\"");

        return $response;
    }
}
