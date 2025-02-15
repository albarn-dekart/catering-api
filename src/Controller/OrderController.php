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
use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Component\Validator\Validator\ValidatorInterface;

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


    #[Route('/api/order', name: 'api_create_order', methods: ['POST'])]
    public function newOrder(
        Request                $request,
        EntityManagerInterface $entityManager,
        MealRepository         $mealRepository,
        ValidatorInterface     $validator
    ): JsonResponse
    {
        $user = $this->getUser();

        $data = json_decode($request->getContent(), true);

        // Input validation constraints
        $constraints = new Assert\Collection([
            'start_date' => [
                new Assert\NotBlank(['message' => 'Start date is required.']),
                new Assert\DateTime(['format' => 'Y-m-d', 'message' => 'Start date must be a valid date in the format Y-m-d.']),
            ],
            'end_date' => [
                new Assert\NotBlank(['message' => 'End date is required.']),
                new Assert\DateTime(['format' => 'Y-m-d', 'message' => 'End date must be a valid date in the format Y-m-d.']),
                new Assert\GreaterThan([
                    'propertyPath' => 'start_date',
                    'message' => 'End date must be after the start date.',
                ]),
            ],
            'delivery_days' => [
                new Assert\NotBlank(['message' => 'Delivery days are required.']),
                new Assert\Type(['type' => 'array', 'message' => 'Delivery days must be an array.']),
                new Assert\All([
                    new Assert\Type(['type' => 'integer', 'message' => 'Each delivery day must be an integer.']),
                    new Assert\Range([
                        'min' => 1,
                        'max' => 7,
                        'notInRangeMessage' => 'Each delivery day must be in range from 1 (Monday) to 7 (Sunday).'
                    ]),
                ]),
            ],
            'meals' => [
                new Assert\NotBlank(['message' => 'Meals are required.']),
                new Assert\Type(['type' => 'array', 'message' => 'Meals must be an array.']),
                new Assert\All([
                    new Assert\Type(['type' => 'numeric', 'message' => 'Meal ID must be a number.']),
                    new Assert\PositiveOrZero(['message' => 'Meal ID must not be a negative number.']),
                ]),
            ],
        ]);

        // Validate input
        $violations = $validator->validate($data, $constraints);

        if (count($violations) > 0) {
            $errors = [];
            foreach ($violations as $violation) {
                $errors[] = $violation->getMessage();
            }
            return new JsonResponse(['error' => $errors], Response::HTTP_BAD_REQUEST);
        }

        try {
            $order = new Order();
            $order->setStartDate(new DateTime($data['start_date']));
            $order->setEndDate(new DateTime($data['end_date']));
            $order->setDeliveryDays($data['delivery_days']);
            $order->setMadeBy($user);

            // Add meals to the order
            foreach ($data['meals'] as $mealId) {
                $mealEntity = $mealRepository->find($mealId);
                if (!$mealEntity) {
                    return new JsonResponse(['error' => "Meal with ID $mealId not found"], Response::HTTP_NOT_FOUND);
                }
                $order->addMeal($mealEntity);
            }

            $entityManager->persist($order);
            $entityManager->flush();

            return new JsonResponse(null, Response::HTTP_CREATED);
        } catch (Exception $e) {
            return new JsonResponse(['error' => 'Failed to create order: ' . $e->getMessage()], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }
}
