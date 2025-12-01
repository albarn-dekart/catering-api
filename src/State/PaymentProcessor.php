<?php

namespace App\State;

use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProcessorInterface;
use App\ApiResource\Payment;
use App\Entity\Order;
use Stripe\Checkout\Session;
use Stripe\Stripe;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;

readonly class PaymentProcessor implements ProcessorInterface
{
    public function __construct(
        #[Autowire(env: 'STRIPE_SECRET_KEY')]
        private string          $stripeSecretKey,
        #[Autowire(env: 'FRONTEND_BASE_URL')]
        private string          $frontendBaseUrl
    ) {}

    public function process(mixed $data, Operation $operation, array $uriVariables = [], array $context = []): mixed
    {
        if (!$data instanceof Payment) {
            return $data;
        }

        /** @var Order $order */
        $order = $data->order;

        if (!$order) {
            throw new NotFoundHttpException('Order not found or invalid Order IRI provided.');
        }

        if (empty($this->stripeSecretKey)) {
            throw new \RuntimeException('Stripe secret key not configured');
        }

        Stripe::setApiKey($this->stripeSecretKey);

        try {
            $session = Session::create([
                'payment_method_types' => ['card'],
                'line_items' => [
                    [
                        'price_data' => [
                            'currency' => 'pln',
                            'product_data' => [
                                'name' => 'Order #' . $order->getId(),
                            ],
                            'unit_amount' => $order->getTotal(),
                        ],
                        'quantity' => 1,
                    ]
                ],
                'mode' => 'payment',
                'success_url' => $this->frontendBaseUrl . '/order?id=' . $order->getId(),
                'cancel_url' => $this->frontendBaseUrl . '/orders',
                'metadata' => [
                    'order_id' => $order->getId(),
                ],
                'payment_intent_data' => [
                    'metadata' => [
                        'order_id' => $order->getId(),
                    ],
                ],
            ]);

            $data->sessionId = $session->id;
            $data->url = $session->url;
            $data->id = $session->id;
        } catch (\Exception $e) {
            throw new BadRequestHttpException('Stripe API Error: ' . $e->getMessage());
        }

        return $data;
    }
}
