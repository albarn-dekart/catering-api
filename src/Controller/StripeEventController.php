<?php

namespace App\Controller;

use App\Entity\Order;
use App\Enum\OrderStatus;
use App\Repository\OrderRepository;
use Doctrine\ORM\EntityManagerInterface;
use Exception;
use Stripe\Checkout\Session;
use Stripe\Exception\ApiErrorException;
use Stripe\Exception\SignatureVerificationException;
use Stripe\Stripe;
use Stripe\Webhook;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use UnexpectedValueException;

class StripeEventController extends AbstractController
{
    private OrderRepository $orderRepository;
    private EntityManagerInterface $entityManager;
    private string $stripeWebhookSecret;

    public function __construct(
        OrderRepository $orderRepository,
        EntityManagerInterface $entityManager,
        string $stripeWebhookSecret,
    ) {
        $this->orderRepository = $orderRepository;
        $this->entityManager = $entityManager;
        $this->stripeWebhookSecret = $stripeWebhookSecret;
    }

    #[Route('/stripe/webhook', name: 'stripe_webhook', methods: ['POST'])]
    public function handleWebhook(Request $request): Response
    {
        $payload = $request->getContent();
        $sigHeader = $request->headers->get('stripe-signature');

        try {
            $event = Webhook::constructEvent($payload, $sigHeader, $this->stripeWebhookSecret);
        } catch (UnexpectedValueException|SignatureVerificationException $e) {
            print('Stripe webhook error: '.$e->getMessage());

            return new Response('Invalid payload or signature.', 400);
        }

        // Handle the event
        switch ($event->type) {
            case 'payment_intent.succeeded':
                $paymentIntent = $event->data->object; // contains a \Stripe\PaymentIntent
                $orderId = $paymentIntent->metadata->order_id ?? null;

                if (!$orderId) {
                    print('Stripe webhook "payment_intent.succeeded" received without an order_id in metadata.');

                    return new Response('Missing order_id in metadata', 400);
                }

                $order = $this->orderRepository->find($orderId);
                if (!$order) {
                    print(sprintf('Stripe webhook could not find Order with ID: %s', $orderId));

                    return new Response('Order not found.', 404);
                }

                // Only update if the order is not already paid to prevent duplicate processing
                if (OrderStatus::Paid !== $order->getStatus()) {
                    $order->setStatus(OrderStatus::Paid);
                    $this->entityManager->flush();
                    print(sprintf('Order %s status updated to Paid via Stripe webhook.', $order->getId()));
                }
                break;

            case 'payment_intent.payment_failed':
                $paymentIntent = $event->data->object;
                $orderId = $paymentIntent->metadata->order_id ?? null;
                print(
                    sprintf(
                        'Payment failed for Order %s. Reason: %s',
                        $orderId,
                        $paymentIntent->last_payment_error ? $paymentIntent->last_payment_error->message : 'N/A'
                    )
                );
                // Optional: Add logic to notify the user about the failed payment.
                break;

            default:
                print('Received unhandled Stripe event type: '.$event->type);
        }

        return new Response('OK', 200);
    }

    #[Route('/api/payments/create-checkout-session', name: 'create_checkout_session', methods: ['POST'])]
    public function createCheckoutSession(Request $request): Response
    {
        $data = json_decode($request->getContent(), true);
        $orderId = $data['orderId'];

        $order = $this->entityManager->getRepository(Order::class)->find($orderId);

        if (!$order) {
            return $this->json(['error' => 'Order not found'], Response::HTTP_NOT_FOUND);
        }

        try {
            $stripeSecretKey = $_ENV['STRIPE_SECRET_KEY'] ?? null;
            if (empty($stripeSecretKey)) {
                return $this->json(['error' => 'Stripe secret key not configured'], Response::HTTP_INTERNAL_SERVER_ERROR);
            }
            Stripe::setApiKey($stripeSecretKey);

            $session = Session::create([
                'payment_method_types' => ['card'], // Temporarily simplified to 'card'
                'line_items' => [[
                    'price_data' => [
                        'currency' => 'pln',
                        'product_data' => [
                            'name' => 'Order #' . $order->getId(),
                        ],
                        'unit_amount' => $order->getTotal(),
                    ],
                    'quantity' => 1,
                ]],
                'mode' => 'payment',
                'success_url' => $_ENV['FRONTEND_BASE_URL'] . '/orders/receipt?orderId=' . $order->getId(),
                'cancel_url' => $_ENV['FRONTEND_BASE_URL'] . '/orders',
                'payment_intent_data' => [
                    'metadata' => [
                        'order_id' => $orderId,
                    ],
                ],
            ]);
        } catch (ApiErrorException $e) {
            return $this->json(['error' => 'Stripe API Error: ' . $e->getMessage()], Response::HTTP_BAD_REQUEST);
        } catch (Exception $e) {
            return $this->json(['error' => 'Error creating Stripe session: ' . $e->getMessage()], Response::HTTP_INTERNAL_SERVER_ERROR);
        }

        return $this->json(['sessionId' => $session->id]);
    }
}
