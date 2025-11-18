<?php

namespace App\Entity;

use ApiPlatform\Doctrine\Orm\Filter\SearchFilter;
use ApiPlatform\Metadata\ApiFilter;
use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\Delete;
use ApiPlatform\Metadata\Get;
use ApiPlatform\Metadata\GetCollection;
use ApiPlatform\Metadata\Patch;
use ApiPlatform\Metadata\Post;
use App\Repository\CategoryRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Serializer\Annotation\Groups;
use Symfony\Component\Validator\Constraints as Assert;

#[ApiResource(
    operations: [
        // ----------------------------------------
        // Public Read Operations (Anyone can view)
        // ----------------------------------------
        new GetCollection(
            normalizationContext: ['groups' => ['category:read']]
        ),
        new Get(
            normalizationContext: ['groups' => ['category:read:detailed']]
        ),

        // ----------------------------------------
        // Admin Write Operations (Only Admin can manage)
        // ----------------------------------------
        new Post(
            denormalizationContext: ['groups' => ['category:write']],
            security: "is_granted('ROLE_ADMIN')"
        ),
        new Patch(
            denormalizationContext: ['groups' => ['category:write']],
            security: "is_granted('ROLE_ADMIN')"
        ),
        new Delete(
            security: "is_granted('ROLE_ADMIN')"
        ),
    ],
    // Default visibility
    normalizationContext: ['groups' => ['category:read']]
)]
// Allows filtering on the name
#[ApiFilter(SearchFilter::class, properties: ['name' => 'partial'])]
#[ORM\Entity(repositoryClass: CategoryRepository::class)]
class Category
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    #[Groups(['category:read', 'category:read:detailed', 'restaurant:read', 'meal_plan:read'])]
    private ?int $id = null;

    #[ORM\Column(length: 50)]
    #[Assert\NotBlank]
    #[Assert\Length(max: 50)]
    #[Groups(['category:read', 'category:read:detailed', 'restaurant:read', 'meal_plan:read', 'category:write'])]
    private ?string $name = null;

    #[ORM\ManyToMany(targetEntity: Restaurant::class, mappedBy: 'categories')]
    // Read only via detailed category view
    #[Groups(['category:read:detailed'])]
    private Collection $restaurants;

    #[ORM\ManyToMany(targetEntity: MealPlan::class, mappedBy: 'categories')]
    // Read only via detailed category view
    #[Groups(['category:read:detailed'])]
    private Collection $mealPlans;

    public function __construct()
    {
        $this->restaurants = new ArrayCollection();
        $this->mealPlans = new ArrayCollection();
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

    /**
     * @return Collection<int, Restaurant>
     */
    public function getRestaurants(): Collection
    {
        return $this->restaurants;
    }

    public function addRestaurant(Restaurant $restaurant): static
    {
        if (!$this->restaurants->contains($restaurant)) {
            $this->restaurants->add($restaurant);
            $restaurant->addCategory($this);
        }

        return $this;
    }

    public function removeRestaurant(Restaurant $restaurant): static
    {
        if ($this->restaurants->removeElement($restaurant)) {
            $restaurant->removeCategory($this);
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
            $mealPlan->addCategory($this);
        }

        return $this;
    }

    public function removeMealPlan(MealPlan $mealPlan): static
    {
        if ($this->mealPlans->removeElement($mealPlan)) {
            $mealPlan->removeCategory($this);
        }

        return $this;
    }
}
