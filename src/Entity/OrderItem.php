<?php

namespace App\Entity;

use ApiPlatform\Metadata\ApiProperty;
use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\GraphQl\DeleteMutation;
use ApiPlatform\Metadata\GraphQl\Mutation;
use ApiPlatform\Metadata\GraphQl\Query;
use ApiPlatform\Metadata\GraphQl\QueryCollection;
use App\Repository\OrderItemRepository;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Serializer\Attribute\Groups;
use Symfony\Component\Validator\Constraints as Assert;

#[ORM\Entity(repositoryClass: OrderItemRepository::class)]
#[ApiResource(
    operations: [],
    normalizationContext: ['groups' => ['read']],
    graphQlOperations: [
        new QueryCollection(security: "is_granted('ROLE_ADMIN') or is_granted('ROLE_RESTAURANT') or is_granted('ROLE_CUSTOMER')"),
        new Query(security: "is_granted('ROLE_ADMIN') or object.getOrder().getCustomer() == user or object.getOrder().getRestaurant().getOwner() == user"),
        new Mutation(security: "is_granted('ROLE_ADMIN')", name: 'update'),
        new DeleteMutation(security: "is_granted('ROLE_ADMIN')", name: 'delete')
    ],
)]
class OrderItem
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\ManyToOne(inversedBy: 'orderItems')]
    #[ORM\JoinColumn(nullable: true, onDelete: 'CASCADE')]
    #[ApiProperty(readableLink: true)]
    #[Groups(['read'])]
    private ?Order $order = null;

    #[ORM\ManyToOne]
    #[ORM\JoinColumn(nullable: true, onDelete: 'SET NULL')]
    #[ApiProperty(readableLink: true, writableLink: false)]
    #[Groups(['read', 'create'])]
    private ?MealPlan $mealPlan = null;

    #[ORM\Column]
    #[Assert\NotNull]
    #[Assert\GreaterThanOrEqual(value: 1)]
    #[Groups(['read', 'create'])]
    private ?int $quantity = null;

    // Snapshot fields - store meal plan data at order time
    #[ORM\Column(length: 100, nullable: true)]
    #[Groups(['read'])]
    private ?string $mealPlanName = null;

    #[ORM\Column(nullable: true)]
    #[Groups(['read'])]
    private ?int $mealPlanPrice = null;

    #[ORM\Column(nullable: true)]
    #[Groups(['read'])]
    private ?float $mealPlanCalories = null;

    #[ORM\Column(nullable: true)]
    #[Groups(['read'])]
    private ?float $mealPlanProtein = null;

    #[ORM\Column(nullable: true)]
    #[Groups(['read'])]
    private ?float $mealPlanFat = null;

    #[ORM\Column(nullable: true)]
    #[Groups(['read'])]
    private ?float $mealPlanCarbs = null;

    public function getId(): ?int
    {
        return $this->id;
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

    public function getMealPlan(): ?MealPlan
    {
        return $this->mealPlan;
    }

    public function setMealPlan(?MealPlan $mealPlan): static
    {
        $this->mealPlan = $mealPlan;

        // Automatically populate snapshot fields when meal plan is set
        if ($mealPlan !== null) {
            $this->mealPlanName = $mealPlan->getName();
            $this->mealPlanPrice = $mealPlan->getPrice();
            $this->mealPlanCalories = $mealPlan->getCalories();
            $this->mealPlanProtein = $mealPlan->getProtein();
            $this->mealPlanFat = $mealPlan->getFat();
            $this->mealPlanCarbs = $mealPlan->getCarbs();
        }

        return $this;
    }

    public function getQuantity(): ?int
    {
        return $this->quantity;
    }

    public function setQuantity(int $quantity): static
    {
        $this->quantity = $quantity;

        return $this;
    }

    // Snapshot getters and setters
    public function getMealPlanName(): ?string
    {
        return $this->mealPlanName;
    }

    public function setMealPlanName(?string $mealPlanName): static
    {
        $this->mealPlanName = $mealPlanName;
        return $this;
    }

    public function getMealPlanPrice(): ?int
    {
        return $this->mealPlanPrice;
    }

    public function setMealPlanPrice(?int $mealPlanPrice): static
    {
        $this->mealPlanPrice = $mealPlanPrice;
        return $this;
    }

    public function getMealPlanCalories(): ?float
    {
        return $this->mealPlanCalories;
    }

    public function setMealPlanCalories(?float $mealPlanCalories): static
    {
        $this->mealPlanCalories = $mealPlanCalories;
        return $this;
    }

    public function getMealPlanProtein(): ?float
    {
        return $this->mealPlanProtein;
    }

    public function setMealPlanProtein(?float $mealPlanProtein): static
    {
        $this->mealPlanProtein = $mealPlanProtein;
        return $this;
    }

    public function getMealPlanFat(): ?float
    {
        return $this->mealPlanFat;
    }

    public function setMealPlanFat(?float $mealPlanFat): static
    {
        $this->mealPlanFat = $mealPlanFat;
        return $this;
    }

    public function getMealPlanCarbs(): ?float
    {
        return $this->mealPlanCarbs;
    }

    public function setMealPlanCarbs(?float $mealPlanCarbs): static
    {
        $this->mealPlanCarbs = $mealPlanCarbs;
        return $this;
    }
}
