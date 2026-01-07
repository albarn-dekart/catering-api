<?php

namespace App\State;

use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProcessorInterface;
use App\Entity\Delivery;
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
        private Security           $security
    ) {}

    public function process(mixed $data, Operation $operation, array $uriVariables = [], array $context = []): mixed
    {
        if ($data instanceof Delivery && $this->security->isGranted('ROLE_COURIER') && !$this->security->isGranted('ROLE_ADMIN')) {
            $today = new \DateTimeImmutable('today');
            $deliveryDate = $data->getDeliveryDate();

            if ($deliveryDate && $deliveryDate > $today) {
                throw new AccessDeniedHttpException('Couriers cannot update future deliveries.');
            }
        }

        return $this->persistProcessor->process($data, $operation, $uriVariables, $context);
    }
}
