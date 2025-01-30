<?php

namespace App\Controller;

use App\Enum\OrderStatus;
use DateTime;
use Exception;
use App\Entity\Order;
use App\Repository\MealRepository;
use App\Repository\OrderRepository;
use App\Repository\UserRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class OrderController extends AbstractController
{
    #[Route('/api/user/orders', name: 'api_get_orders', methods: ['GET'])]
    public function getOrders(UserRepository $repository): JsonResponse
    {
        return $this->getUserOrders($repository);
    }

    #[Route('/api/admin/user/{id}/orders', name: 'api_user_orders', methods: ['GET'])]
    public function getOrdersByUserId(UserRepository $repository, int $id): JsonResponse
    {
        return $this->getUserOrders($repository, $id);
    }

    function getUserOrders(UserRepository $repository, int $id = null): JsonResponse
    {
        $user = $id ? $repository->find($id) : $this->getUser();

        $data = [];

        foreach ($user->getOrders() as $order) {
            $meals = [];

            foreach ($order->getMeals() as $meal) {
                $meals[] = $meal->data();
            }

            $data[] = [
                'id' => $order->getId(),
                'status' => $order->getStatus()->name,
                'start-date' => $order->getStartDate()->format('Y-m-d'),
                'end-date' => $order->getStartDate()->format('Y-m-d'),
                'delivery-days' => $order->getDeliveryDays(),
                'meals' => $meals
            ];
        }

        return new JsonResponse($data, Response::HTTP_OK);
    }

    #[Route('/api/admin/order/{id}/update_status', name: 'api_update_order_status', methods: ['PATCH'])]
    public function updateOrderStatus(
        Request                $request,
        OrderRepository        $repository,
        EntityManagerInterface $entityManager,
        int                    $id
    ): JsonResponse
    {
        $order = $repository->find($id);
        if (!$order) return new JsonResponse(null, Response::HTTP_NOT_FOUND);

        $data = json_decode($request->getContent(), true);

        $order->setStatus(OrderStatus::from($data['status']));
        $entityManager->flush();

        return new JsonResponse(null, Response::HTTP_NO_CONTENT);
    }

    #[Route('/api/admin/order/{id}/delete', name: 'api_delete_order', methods: ['DELETE'])]
    public function deleteOrder(OrderRepository $repository, int $id, EntityManagerInterface $entityManager): JsonResponse
    {
        $order = $repository->find($id);
        if (!$order) return new JsonResponse(null, Response::HTTP_NOT_FOUND);

        $entityManager->remove($order);
        $entityManager->flush();

        return new JsonResponse(null, Response::HTTP_NO_CONTENT);
    }

    #[Route('/api/order/new', name: 'api_create_order', methods: ['POST'])]
    public function newOrder(
        Request                $request,
        EntityManagerInterface $entityManager,
        MealRepository         $mealRepository,
    ): JsonResponse
    {
        $user = $this->getUser();

        $data = json_decode($request->getContent(), true);
        if (!$data || !isset($data['start_date'], $data['end_date'], $data['delivery_days'], $data['meals'])) {
            return new JsonResponse(['message' => 'Failed to create order. Empty input data'], Response::HTTP_BAD_REQUEST);
        }

        try {
            $order = new Order();
            $order->setStartDate(new DateTime($data['start_date']));
            $order->setEndDate(new DateTime($data['end_date']));
            $order->setDeliveryDays(($data['delivery_days']));
            $order->setMadeBy($user);

            foreach ($data['meals'] as $meal) {
                $order->addMeal($mealRepository->find($meal['id']));
            }

            $entityManager->persist($order);
            $entityManager->flush();

            return new JsonResponse(null, Response::HTTP_CREATED);

        } catch (Exception $e) {
            return new JsonResponse(['message' => 'Failed to create order: ' . $e->getMessage()], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }
}
