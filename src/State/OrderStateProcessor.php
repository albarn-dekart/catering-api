<?php

namespace App\State;

use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProcessorInterface;
use App\Entity\Delivery;
use App\Entity\Order;
use App\Entity\OrderItem; // 💡 Import OrderItem
use App\Enum\OrderStatus;  // 💡 Import OrderStatus
use App\Entity\User;       // 💡 Import User
use DateInterval;
use DatePeriod;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\HttpFoundation\Request; // 💡 For checking PATCH method
use Symfony\Component\Security\Core\Exception\AccessDeniedException;


final readonly class OrderStateProcessor implements ProcessorInterface
{
    public function __construct(
        // 💡 Use the standard Doctrine processor for the final save
        #[Autowire(service: 'api_platform.doctrine.orm.state.persist_processor')]
        private ProcessorInterface $persistProcessor,
        private Security $security
    ) {
    }

    public function process(mixed $data, Operation $operation, array $uriVariables = [], array $context = []): mixed
    {
        if (!$data instanceof Order) {
            return $this->persistProcessor->process($data, $operation, $uriVariables, $context);
        }

        /** @var User $user */
        $user = $this->security->getUser();

        // ----------------------------------------------------
        // 1. Logic for POST (Order Creation)
        // ----------------------------------------------------
        if (Request::METHOD_POST === $context['request']->getMethod()) {
            if (!$user) {
                throw new AccessDeniedException('User must be logged in to create an order.');
            }

            $data->setCustomer($user);
            $data->setStatus(OrderStatus::Unpaid); // Set initial status

            // **Removed manual MealPlan resolution:** API Platform's denormalizer handles IRIs for nested OrderItems.

            // Ensure OrderItems are linked to the Order
            /** @var OrderItem $orderItem */
            foreach ($data->getOrderItems() as $orderItem) {
                $orderItem->setOrder($data);
            }

            // Create deliveries and calculate total (must run before persist)
            $this->createDeliveriesForOrder($data);
            $data->setTotal($this->calculateTotal($data));
        }

        // ----------------------------------------------------
        // 2. Logic for PATCH (Order Update/Status Change)
        // ----------------------------------------------------
        if (Request::METHOD_PATCH === $context['request']->getMethod()) {
            // Check for illegal status changes (e.g., customer trying to change to Paid)
            $this->validateStatusChange($data, $context['previous_data']);

            // Re-calculate total if items or delivery schedule changed
            // This is complex and might require checking if OrderItems or Delivery fields were modified.
            // For simplicity, we recalculate if any delivery-related fields were submitted.
            $submittedData = $context['request']->toArray();
            if (isset($submittedData['orderItems']) || isset($submittedData['deliveryDays'])) {
                $this->createDeliveriesForOrder($data);
                $data->setTotal($this->calculateTotal($data));
            }
        }

        // ----------------------------------------------------
        // 3. Final Persist
        // ----------------------------------------------------
        return $this->persistProcessor->process($data, $operation, $uriVariables, $context);
    }

    /**
     * Calculates the total price of the order based on items and delivery schedule.
     */
    private function calculateTotal(Order $order): int
    {
        $deliveryCount = $this->getDeliveryCount($order);
        if ($deliveryCount === 0) {
            return 0; // Prevent division by zero and ensure correct total for failed setup
        }

        $basePricePerDay = 0;
        /** @var OrderItem $orderItem */
        foreach ($order->getOrderItems() as $orderItem) {
            $mealPlan = $orderItem->getMealPlan();
            $quantity = $orderItem->getQuantity() ?? 0;

            // MealPlan::getPrice() calculates the price of all Meals in the plan.
            $pricePerUnit = $mealPlan ? $mealPlan->getPrice() : 0;

            $basePricePerDay += ($pricePerUnit * $quantity);
        }

        return $basePricePerDay * $deliveryCount;
    }

    /**
     * Helper to get the number of delivery days between start and end date.
     */
    private function getDeliveryCount(Order $order): int
    {
        $begin = $order->getDeliveryStartDate();
        $end = $order->getDeliveryEndDate();
        $deliveryDays = $order->getDeliveryDays();
        $deliveryCount = 0;

        if (!$begin || !$end || empty($deliveryDays)) {
            return 0;
        }

        $end = (clone $end)->modify('+1 day');
        $interval = new DateInterval('P1D');
        $dateRange = new DatePeriod($begin, $interval, $end);

        foreach ($dateRange as $date) {
            // date->format('D') returns 'Mon', 'Tue', etc.
            if (in_array($date->format('D'), $deliveryDays)) {
                $deliveryCount++;
            }
        }

        return $deliveryCount;
    }

    /**
     * Creates or updates Delivery entities linked to the Order based on the delivery schedule.
     */
    private function createDeliveriesForOrder(Order $order): void
    {
        $begin = $order->getDeliveryStartDate();
        $end = $order->getDeliveryEndDate();
        $deliveryDays = $order->getDeliveryDays();

        // Clear existing deliveries to ensure we don't duplicate them on updates
        foreach ($order->getDeliveries() as $delivery) {
            $order->removeDelivery($delivery);
        }

        if (!$begin || !$end || empty($deliveryDays)) {
            return;
        }

        $end = (clone $end)->modify('+1 day');
        $interval = new DateInterval('P1D');
        $dateRange = new DatePeriod($begin, $interval, $end);

        foreach ($dateRange as $date) {
            // date->format('D') returns 'Mon', 'Tue', etc.
            if (in_array($date->format('D'), $deliveryDays)) {
                $delivery = new Delivery();
                $delivery->setDeliveryDate($date);
                $order->addDelivery($delivery);
                // 💡 No need to persist Delivery here, cascade persist from Order handles it
            }
        }
    }

    /**
     * Validates if the new status transition is allowed.
     */
    private function validateStatusChange(Order $data, Order $previousData): void
    {
        $user = $this->security->getUser();
        $newStatus = $data->getStatus();
        $oldStatus = $previousData->getStatus();

        // No status change occurred
        if ($newStatus === $oldStatus) {
            return;
        }

        if (!$user) {
            throw new AccessDeniedException('Not authenticated.');
        }

        // Admin can do anything
        if ($this->security->isGranted('ROLE_ADMIN')) {
            return;
        }

        // Customer can only cancel unpaid orders or initiate payment (implicit status change to Paid via Stripe webhook)
        if ($this->security->isGranted('ROLE_CUSTOMER') && $data->getCustomer() === $user) {
            if ($oldStatus === OrderStatus::Unpaid && $newStatus === OrderStatus::Cancelled) {
                return; // Customer canceled
            }
            // Customer is NOT allowed to change status to anything else.
            if ($newStatus !== $oldStatus) {
                throw new AccessDeniedException('Customers can only cancel unpaid orders.');
            }
        }

        // Owner (Restaurant) can transition from Unpaid/Paid/Preparing
        if ($this->security->isGranted('ROLE_RESTAURANT') && $data->getRestaurant()->getOwner() === $user) {
            if (in_array($oldStatus, [OrderStatus::Unpaid, OrderStatus::Paid, OrderStatus::Preparing])) {
                if (in_array($newStatus, [OrderStatus::Preparing, OrderStatus::Cancelled, OrderStatus::ReadyForDelivery])) {
                    return;
                }
            }
        }

        // Any other change is disallowed
        throw new AccessDeniedException(sprintf(
            'Cannot transition order status from "%s" to "%s".',
            $oldStatus->value,
            $newStatus->value
        ));
    }
}