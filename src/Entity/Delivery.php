<?php

namespace App\Entity;

use ApiPlatform\Metadata\ApiProperty;
use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\GraphQl\DeleteMutation;
use ApiPlatform\Metadata\GraphQl\Mutation;
use ApiPlatform\Metadata\GraphQl\Query;
use ApiPlatform\Metadata\GraphQl\QueryCollection;
use App\Enum\DeliveryStatus;
use App\Enum\OrderStatus;
use App\Repository\DeliveryRepository;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Serializer\Annotation\Groups;
use Symfony\Component\Validator\Constraints as Assert;
use ApiPlatform\Metadata\ApiFilter;
use ApiPlatform\Doctrine\Orm\Filter\SearchFilter;
use ApiPlatform\Doctrine\Orm\Filter\OrderFilter;
use App\Filter\DeliverySearchFilter;

#[ORM\Entity(repositoryClass: DeliveryRepository::class)]
#[ApiFilter(DeliverySearchFilter::class)]
#[ApiFilter(SearchFilter::class, properties: [
    'status' => 'exact',
    'driver' => 'exact',
    'order.restaurant' => 'exact'
])]
#[ApiFilter(OrderFilter::class, properties: ['deliveryDate', 'id'])]
#[ApiResource(order: ['id' => 'DESC'])]
#[ApiResource(
    operations: [],
    normalizationContext: ['groups' => ['read']],
    denormalizationContext: ['groups' => ['update']],
    graphQlOperations: [
        new QueryCollection(security: "is_granted('ROLE_ADMIN') or is_granted('ROLE_RESTAURANT') or is_granted('ROLE_DRIVER') or is_granted('ROLE_CUSTOMER')"),
        new Query(security: "is_granted('ROLE_ADMIN') or object.getOrder().getRestaurant().getOwner() == user or object.getDriver() == user or object.getOrder().getCustomer() == user"),
        new Mutation(security: "is_granted('ROLE_ADMIN') or object.getDriver() == user or object.isOwnedByRestaurantOwner(user)", name: 'update'),
        new DeleteMutation(security: "is_granted('ROLE_ADMIN')", name: 'delete')
    ],
)]
class Delivery
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;



    #[ORM\ManyToOne(inversedBy: 'deliveries')]
    #[ORM\JoinColumn(onDelete: 'SET NULL')]
    #[ApiProperty(readableLink: true, writableLink: false)]
    #[Groups(['read', 'update'])]
    private ?User $driver = null;

    #[ORM\Column(type: 'string', enumType: DeliveryStatus::class)]
    #[Assert\NotNull]
    #[Groups(['read', 'update', 'create'])]
    private DeliveryStatus $status = DeliveryStatus::Pending;

    #[ORM\Column(type: 'date_immutable')]
    #[Assert\NotNull]
    #[ApiProperty(writable: true)]
    #[Groups(['read', 'create'])]
    private ?\DateTimeInterface $deliveryDate = null;

    #[ORM\ManyToOne(inversedBy: 'deliveries')]
    #[ORM\JoinColumn(nullable: true)]
    #[ApiProperty(readableLink: true, writableLink: false)]
    #[Groups(['read'])]
    private ?Order $order = null;

    public function isDrivenBy(User $user): bool
    {
        return $this->driver === $user;
    }

    public function isOwnedByRestaurantOwner(User $user): bool
    {
        return $this->getOrder()?->getRestaurant()?->getOwner() === $user;
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getDriver(): ?User
    {
        return $this->driver;
    }

    public function setDriver(?User $driver): static
    {
        $this->driver = $driver;

        if ($driver !== null && $this->status === DeliveryStatus::Pending) {
            $this->setStatus(DeliveryStatus::Assigned);
        }

        return $this;
    }

    public function getStatus(): DeliveryStatus
    {
        return $this->status;
    }

    public function setStatus(DeliveryStatus $status): static
    {
        $this->status = $status;

        $order = $this->getOrder();
        if ($order) {
            // Paid -> Active
            if (
                $order->getStatus() === OrderStatus::Paid &&
                ($status === DeliveryStatus::Assigned ||
                    $status === DeliveryStatus::Picked_up ||
                    $status === DeliveryStatus::Delivered)
            ) {
                $order->setStatus(OrderStatus::Active);
            }

            // Active -> Completed
            // Only check completion if current status is Delivered and Order is Active (or Paid/Unpaid? usually Active)
            if (
                ($status === DeliveryStatus::Delivered || $status === DeliveryStatus::Returned) &&
                ($order->getStatus() === OrderStatus::Active || $order->getStatus() === OrderStatus::Paid)
            ) {

                $allDelivered = true;
                foreach ($order->getDeliveries() as $delivery) {
                    // Check if delivery is in any terminal state (Delivered OR Returned)
                    // Failed is NOT terminal (can still be retried)
                    $status = $delivery->getStatus();
                    if ($status !== DeliveryStatus::Delivered && $status !== DeliveryStatus::Returned) {
                        $allDelivered = false;
                        break;
                    }
                }

                if ($allDelivered) {
                    $order->setStatus(OrderStatus::Completed);
                }
            }
        }

        return $this;
    }

    public function getDeliveryDate(): ?\DateTimeInterface
    {
        return $this->deliveryDate;
    }

    public function setDeliveryDate(\DateTimeInterface $deliveryDate): static
    {
        if ($deliveryDate instanceof \DateTime) {
            $deliveryDate = \DateTimeImmutable::createFromMutable($deliveryDate);
        }
        $this->deliveryDate = $deliveryDate;

        return $this;
    }



    public function getOrder(): ?Order
    {
        return $this->order;
    }

    public function setOrder(?Order $order): static
    {
        $this->order = $order;

        return $this;
    }
}
