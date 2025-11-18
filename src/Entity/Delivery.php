<?php

namespace App\Entity;

use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\Get;
use ApiPlatform\Metadata\GetCollection;
use ApiPlatform\Metadata\Patch;
// 💡 We omit POST, PUT, and DELETE since Deliveries are created by the OrderStateProcessor
use App\Enum\DeliveryStatus;
use App\Repository\DeliveryRepository;
use App\State\MeStateProvider;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Serializer\Annotation\Groups;
use Symfony\Component\Validator\Constraints as Assert;

#[ORM\Entity(repositoryClass: DeliveryRepository::class)]
#[ApiResource(
    operations: [
        new GetCollection(
        // Admins, Owners, and Drivers can view collections
            normalizationContext: ['groups' => ['delivery:read']],
            // 💡 A Doctrine Extension will be needed here to filter for Owners and Drivers
            security: "is_granted('ROLE_ADMIN') or is_granted('ROLE_RESTAURANT') or is_granted('ROLE_DRIVER')"
        ),
        new Get(
        // Access granted if Admin, or if user is the Driver, or if user is the Restaurant Owner
            normalizationContext: ['groups' => ['delivery:read:detailed']],
            security: "is_granted('ROLE_ADMIN') or object.isDrivenBy(user) or object.isOwnedByRestaurantOwner(user)"
        ),
        new Patch(
        // Only Drivers and Owners can update a delivery
            denormalizationContext: ['groups' => ['delivery:update']],
            security: "is_granted('ROLE_ADMIN') or object.isDrivenBy(user) or object.isOwnedByRestaurantOwner(user)",
        // A DeliveryStateProcessor might be used here for status transition validation
        // We will omit the processor for now for simplicity, focusing on security.
        ),
    ],
    // Default visibility for embedded objects (e.g., in Order)
    normalizationContext: ['groups' => ['delivery:read']]
)]
class Delivery
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    #[Groups(['delivery:read', 'delivery:read:detailed', 'order:read:detailed'])]
    private ?int $id = null;

    #[ORM\ManyToOne(inversedBy: 'deliveries')]
    // Driver: Writable for Owners (to assign), Readable for Owners/Drivers
    #[Groups(['delivery:read', 'delivery:read:detailed', 'delivery:update', 'order:read:detailed'])]
    private ?User $driver = null;

    #[ORM\Column(type: 'string', enumType: DeliveryStatus::class)]
    #[Assert\NotNull]
    #[Groups(['delivery:read', 'delivery:read:detailed', 'delivery:update', 'order:read:detailed'])]
    private DeliveryStatus $status = DeliveryStatus::Pending;

    #[ORM\Column(type: 'date_immutable')]
    #[Assert\NotNull]
    #[Groups(['delivery:read', 'delivery:read:detailed', 'order:read:detailed'])]
    private ?\DateTimeInterface $deliveryDate = null;

    #[ORM\ManyToOne(inversedBy: 'deliveries')]
    #[ORM\JoinColumn(nullable: false)]
    #[Groups(['delivery:read:detailed'])] // Expose parent Order on item GET
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

        return $this;
    }

    public function getStatus(): DeliveryStatus
    {
        return $this->status;
    }

    public function setStatus(DeliveryStatus $status): static
    {
        $this->status = $status;

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