<?php

namespace App\Entity;

use App\Enum\DeliveryStatus;
use App\Repository\DeliveryRepository;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;

#[ORM\Entity(repositoryClass: DeliveryRepository::class)]
class Delivery
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\ManyToOne(inversedBy: 'deliveries')]
    private ?User $driver = null;

    #[ORM\Column(type: 'string', enumType: DeliveryStatus::class)]
    #[Assert\NotNull]
    private DeliveryStatus $status = DeliveryStatus::Pending;

    #[ORM\Column]
    #[Assert\NotNull]
    private ?\DateTimeImmutable $deliveryDate = null;

    #[ORM\ManyToOne(inversedBy: 'deliveries')]
    #[ORM\JoinColumn(nullable: false)]
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

    public function getDeliveryDate(): ?\DateTimeImmutable
    {
        return $this->deliveryDate;
    }

    public function setDeliveryDate(\DateTimeImmutable $deliveryDate): static
    {
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