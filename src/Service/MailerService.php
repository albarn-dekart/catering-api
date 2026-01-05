<?php

namespace App\Service;

use App\Entity\Order;
use Psr\Log\LoggerInterface;
use Symfony\Component\Mailer\Exception\TransportExceptionInterface;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mime\Email;

class MailerService
{
    public function __construct(
        private readonly MailerInterface $mailer,
        private readonly LoggerInterface $logger,
        private readonly string $senderEmail
    ) {}

    public function sendCourierInvitation(string $email, string $password): void
    {
        $subject = 'Zaproszenie do zespołu kierowców';
        $content = sprintf(
            'Witaj!<br><br>Zostałeś dodany do systemu jako kierowca.<br>Twoje dane logowania:<br>Email: %s<br>Hasło: %s<br><br>Zaloguj się w aplikacji, aby rozpocząć pracę.',
            $email,
            $password
        );

        $this->sendEmail($email, $subject, $content);
    }

    public function sendRestaurantInvitation(string $email, string $password, string $restaurantName): void
    {
        $subject = 'Witaj w platformie Cateringowej!';
        $content = sprintf(
            'Witaj!<br><br>Twoja restauracja "%s" została pomyślnie utworzona.<br>Możesz teraz zarządzać swoim menu i zamówieniami.<br><br>Dane logowania:<br>Email: %s<br>Hasło: %s',
            $restaurantName,
            $email,
            $password
        );

        $this->sendEmail($email, $subject, $content);
    }

    public function sendWelcomeEmail(string $email): void
    {
        $subject = 'Witaj w naszym cateringu!';
        $content = 'Witaj!<br><br>Dziękujemy za rejestrację w naszym systemie.<br>Zapraszamy do zapoznania się z ofertą restauracji i złożenia pierwszego zamówienia!';

        $this->sendEmail($email, $subject, $content);
    }

    public function sendOrderConfirmation(Order $order, ?string $attachmentContent = null, ?string $attachmentName = null): void
    {
        $user = $order->getCustomer();
        if (!$user) {
            return;
        }

        $email = $user->getEmail();
        $subject = sprintf('Potwierdzenie zamówienia #%s', $order->getId());

        // Calculate daily costs for breakdown
        $dailyFoodCost = 0;
        $itemsHtml = '<ul>';
        foreach ($order->getOrderItems() as $item) {
            $mealPlan = $item->getMealPlan();
            $mealName = $mealPlan ? $mealPlan->getName() : 'Produkt';
            $price = $mealPlan ? $mealPlan->getPrice() : 0;
            $quantity = $item->getQuantity();
            $lineTotal = $price * $quantity;
            $dailyFoodCost += $lineTotal;

            $itemsHtml .= sprintf(
                '<li>%s (x%d) - %s zł</li>',
                $mealName,
                $quantity,
                number_format($lineTotal / 100, 2)
            );
        }
        $itemsHtml .= '</ul>';

        $deliveryCount = $order->getDeliveries()->count();
        $deliveryPrice = $order->getRestaurant() ? $order->getRestaurant()->getDeliveryPrice() : 0;

        $calculationHtml = sprintf(
            '<br><strong>Szczegóły kalkulacji:</strong><br>' .
                'Koszt jedzenia (dzień): %s zł<br>' .
                'Koszt dostawy (dzień): %s zł<br>' .
                'Liczba dni dostaw: %d<br>' .
                '-----------------------------------<br>' .
                '<strong>Razem: (%s zł + %s zł) * %d dni = %s zł</strong>',
            number_format($dailyFoodCost / 100, 2),
            number_format($deliveryPrice / 100, 2),
            $deliveryCount,
            number_format($dailyFoodCost / 100, 2),
            number_format($deliveryPrice / 100, 2),
            $deliveryCount,
            number_format($order->getTotal() / 100, 2)
        );

        $content = sprintf(
            'Dziękujemy za złożenie zamówienia!<br><br>Numer zamówienia: #%s<br>Status: Opłacone<br><br>Zamówione pozycje:%s%s<br><br>Smacznego!',
            $order->getId(),
            $itemsHtml,
            $calculationHtml
        );

        $this->sendEmail($email, $subject, $content, $attachmentContent, $attachmentName);
    }

    private function sendEmail(string $to, string $subject, string $content, ?string $attachmentContent = null, ?string $attachmentName = null): void
    {
        try {
            $email = (new Email())
                ->from($this->senderEmail)
                ->to($to)
                ->subject($subject)
                ->html($content);

            if ($attachmentContent && $attachmentName) {
                $email->attach($attachmentContent, $attachmentName, 'application/pdf');
            }

            $this->mailer->send($email);
            $this->logger->info(sprintf('Email sent to %s with subject "%s"', $to, $subject));
        } catch (TransportExceptionInterface $e) {
            // Log error but don't break the application if mailer is not configured
            $this->logger->error(sprintf('Failed to send email to %s: %s', $to, $e->getMessage()));
        }
    }
}
