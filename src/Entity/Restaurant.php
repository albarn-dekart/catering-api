<?php

namespace App\Entity;

use App\Repository\RestaurantRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
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
        new GetCollection(),
        new Post(security: "is_granted('ROLE_ADMIN')"),
        new Get(),
        new Put(security: "is_granted('ROLE_RESTAURANT') and object.isOwnedBy(user)"),
        new Patch(security: "is_granted('ROLE_RESTAURANT') and object.isOwnedBy(user)"),
        new Delete(security: "is_granted('ROLE_ADMIN') or (is_granted('ROLE_RESTAURANT') and object.isOwnedBy(user))"),
    ],
    normalizationContext: ['groups' => ['restaurant:read']],
    denormalizationContext: ['groups' => ['restaurant:write']]
)]
#[ORM\Entity(repositoryClass: RestaurantRepository::class)]
class Restaurant
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 50)]
    #[Groups(['restaurant:read', 'restaurant:write'])]
    private ?string $name = null;

    #[ORM\Column(length: 255, nullable: true)]
    #[Groups(['restaurant:read', 'restaurant:write'])]
    private ?string $imageFile = null;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    #[Groups(['restaurant:read', 'restaurant:write'])]
    private ?string $description = null;

    #[ORM\OneToOne(inversedBy: 'restaurant', cascade: ['persist', 'remove'])]
    private ?User $owner = null;

    /**
     * @var Collection<int, Order>
     */
    #[ORM\OneToMany(targetEntity: Order::class, mappedBy: 'restaurant', orphanRemoval: true)]
    private Collection $orders;

    /**
     * @var Collection<int, Meal>
     */
    #[ORM\OneToMany(targetEntity: Meal::class, mappedBy: 'restaurant')]
    private Collection $meals;

    /**
     * @var Collection<int, MealPlan>
     */
    #[ORM\OneToMany(targetEntity: MealPlan::class, mappedBy: 'restaurant')]
    private Collection $mealPlans;

    public function __construct()
    {
        $this->orders = new ArrayCollection();
        $this->meals = new ArrayCollection();
        $this->mealPlans = new ArrayCollection();
    }

    public function isOwnedBy(?User $user): bool
    {
        if ($user === null || $this->owner === null) {
            return false;
        }

        return $this->owner->getId() === $user->getId();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getName(): ?string
    {
        return $this->name;
    }

    public function setName(string $name): static
    {
        $this->name = $name;

        return $this;
    }

    public function getImageFile(): ?string
    {
        return $this->imageFile;
    }

    public function setImageFile(?string $imageFile): static
    {
        $this->imageFile = $imageFile;

        return $this;
    }

    public function getDescription(): ?string
    {
        return $this->description;
    }

    public function setDescription(?string $description): static
    {
        $this->description = $description;

        return $this;
    }

    public function getOwner(): ?User
    {
        return $this->owner;
    }

    public function setOwner(?User $owner): static
    {
        $this->owner = $owner;

        return $this;
    }

    /**
     * @return Collection<int, Order>
     */
    public function getOrders(): Collection
    {
        return $this->orders;
    }

    public function addOrder(Order $order): static
    {
        if (!$this->orders->contains($order)) {
            $this->orders->add($order);
            $order->setRestaurant($this);
        }

        return $this;
    }

    public function removeOrder(Order $order): static
    {
        if ($this->orders->removeElement($order)) {
            // set the owning side to null (unless already changed)
            if ($order->getRestaurant() === $this) {
                $order->setRestaurant(null);
            }
        }

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
            $meal->setRestaurant($this);
        }

        return $this;
    }

    public function removeMeal(Meal $meal): static
    {
        if ($this->meals->removeElement($meal)) {
            // set the owning side to null (unless already changed)
            if ($meal->getRestaurant() === $this) {
                $meal->setRestaurant(null);
            }
        }

        return $this;
    }

    /**
     * @return Collection<int, MealPlan>
     */
    public function getMealPlans(): Collection
    {
        return $this->mealPlans;
    }

    public function addMealPlan(MealPlan $mealPlan): static
    {
        if (!$this->mealPlans->contains($mealPlan)) {
            $this->mealPlans->add($mealPlan);
            $mealPlan->setRestaurant($this);
        }

        return $this;
    }

    public function removeMealPlan(MealPlan $mealPlan): static
    {
        if ($this->mealPlans->removeElement($mealPlan)) {
            // set the owning side to null (unless already changed)
            if ($mealPlan->getRestaurant() === $this) {
                $mealPlan->setRestaurant(null);
            }
        }

        return $this;
    }
}
