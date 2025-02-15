<?php

namespace App\Entity;

use App\Repository\MealRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: MealRepository::class)]
class Meal
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 50)]
    private ?string $name = null;

    #[ORM\ManyToMany(targetEntity: Category::class, mappedBy: 'meals')]
    private Collection $categories;

    #[ORM\ManyToMany(targetEntity: MealPlan::class, mappedBy: 'meals')]
    private Collection $mealPlans;

    #[ORM\Column(type: Types::DECIMAL, precision: 5, scale: 2)]
    private ?string $calories = null;

    #[ORM\Column(type: Types::DECIMAL, precision: 5, scale: 2)]
    private ?string $protein = null;

    #[ORM\Column(type: Types::DECIMAL, precision: 5, scale: 2)]
    private ?string $fat = null;

    #[ORM\Column(type: Types::DECIMAL, precision: 5, scale: 2)]
    private ?string $carbs = null;

    #[ORM\Column(type: Types::DECIMAL, precision: 5, scale: 2)]
    private ?string $price = null;

    public function __construct()
    {
        $this->categories = new ArrayCollection();
        $this->mealPlans = new ArrayCollection();
    }

    public function data(): array
    {
        $categories = [];
        foreach ($this->categories as $category) {
            $categories[] = ['id' => $category->getId(), 'name' => $category->getName()];
        }

        return [
            'id' => $this->getId(),
            'name' => $this->getName(),
            'categories' => $categories,
            'calories' => $this->getCalories(),
            'protein' => $this->getProtein(),
            'fat' => $this->getFat(),
            'carbs' => $this->getCarbs(),
            'price' => $this->getPrice(),
        ];
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

    public function getCalories(): ?string
    {
        return $this->calories;
    }

    public function setCalories(string $calories): static
    {
        $this->calories = $calories;

        return $this;
    }

    public function getProtein(): ?string
    {
        return $this->protein;
    }

    public function setProtein(string $protein): static
    {
        $this->protein = $protein;

        return $this;
    }

    public function getFat(): ?string
    {
        return $this->fat;
    }

    public function setFat(string $fat): static
    {
        $this->fat = $fat;

        return $this;
    }

    public function getCarbs(): ?string
    {
        return $this->carbs;
    }

    public function setCarbs(string $carbs): static
    {
        $this->carbs = $carbs;

        return $this;
    }

    public function getPrice(): ?string
    {
        return $this->price;
    }

    public function setPrice(string $price): static
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
}
