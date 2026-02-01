<?php

namespace App\State;

use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProcessorInterface;
use App\Entity\Delivery;
use App\Entity\User;
use App\Enum\DeliveryStatus;
use DateTime;
use DateTimeImmutable;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;

/**
 * @implements ProcessorInterface<Delivery, mixed>
 */
final readonly class DeliveryStatusProcessor implements ProcessorInterface
{
    public function __construct(
        #[Autowire(service: 'api_platform.doctrine.orm.state.persist_processor')]
        private ProcessorInterface $persistProcessor,
        private Security           $security,
        private EntityManagerInterface $entityManager
    ) {}

    public function process(mixed $data, Operation $operation, array $uriVariables = [], array $context = []): mixed
    {
        if ($data instanceof Delivery) {
            $uow = $this->entityManager->getUnitOfWork();
            $originalData = $uow->getOriginalEntityData($data);
            $oldStatus = $originalData['status'] ?? null;

            // Prevent courier reassignment if order is already picked up or completed
            if (!$this->security->isGranted('ROLE_ADMIN')) {
                $oldCourier = $originalData['courier'] ?? null;
                $newCourier = $data->getCourier();

                $oldCourierId = $oldCourier instanceof User ? $oldCourier->getId() : $oldCourier;
                $newCourierId = $newCourier?->getId();

                if ($oldCourierId !== null && $oldCourierId !== $newCourierId) {
                    if (
                        $oldStatus === DeliveryStatus::Picked_up ||
                        $oldStatus === DeliveryStatus::Failed ||
                        $oldStatus === DeliveryStatus::Delivered
                    ) {
                        throw new AccessDeniedHttpException('Cannot reassign courier when delivery is in progress, failed or completed.');
                    }
                }
            }

            if ($this->security->isGranted('ROLE_COURIER') && !$this->security->isGranted('ROLE_ADMIN')) {
                $today = new DateTimeImmutable('today');
                $deliveryDateRaw = $data->getDeliveryDate();
                $deliveryDate = $deliveryDateRaw ? DateTimeImmutable::createFromInterface($deliveryDateRaw)->setTime(0, 0) : null;

                if ($deliveryDate && $deliveryDate > $today) {
                    throw new AccessDeniedHttpException('Couriers cannot update future deliveries.');
                }

                // Check for revert grace period
                if ($oldStatus !== null && (
                    $oldStatus === DeliveryStatus::Delivered ||
                    $oldStatus === DeliveryStatus::Failed
                )) {
                    $newStatus = $data->getStatus();
                    if ($newStatus !== $oldStatus) {
                        $lastUpdate = $data->getStatusUpdatedAt();
                        if ($lastUpdate) {
                            $now = new DateTime();
                            $diff = $now->getTimestamp() - $lastUpdate->getTimestamp();
                            if ($diff > 60) { // 60 seconds
                                throw new AccessDeniedHttpException('Status revert grace period (1 min) has expired.');
                            }
                        }
                    }
                }
            }
        }

        return $this->persistProcessor->process($data, $operation, $uriVariables, $context);
    }
}
