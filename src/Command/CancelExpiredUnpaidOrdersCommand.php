<?php

namespace App\Command;

use App\Entity\Order;
use App\Enum\OrderStatus;
use App\Repository\OrderRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\AsCommand as AsCommandAlias;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(
    name: 'app:orders:cancel-expired-unpaid',
    description: 'Automatically cancels unpaid orders that have passed their first delivery date.',
)]
class CancelExpiredUnpaidOrdersCommand extends Command
{
    public function __construct(
        private OrderRepository $orderRepository,
        private EntityManagerInterface $entityManager
    ) {
        parent::__construct();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $unpaidOrders = $this->orderRepository->findBy(['status' => OrderStatus::Unpaid]);

        $now = new \DateTimeImmutable();
        $cancelledCount = 0;

        foreach ($unpaidOrders as $order) {
            $earliestDelivery = null;
            foreach ($order->getDeliveries() as $delivery) {
                $deliveryDate = $delivery->getDeliveryDate();
                if ($earliestDelivery === null || ($deliveryDate && $deliveryDate < $earliestDelivery)) {
                    $earliestDelivery = $deliveryDate;
                }
            }

            if ($earliestDelivery && $earliestDelivery < $now->setTime(0, 0, 0)) {
                $order->setStatus(OrderStatus::Cancelled);
                $cancelledCount++;
            }
        }

        if ($cancelledCount > 0) {
            $this->entityManager->flush();
            $io->success(sprintf('Successfully cancelled %d expired unpaid orders.', $cancelledCount));
        } else {
            $io->info('No expired unpaid orders found.');
        }

        return Command::SUCCESS;
    }
}
