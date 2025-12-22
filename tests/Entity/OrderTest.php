<?php

namespace Entity;

use App\Entity\Delivery;
use App\Entity\MealPlan;
use App\Entity\Order;
use App\Entity\OrderItem;
use App\Entity\Restaurant;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Validator\Context\ExecutionContextInterface;
use Symfony\Component\Validator\Violation\ConstraintViolationBuilderInterface;

class OrderTest extends TestCase
{
    public function testCalculateTotal()
    {
        // 1. Create Order
        $order = new Order();

        // 2. Create MealPlan with price 2000 (20.00 PLN)
        $mealPlan = new MealPlan();
        $mealPlan->setPrice(2000);

        // 3. Create OrderItem with quantity 2
        $orderItem = new OrderItem();
        $orderItem->setMealPlan($mealPlan);
        $orderItem->setQuantity(2);

        // 4. Add item to order
        $order->addOrderItem($orderItem);

        // 5. Calculate total (should be 2 * 2000 = 4000)
        // Note: calculateTotal is usually called by Lifecycle Callbacks (PrePersist/PreUpdate)
        // or manually before saving. Here we call it manually.
        $order->calculateTotal();

        $this->assertEquals(4000, $order->getTotal(), 'Total should be 4000 (2 items * 20.00)');

        // 6. Test with Deliveries (e.g. 5 days)
        for ($i = 0; $i < 5; $i++) {
            $delivery = new Delivery();
            $order->addDelivery($delivery);
        }

        $order->calculateTotal();

        // Total should be 4000 * 5 = 20000
        // Total should be 4000 * 5 = 20000
        // Total should be 4000 * 5 = 20000
        $this->assertEquals(20000, $order->getTotal(), 'Total should be 20000 (4000 * 5 deliveries)');
    }

    public function testCalculateTotalWithDeliveryFee()
    {
        // 1. Setup Restaurant with Delivery Price 15.00 PLN (1500)
        $restaurant = new Restaurant();
        $restaurant->setDeliveryPrice(1500);

        // 2. Setup Order linked to Restaurant
        $order = new Order();
        $order->setRestaurant($restaurant);

        // 3. Setup MealPlan (Price 50.00 PLN)
        $mealPlan = new MealPlan();
        $mealPlan->setPrice(5000); // 5000 gr

        // 4. Add 1 Item
        $item = new OrderItem();
        $item->setMealPlan($mealPlan);
        $item->setQuantity(1);
        $order->addOrderItem($item);

        // 5. Add 2 Deliveries
        for ($i = 0; $i < 2; $i++) {
            $delivery = new Delivery();
            $order->addDelivery($delivery);
        }

        // 6. Calculate
        // Expected: (ItemPrice * Days) + (DeliveryFee * Days)
        // (5000 * 2) + (1500 * 2) = 10000 + 3000 = 13000
        $order->calculateTotal();

        $this->assertEquals(13000, $order->getTotal(), 'Total should include delivery fees');
    }

    public function testRestaurantConsistencyValidation()
    {
        $order = new Order();

        // Mock Restaurant 1
        $restaurant1 = $this->createMock(Restaurant::class);

        // Mock Restaurant 2
        $restaurant2 = $this->createMock(Restaurant::class);

        // MealPlan from Rest 1
        $mealPlan1 = new MealPlan();
        $mealPlan1->setRestaurant($restaurant1);

        // MealPlan from Rest 2
        $mealPlan2 = new MealPlan();
        $mealPlan2->setRestaurant($restaurant2);

        // Item 1
        $item1 = new OrderItem();
        $item1->setMealPlan($mealPlan1);
        $order->addOrderItem($item1);

        // Item 2
        $item2 = new OrderItem();
        $item2->setMealPlan($mealPlan2);
        $order->addOrderItem($item2);

        // Mock ExecutionContext
        $context = $this->createMock(ExecutionContextInterface::class);

        // Expect violation builder to be called
        $violationBuilder = $this->createMock(ConstraintViolationBuilderInterface::class);
        $violationBuilder->method('atPath')->willReturnSelf();
        $violationBuilder->method('addViolation')->willReturnSelf();

        $context->expects($this->once())
            ->method('buildViolation')
            ->with('All items in an order must be from the same restaurant.')
            ->willReturn($violationBuilder);

        $order->validateRestaurantConsistency($context);
    }
}
