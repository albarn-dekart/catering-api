<?php

namespace App\Controller;

use App\Entity\User;
use App\Repository\DeliveryRepository;
use App\Repository\OrderRepository;
use App\Repository\UserRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\StreamedResponse;
use Symfony\Component\HttpKernel\Attribute\AsController;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[AsController]
class ExportController extends AbstractController
{
    public function __construct(
        private readonly OrderRepository $orderRepository,
        private readonly UserRepository $userRepository,
        private readonly DeliveryRepository $deliveryRepository
    ) {}

    #[Route('/api/export/orders', name: 'export_orders', methods: ['POST'])]
    #[IsGranted('ROLE_CUSTOMER')]
    public function exportOrders(Request $request): Response
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

        if ($this->isGranted('ROLE_RESTAURANT')) {
            // Restaurant owners see only their restaurant's orders
            $qb->andWhere('o.restaurant = :restaurant')
                ->setParameter('restaurant', $user->getRestaurant());
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

        if (!empty($data['restaurantId'])) {
            $qb->andWhere('o.restaurant = :restaurantId')
                ->setParameter('restaurantId', $data['restaurantId']);
        }

        $orders = $qb->getQuery()->getResult();

        // Create CSV response
        $response = new StreamedResponse(function () use ($orders) {
            $handle = fopen('php://output', 'w');

            // CSV Header
            fputcsv($handle, [
                'Order ID',
                'Customer Email',
                'Restaurant Name',
                'Status',
                'Total (PLN)',
                'Delivery Start Date',
                'Delivery End Date',
                'Delivery Days',
                'Delivery Address',
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

                fputcsv($handle, [
                    $order->getId(),
                    $order->getCustomer()?->getEmail() ?? 'N/A',
                    $order->getRestaurant()?->getName() ?? 'N/A',
                    $order->getStatus()->value,
                    number_format($order->getTotal() / 100, 2, '.', ''),
                    $order->getDeliveryStartDate()?->format('Y-m-d') ?? 'N/A',
                    $order->getDeliveryEndDate()?->format('Y-m-d') ?? 'N/A',
                    $deliveryDays ?: 'N/A',
                    $deliveryAddress ?: 'N/A',
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
                $restaurant = $user->getRestaurant()?->getName() ?? 'N/A';

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
            ->leftJoin('d.order', 'order')
            ->leftJoin('d.restaurant', 'restaurant')
            ->leftJoin('d.driver', 'driver')
            ->orderBy('d.deliveryDate', 'DESC');

        if ($this->isGranted('ROLE_RESTAURANT')) {
            // Restaurant owners see only their restaurant's deliveries
            $qb->andWhere('d.restaurant = :restaurant')
                ->setParameter('restaurant', $user->getRestaurant());
        }

        // Apply filters from request
        if (!empty($data['status'])) {
            $qb->andWhere('d.status = :status')
                ->setParameter('status', $data['status']);
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
                'Driver Email',
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
                    $delivery->getRestaurant()?->getName() ?? 'N/A',
                    $delivery->getDeliveryDate()->format('Y-m-d'),
                    $delivery->getStatus()->value,
                    $delivery->getDriver()?->getEmail() ?? 'Unassigned',
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

    #[Route('/api/export/statistics', name: 'export_statistics', methods: ['POST'])]
    #[IsGranted('ROLE_ADMIN')]
    public function exportStatistics(): Response
    {
        // Get statistics using repository methods
        $totalRevenue = $this->orderRepository->getTotalRevenue();
        $orderCountsByStatus = $this->orderRepository->getOrderCountByStatus();
        $totalOrders = array_sum($orderCountsByStatus);
        $averageOrderValue = $this->orderRepository->getAverageOrderValue();
        $usersByRole = $this->userRepository->getTotalUsersByRole();
        $totalUsers = array_sum($usersByRole);

        // Create CSV response
        $response = new StreamedResponse(function () use (
            $totalRevenue,
            $totalOrders,
            $averageOrderValue,
            $totalUsers,
            $orderCountsByStatus,
            $usersByRole
        ) {
            $handle = fopen('php://output', 'w');

            // CSV Header
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
}
