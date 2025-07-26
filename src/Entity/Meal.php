<?php

namespace App\Entity;

use App\Repository\MealRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Serializer\Annotation\Groups;
use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\Get;
use ApiPlatform\Metadata\GetCollection;
use ApiPlatform\Metadata\Post;
use ApiPlatform\Metadata\Put;
use ApiPlatform\Metadata\Patch;
use ApiPlatform\Metadata\Delete;

#[ApiResource(
    operations: [
        new GetCollection(),
        new Post(security: "is_granted('ROLE_RESTAURANT')"),
        new Get(),
        new Put(security: "is_granted('ROLE_RESTAURANT') and object.getRestaurant().isOwnedBy(user)"),
        new Patch(security: "is_granted('ROLE_RESTAURANT') and object.getRestaurant().isOwnedBy(user)"),
        new Delete(security: "is_granted('ROLE_RESTAURANT') and object.getRestaurant().isOwnedBy(user)"),
    ],
    normalizationContext: ['groups' => ['meal:read']],
    denormalizationContext: ['groups' => ['meal:write']]
)]
#[ORM\Entity(repositoryClass: MealRepository::class)]
class Meal
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 50)]
    #[Groups(['meal:read', 'meal:write', 'meal_plan:read'])]
    private ?string $name = null;

    #[ORM\ManyToMany(targetEntity: Category::class, mappedBy: 'meals')]
    #[Groups(['meal:read', 'meal:write'])]
    private Collection $categories;

    #[ORM\ManyToMany(targetEntity: MealPlan::class, mappedBy: 'meals')]
    private Collection $mealPlans;

    #[ORM\Column(type: Types::FLOAT)]
    #[Groups(['meal:read', 'meal:write', 'meal_plan:read'])]
    private ?float $calories = null;

    #[ORM\Column(type: Types::FLOAT)]
    #[Groups(['meal:read', 'meal:write', 'meal_plan:read'])]
    private ?float $protein = null;

    #[ORM\Column(type: Types::FLOAT)]
    #[Groups(['meal:read', 'meal:write', 'meal:plan:read'])]
    private ?float $fat = null;

    #[ORM\Column(type: Types::FLOAT)]
    #[Groups(['meal:read', 'meal:write', 'meal_plan:read'])]
    private ?float $carbs = null;

    #[ORM\Column(type: Types::FLOAT)]
    #[Groups(['meal:read', 'meal:write', 'meal_plan:read'])]
    private ?float $price = null;

    #[ORM\ManyToOne(inversedBy: 'meals')]
    #[ORM\JoinColumn(nullable: false)]
    #[Groups(['meal:read', 'meal:write'])]
    private ?Restaurant $restaurant = null;

    public function __construct()
    {
        $this->categories = new ArrayCollection();
        $this->mealPlans = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getCategories(): Collection
    {
        return $this->categories;
    }

    public function addCategory(Category $category): static
    {
        if (!$this->categories->contains($category)) {
            $this->categories->add($category);
            $category->addMeal($this);
        }

        return $this;
    }

    public function removeCategory(Category $category): static
    {
        if ($this->categories->removeElement($category)) {
            $category->removeMeal($this);
        }

        return $this;
    }

    public function clearCategories(): static
    {
        foreach ($this->categories as $category) {
            $category->removeMeal($this);
        }
        $this->categories->clear();

        return $this;
    }

    public function getCalories(): ?float
    {
        return $this->calories;
    }

    public function setCalories(float $calories): static
    {
        $this->calories = $calories;

        return $this;
    }

    public function getProtein(): ?float
    {
        return $this->protein;
    }

    public function setProtein(float $protein): static
    {
        $this->protein = $protein;

        return $this;
    }

    public function getFat(): ?float
    {
        return $this->fat;
    }

    public function setFat(float $fat): static
    {
        $this->fat = $fat;

        return $this;
    }

    public function getCarbs(): ?float
    {
        return $this->carbs;
    }

    public function setCarbs(float $carbs): static
    {
        $this->carbs = $carbs;

        return $this;
    }

    public function getPrice(): ?float
    {
        return $this->price;
    }

    public function setPrice(float $price): static
    {
        $this->price = $price;

        return $this;
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

    public function getRestaurant(): ?Restaurant
    {
        return $this->restaurant;
    }

    public function setRestaurant(?Restaurant $restaurant): static
    {
        $this->restaurant = $restaurant;

        return $this;
    }
}
