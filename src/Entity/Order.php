<?php

namespace App\Entity;

use App\Repository\OrderRepository;
use App\Enum\OrderStatus;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\Get;
use ApiPlatform\Metadata\GetCollection;
use ApiPlatform\Metadata\Post;
use ApiPlatform\Metadata\Put;
use ApiPlatform\Metadata\Patch;
use ApiPlatform\Metadata\Delete;
use Symfony\Component\Serializer\Annotation\Groups;

#[ApiResource(
    operations: [
        new GetCollection(security: "is_granted('ROLE_ADMIN')"),
        new Post(security: "is_granted('ROLE_USER')"),
        new Get(security: "is_granted('ROLE_USER') and object.getCustomer() == user or is_granted('ROLE_RESTAURANT') and object.getRestaurant().isOwnedBy(user) or is_granted('ROLE_ADMIN')"),
        new Put(security: "is_granted('ROLE_RESTAURANT') and object.getRestaurant().isOwnedBy(user) or is_granted('ROLE_ADMIN')"),
        new Patch(security: "is_granted('ROLE_RESTAURANT') and object.getRestaurant().isOwnedBy(user) or is_granted('ROLE_ADMIN')"),
    ],
    normalizationContext: ['groups' => ['order:read']],
    denormalizationContext: ['groups' => ['order:write']]
)]
#[ORM\Entity(repositoryClass: OrderRepository::class)]
#[ORM\Table(name: '`order`')]
class Order
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\ManyToOne(inversedBy: 'orders')]
    #[Groups(['order:read', 'order:write'])]
    private ?User $customer = null;

    #[ORM\Column(type: 'string', enumType: OrderStatus::class)]
    #[Groups(['order:read', 'order:write'])]
    private OrderStatus $status = OrderStatus::Unpaid;

    #[ORM\ManyToMany(targetEntity: Meal::class)]
    #[Groups(['order:read', 'order:write'])]
    private Collection $meals;

    #[ORM\ManyToOne(inversedBy: 'orders')]
    #[Groups(['order:read', 'order:write'])]
    private ?Restaurant $restaurant = null;

    #[ORM\OneToMany(targetEntity: Delivery::class, mappedBy: 'order', orphanRemoval: true)]
    #[Groups(['order:read'])]
    private Collection $deliveries;

    public function __construct()
    {
        $this->meals = new ArrayCollection();
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

    public function getStatus(): ?OrderStatus
    {
        return $this->status;
    }

    public function setStatus(OrderStatus $status): static
    {
        $this->status = $status;

        return $this;
    }

    /**
     * @return Collection<int, Meal>
     */
    public function getMeals(): Collection
    {
        return $this->meals;
    }

    public function addMeal(Meal $meal): static
    {
        if (!$this->meals->contains($meal)) {
            $this->meals->add($meal);
        }

        return $this;
    }

    public function removeMeal(Meal $meal): static
    {
        $this->meals->removeElement($meal);

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
}