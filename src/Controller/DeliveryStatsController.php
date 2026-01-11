<?php

namespace App\Controller;

use App\Entity\User;
use App\Enum\DeliveryStatus;
use App\Repository\DeliveryRepository;
use App\Repository\RestaurantRepository;
use DateTime;
use Exception;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Attribute\AsController;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[AsController]
class DeliveryStatsController extends AbstractController
{
    public function __construct(
        private readonly DeliveryRepository $deliveryRepository,
        private readonly RestaurantRepository $restaurantRepository
    ) {}

    #[Route('/api/courier/delivery-stats', name: 'courier_delivery_stats', methods: ['GET'])]
    #[IsGranted('ROLE_COURIER')]
    public function getCourierDeliveryStats(Request $request): JsonResponse
    {
        /** @var User|null $courier */
        $courier = $this->getUser();
        $startDateStr = $request->query->get('startDate');
        $endDateStr = $request->query->get('endDate');
        $statusFilter = $request->query->get('status');

        try {
            $startDate = null;
            $endDate = null;

            // Parse dates if provided, otherwise leave as null for "all time"
            if ($startDateStr && $endDateStr) {
                $startDate = new DateTime($startDateStr);
                $endDate = new DateTime($endDateStr);
                $startDate->setTime(0, 0);
                $endDate->setTime(23, 59, 59);
            }

            // Parse status filter if provided
            $status = null;
            if ($statusFilter) {
                $status = DeliveryStatus::tryFrom($statusFilter);
                if (!$status) {
                    return $this->json(['error' => 'Invalid status'], 400);
                }
            }

            $stats = $this->deliveryRepository->getCourierDeliveryStats(
                $courier,
                $startDate,
                $endDate,
                $status
            );

            return $this->json($stats);
        } catch (Exception) {
            return $this->json(['error' => 'Invalid date format or processing error'], 400);
        }
    }

    #[Route('/api/restaurants/{restaurantId}/delivery-stats', name: 'restaurant_delivery_stats', methods: ['GET'])]
    #[IsGranted('IS_AUTHENTICATED_FULLY')]
    public function getRestaurantDeliveryStats(string $restaurantId, Request $request): JsonResponse
    {
        $restaurant = $this->restaurantRepository->find($restaurantId);

        if (!$restaurant) {
            throw new NotFoundHttpException('Restaurant not found');
        }

        /** @var User|null $user */
        $user = $this->getUser();

        if (!$this->isGranted('ROLE_ADMIN')) {
            if (!$this->isGranted('ROLE_RESTAURANT') || $user->getOwnedRestaurant()?->getId() !== (int)$restaurantId) {
                throw new AccessDeniedHttpException('You do not have permission to view this production plan');
            }
        }

        $startDateStr = $request->query->get('startDate');
        $endDateStr = $request->query->get('endDate');
        $statusFilter = $request->query->get('status');

        try {
            $startDate = null;
            $endDate = null;

            // Parse dates if provided, otherwise leave as null for "all time"
            if ($startDateStr && $endDateStr) {
                $startDate = new DateTime($startDateStr);
                $endDate = new DateTime($endDateStr);
                $startDate->setTime(0, 0);
                $endDate->setTime(23, 59, 59);
            }

            // Parse status filter if provided
            $status = null;
            if ($statusFilter) {
                $status = DeliveryStatus::tryFrom($statusFilter);
                if (!$status) {
                    return $this->json(['error' => 'Invalid status'], 400);
                }
            }

            $stats = $this->deliveryRepository->getRestaurantDeliveryStats(
                $restaurant,
                $startDate,
                $endDate,
                $status
            );

            return $this->json($stats);
        } catch (Exception) {
            return $this->json(['error' => 'Invalid date format or processing error'], 400);
        }
    }
}