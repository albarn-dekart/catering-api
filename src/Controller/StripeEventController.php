<?php

namespace App\Controller;

use App\Entity\Order;
use App\Enum\OrderStatus;
use App\Repository\OrderRepository;
use App\Service\InvoiceService;
use App\Service\MailerService;
use Doctrine\ORM\EntityManagerInterface;
use Exception;
use Stripe\Checkout\Session;
use Stripe\Exception\SignatureVerificationException;
use Stripe\Webhook;
use Stripe\Stripe;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use UnexpectedValueException;
use Psr\Log\LoggerInterface;

class StripeEventController extends AbstractController
{
    public function __construct(
        // Autowire the secret key from your .env.txt file
        #[Autowire(env: 'STRIPE_SECRET_KEY')]
        private readonly string                 $stripeSecretKey,
        #[Autowire(env: 'STRIPE_WEBHOOK_SECRET')]
        private readonly string                 $stripeWebhookSecret,
        private readonly EntityManagerInterface $entityManager,
        private readonly OrderRepository $orderRepository,
        private readonly LoggerInterface $logger,
        private readonly MailerService $mailerService,
        private readonly InvoiceService $invoiceService
    ) {
        Stripe::setApiKey($this->stripeSecretKey);
    }

    #[Route('/stripe/webhook', name: 'stripe_webhook', methods: ['POST'])]
    public function webhook(Request $request): Response
    {
        $payload = $request->getContent();
        $sigHeader = $request->headers->get('Stripe-Signature');

        if (null === $sigHeader) {
            throw new BadRequestHttpException('Missing Stripe-Signature header.');
        }

        try {
            $event = Webhook::constructEvent($payload, $sigHeader, $this->stripeWebhookSecret);
        } catch (UnexpectedValueException) {
            throw new BadRequestHttpException('Invalid Stripe payload.');
        } catch (SignatureVerificationException) {
            throw new BadRequestHttpException('Invalid Stripe signature.');
        }

        $this->logger->info('Stripe webhook received', ['event_type' => $event->type]);

        // Handle the event
        switch ($event->type) {
            case 'checkout.session.completed':
                /** @var Session $session */
                $session = $event->data->object;

                $orderId = $session->metadata->order_id ?? null;
                $this->logger->info('Processing checkout.session.completed', [
                    'order_id' => $orderId,
                    'payment_status' => $session->payment_status,
                    'metadata' => (array) $session->metadata
                ]);

                if ($orderId) {
                    /** @var Order|null $order */
                    $order = $this->orderRepository->find($orderId);

                    if (!$order) {
                        $this->logger->error('Order not found', ['order_id' => $orderId]);
                    } else {
                        $this->logger->info('Order found', [
                            'order_id' => $orderId,
                            'current_status' => $order->getStatus()->value
                        ]);

                        if ($session->payment_status === 'paid') {
                            $order->setStatus(OrderStatus::Paid);
                            $order->setPaymentIntentId($session->payment_intent);
                            $this->entityManager->flush();

                            // Generate Invoice PDF
                            try {
                                $invoicePdf = $this->invoiceService->generateInvoicePdf($order);
                                $invoiceName = sprintf('Faktura_%s.pdf', $order->getId());

                                // Send confirmation email with Invoice
                                $this->mailerService->sendOrderConfirmation($order, $invoicePdf, $invoiceName);
                            } catch (Exception $e) {
                                $this->logger->error('Failed to generate/send invoice', [
                                    'order_id' => $orderId,
                                    'error' => $e->getMessage()
                                ]);
                                // Fallback: Send email without invoice if generation fails
                                $this->mailerService->sendOrderConfirmation($order);
                            }

                            $this->logger->info('Order updated to Paid, Invoice generated', [
                                'order_id' => $orderId,
                                'payment_intent_id' => $session->payment_intent
                            ]);
                        } else {
                            $this->logger->warning('Payment status not paid', [
                                'payment_status' => $session->payment_status
                            ]);
                        }
                    }
                } else {
                    $this->logger->warning('No order_id in session metadata');
                }
                break;

            default:
                break;
        }

        return new Response('', Response::HTTP_OK);
    }
}
