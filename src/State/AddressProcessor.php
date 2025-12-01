<?php

namespace App\State;

use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProcessorInterface;
use App\Entity\Address;
use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\SecurityBundle\Security;
use ApiPlatform\Metadata\GraphQl\Mutation;
use Symfony\Component\DependencyInjection\Attribute\Autowire;

readonly class AddressProcessor implements ProcessorInterface
{
    public function __construct(
        // Inject the default persistence mechanism
        #[Autowire(service: 'api_platform.doctrine.orm.state.persist_processor')]
        private ProcessorInterface $persistProcessor,
        private EntityManagerInterface $entityManager,
        private Security $security
    ) {}

    /**
     * @param Address $data
     */
    public function process(mixed $data, Operation $operation, array $uriVariables = [], array $context = []): mixed
    {
        // 1. Ensure we only run this logic for Address mutations
        if (!$data instanceof Address || !$operation instanceof Mutation) {
            // Pass to the next processor if not applicable
            return $this->persistProcessor->process($data, $operation, $uriVariables, $context);
        }

        // 2. Business Logic: Handle the single default address constraint
        if ($data->getIsDefault()) {
            $user = $data->getUser();

            // Set the user if it's a customer making a create mutation
            // and the user field wasn't included in the payload (or is restricted)
            if (!$user && $this->security->getUser() instanceof User) {
                $user = $this->security->getUser();
                $data->setUser($user);
            }

            if ($user) {
                // Bulk update all other addresses for this user to be non-default.
                $qb = $this->entityManager->createQueryBuilder();
                $qb->update(Address::class, 'a')
                    ->set('a.isDefault', $qb->expr()->literal(false))
                    ->where('a.user = :user')
                    ->andWhere('a.isDefault = :true')
                    ->setParameter('user', $user)
                    ->setParameter('true', true);

                // For an update operation, exclude the current address from the update.
                if ($data->getId() !== null) {
                    $qb->andWhere('a.id != :currentId')
                        ->setParameter('currentId', $data->getId());
                }

                $qb->getQuery()->execute();
            }
        }

        // 3. Delegate to the default processor to persist the current Address object (which now has isDefault=true)
        return $this->persistProcessor->process($data, $operation, $uriVariables, $context);
    }
}