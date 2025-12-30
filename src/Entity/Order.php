<?php

namespace App\Entity;

use ApiPlatform\Metadata\ApiProperty;
use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\GraphQl\DeleteMutation;
use ApiPlatform\Metadata\GraphQl\Mutation;
use ApiPlatform\Metadata\GraphQl\Query;
use ApiPlatform\Metadata\GraphQl\QueryCollection;
use App\Enum\OrderStatus;
use App\Repository\OrderRepository;
use DateTimeInterface;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Serializer\Attribute\Groups;
use ApiPlatform\Metadata\ApiFilter;
use ApiPlatform\Doctrine\Orm\Filter\SearchFilter;
use ApiPlatform\Doctrine\Orm\Filter\DateFilter;
use App\Filter\OrderSearchFilter;
use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Component\Validator\Context\ExecutionContextInterface;


#[ORM\Entity(repositoryClass: OrderRepository::class)]
#[ORM\Table(name: '`order`')]
#[ORM\Index(fields: ['status'])]
#[ORM\HasLifecycleCallbacks]
#[ApiFilter(OrderSearchFilter::class)]
#[ApiFilter(SearchFilter::class, properties: ['status' => 'exact'])]
#[ApiFilter(DateFilter::class, properties: ['createdAt'])]
#[ApiResource(order: ['id' => 'DESC'])]
#[ApiResource(
    operations: [],
    normalizationContext: ['groups' => ['read']],
    denormalizationContext: ['groups' => ['create', 'update']],
    graphQlOperations: [
        new QueryCollection(security: "is_granted('ROLE_ADMIN') or is_granted('ROLE_RESTAURANT') or is_granted('ROLE_CUSTOMER') or is_granted('ROLE_DRIVER')"),
        new Query(security: "is_granted('ROLE_CUSTOMER') and object.getCustomer() == user or (is_granted('ROLE_RESTAURANT') and object.getRestaurant().getOwner() == user) or is_granted('ROLE_ADMIN')"),
        new Mutation(security: "is_granted('ROLE_CUSTOMER')", name: 'create'),
        new Mutation(security: "is_granted('ROLE_ADMIN') or (is_granted('ROLE_RESTAURANT') and object.getRestaurant().getOwner() == user) or (is_granted('ROLE_CUSTOMER') and object.getCustomer() == user)", name: 'update'),
        new DeleteMutation(security: "is_granted('ROLE_ADMIN')", name: 'delete')
    ],
)]
class Order
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\ManyToOne(inversedBy: 'orders')]
    #[ORM\JoinColumn(onDelete: 'SET NULL')]
    #[ApiProperty(readableLink: true, writableLink: false)]
    #[Groups(['read', 'create'])]
    private ?User $customer = null;

    #[ORM\Column(type: 'string', enumType: OrderStatus::class)]
    #[Groups(['read', 'update'])]
    private OrderStatus $status = OrderStatus::Unpaid;

    #[ORM\OneToMany(targetEntity: OrderItem::class, mappedBy: 'order', cascade: ['persist'], orphanRemoval: true)]
    #[ApiProperty(readableLink: true, writableLink: true)]
    #[Groups(['read', 'create'])]
    private Collection $orderItems;

    #[ORM\ManyToOne(inversedBy: 'orders')]
    #[ORM\JoinColumn(onDelete: 'SET NULL')]
    #[ApiProperty(readableLink: true, writableLink: false)]
    #[Groups(['read', 'create'])]
    private ?Restaurant $restaurant = null;

    #[ORM\OneToMany(targetEntity: Delivery::class, mappedBy: 'order', cascade: ['persist'], orphanRemoval: true)]
    #[ApiProperty(readableLink: true, writableLink: true)]
    #[Groups(['read', 'create'])]
    private Collection $deliveries;

    #[ORM\Column(type: Types::INTEGER)]
    #[Groups(['create'])]
    private ?int $total = null;

    #[ORM\Column(length: 255, nullable: true)]
    #[Groups(['read'])]
    private ?string $paymentIntentId = null;

    #[ORM\Column(length: 255, nullable: true)]
    #[Groups(['read', 'create'])]
    private ?string $deliveryFirstName = null;

    #[ORM\Column(length: 255, nullable: true)]
    #[Groups(['read', 'create'])]
    private ?string $deliveryLastName = null;

    #[ORM\Column(length: 255, nullable: true)]
    #[Groups(['read', 'create'])]
    private ?string $deliveryPhoneNumber = null;

    #[ORM\Column(length: 255, nullable: true)]
    #[Groups(['read', 'create'])]
    private ?string $deliveryStreet = null;

    #[ORM\Column(length: 255, nullable: true)]
    #[Groups(['read', 'create'])]
    private ?string $deliveryApartment = null;

    #[ORM\Column(length: 255, nullable: true)]
    #[Groups(['read', 'create'])]
    private ?string $deliveryCity = null;

    #[ORM\Column(length: 255, nullable: true)]
    #[Groups(['read', 'create'])]
    private ?string $deliveryZipCode = null;

    #[ORM\Column(type: 'datetime')]
    #[Groups(['read'])]
    private ?\DateTimeInterface $createdAt = null;

    public function __construct()
    {
        $this->orderItems = new ArrayCollection();
        $this->deliveries = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getCustomer(): ?User
    {
        return $this->customer;
    }

    public function setCustomer(?User $customer): static
    {
        $this->customer = $customer;

        return $this;
    }

    public function getStatus(): OrderStatus
    {
        return $this->status;
    }

    public function setStatus(OrderStatus $status): static
    {
        $this->status = $status;

        return $this;
    }

    /**
     * @return Collection<int, OrderItem>
     */
    public function getOrderItems(): Collection
    {
        return $this->orderItems;
    }

    public function addOrderItem(OrderItem $orderItem): static
    {
        if (!$this->orderItems->contains($orderItem)) {
            $this->orderItems->add($orderItem);
            $orderItem->setOrder($this);
        }

        return $this;
    }

    public function removeOrderItem(OrderItem $orderItem): static
    {
        if ($this->orderItems->removeElement($orderItem)) {
            // set the owning side to null (unless already changed)
            if ($orderItem->getOrder() === $this) {
                $orderItem->setOrder(null);
            }
        }

        return $this;
    }

    public function getRestaurant(): ?Restaurant
    {
        return $this->restaurant;
    }

    public function setRestaurant(?Restaurant $restaurant): static
    {
        $this->restaurant = $restaurant;

        return $this;
    }

    /**
     * @return Collection<int, Delivery>
     */
    public function getDeliveries(): Collection
    {
        return $this->deliveries;
    }

    public function addDelivery(Delivery $delivery): static
    {
        if (!$this->deliveries->contains($delivery)) {
            $this->deliveries->add($delivery);
            $delivery->setOrder($this);
        }

        return $this;
    }

    public function removeDelivery(Delivery $delivery): static
    {
        if ($this->deliveries->removeElement($delivery)) {
            // set the owning side to null (unless already changed)
            if ($delivery->getOrder() === $this) {
                $delivery->setOrder(null);
            }
        }

        return $this;
    }

    public function getTotal(): ?int
    {
        return $this->total;
    }

    public function setTotal(int $total): static
    {
        $this->total = $total;

        return $this;
    }

    /**
     * Get the earliest delivery date from the deliveries collection.
     * Returns null if no deliveries exist.
     */
    public function getDeliveryStartDate(): ?DateTimeInterface
    {
        if ($this->deliveries->isEmpty()) {
            return null;
        }

        $dates = [];
        foreach ($this->deliveries as $delivery) {
            $dates[] = $delivery->getDeliveryDate();
        }

        return min($dates);
    }

    /**
     * Get the latest delivery date from the deliveries collection.
     * Returns null if no deliveries exist.
     */
    public function getDeliveryEndDate(): ?DateTimeInterface
    {
        if ($this->deliveries->isEmpty()) {
            return null;
        }

        $dates = [];
        foreach ($this->deliveries as $delivery) {
            $dates[] = $delivery->getDeliveryDate();
        }

        return max($dates);
    }

    /**
     * Get unique delivery days (weekday abbreviations) from the deliveries collection.
     * Returns array of day abbreviations (e.g., ['Mon', 'Wed', 'Fri']).
     */
    public function getDeliveryDays(): array
    {
        if ($this->deliveries->isEmpty()) {
            return [];
        }

        $days = [];
        foreach ($this->deliveries as $delivery) {
            $dayOfWeek = $delivery->getDeliveryDate()->format('D');
            if (!in_array($dayOfWeek, $days)) {
                $days[] = $dayOfWeek;
            }
        }

        return $days;
    }

    public function getPaymentIntentId(): ?string
    {
        return $this->paymentIntentId;
    }

    public function setPaymentIntentId(?string $paymentIntentId): static
    {
        $this->paymentIntentId = $paymentIntentId;

        return $this;
    }

    public function getDeliveryFirstName(): ?string
    {
        return $this->deliveryFirstName;
    }

    public function setDeliveryFirstName(?string $deliveryFirstName): static
    {
        $this->deliveryFirstName = $deliveryFirstName;

        return $this;
    }

    public function getDeliveryLastName(): ?string
    {
        return $this->deliveryLastName;
    }

    public function setDeliveryLastName(?string $deliveryLastName): static
    {
        $this->deliveryLastName = $deliveryLastName;

        return $this;
    }

    public function getDeliveryPhoneNumber(): ?string
    {
        return $this->deliveryPhoneNumber;
    }

    public function setDeliveryPhoneNumber(?string $deliveryPhoneNumber): static
    {
        $this->deliveryPhoneNumber = $deliveryPhoneNumber;

        return $this;
    }

    public function getDeliveryStreet(): ?string
    {
        return $this->deliveryStreet;
    }

    public function setDeliveryStreet(?string $deliveryStreet): static
    {
        $this->deliveryStreet = $deliveryStreet;

        return $this;
    }

    #[Assert\Callback]
    public function validateRestaurantConsistency(ExecutionContextInterface $context): void
    {
        if ($this->orderItems->isEmpty()) {
            return;
        }

        $firstRestaurant = null;

        foreach ($this->orderItems as $item) {
            $mealPlan = $item->getMealPlan();
            if (!$mealPlan) {
                continue;
            }

            $currentRestaurant = $mealPlan->getRestaurant();

            if (!$firstRestaurant) {
                $firstRestaurant = $currentRestaurant;
            } elseif ($firstRestaurant !== $currentRestaurant) {
                $context->buildViolation('All items in an order must be from the same restaurant.')
                    ->atPath('orderItems')
                    ->addViolation();
                return;
            }
        }

        // Also ensure the order's restaurant matches the items' restaurant
        if ($this->restaurant && $firstRestaurant && $this->restaurant !== $firstRestaurant) {
            $context->buildViolation('The order restaurant does not match the items restaurant.')
                ->atPath('restaurant')
                ->addViolation();
        }
    }

    public function getDeliveryApartment(): ?string
    {
        return $this->deliveryApartment;
    }

    public function setDeliveryApartment(?string $deliveryApartment): static
    {
        $this->deliveryApartment = $deliveryApartment;

        return $this;
    }

    public function getDeliveryCity(): ?string
    {
        return $this->deliveryCity;
    }

    public function setDeliveryCity(?string $deliveryCity): static
    {
        $this->deliveryCity = $deliveryCity;

        return $this;
    }

    public function getDeliveryZipCode(): ?string
    {
        return $this->deliveryZipCode;
    }

    public function setDeliveryZipCode(?string $deliveryZipCode): static
    {
        $this->deliveryZipCode = $deliveryZipCode;

        return $this;
    }

    public function getCreatedAt(): ?\DateTimeInterface
    {
        return $this->createdAt;
    }

    public function setCreatedAt(\DateTimeInterface $createdAt): static
    {
        $this->createdAt = $createdAt;

        return $this;
    }

    #[ORM\PrePersist]
    public function onPrePersist(): void
    {
        // Set createdAt timestamp
        if ($this->createdAt === null) {
            $this->createdAt = new \DateTime();
        }
        $this->calculateTotal();
    }

    #[ORM\PreUpdate]
    public function onPreUpdate(): void
    {
        $this->calculateTotal();
    }

    public function calculateTotal(): void
    {
        // Calculate Total
        $subtotal = 0;
        foreach ($this->orderItems as $item) {
            if ($item->getMealPlan() && $item->getQuantity()) {
                $subtotal += $item->getMealPlan()->getPrice() * $item->getQuantity();
            }
        }

        $deliveryCount = $this->deliveries->count();
        if ($deliveryCount > 0) {
            $subtotal *= $deliveryCount;

            // Add delivery fees
            if ($this->restaurant) {
                $subtotal += ($this->restaurant->getDeliveryPrice() * $deliveryCount);
            }
        }
        $this->total = $subtotal;
    }
}
