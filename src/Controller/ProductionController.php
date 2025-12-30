<?php

namespace App\Controller;

use App\Entity\User;
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
class ProductionController extends AbstractController
{
    public function __construct(
        private readonly RestaurantRepository $restaurantRepository,
        private readonly DeliveryRepository $deliveryRepository,
    ) {}

    /**
     * @throws Exception
     */
    #[Route('/api/restaurants/{restaurantId}/production-plan', name: 'get_production_plan', methods: ['GET'])]
    #[IsGranted('IS_AUTHENTICATED_FULLY')]
    public function getProductionPlan(string $restaurantId, Request $request): JsonResponse
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

        $dateStr = $request->query->get('date');
        $date = $dateStr ? new DateTime($dateStr) : new DateTime('today'); // Default to today to show current delivery needs

        $productionPlan = $this->deliveryRepository->getProductionPlan($restaurant, $date);

        return $this->json([
            'date' => $date->format('Y-m-d'),
            'items' => $productionPlan,
        ]);
    }
}
