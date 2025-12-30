<?php

namespace App\Entity;

use ApiPlatform\Metadata\ApiProperty;
use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\GraphQl\DeleteMutation;
use ApiPlatform\Metadata\GraphQl\Mutation;
use ApiPlatform\Metadata\GraphQl\Query;
use ApiPlatform\Metadata\GraphQl\QueryCollection;
use App\ApiResource\ImageUploadableInterface;
use App\Repository\MealPlanRepository;
use DateTimeImmutable;
use DateTimeInterface;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\HttpFoundation\File\File;
use Symfony\Component\Serializer\Attribute\Groups;
use Symfony\Component\Validator\Constraints as Assert;
use Vich\UploaderBundle\Mapping\Attribute as Vich;

use ApiPlatform\Metadata\ApiFilter;
use ApiPlatform\Doctrine\Orm\Filter\RangeFilter;
use ApiPlatform\Doctrine\Orm\Filter\SearchFilter;
use App\Filter\MealPlanSearchFilter;

#[ORM\Entity(repositoryClass: MealPlanRepository::class)]
#[ORM\HasLifecycleCallbacks]
#[Vich\Uploadable]
#[ApiFilter(MealPlanSearchFilter::class)]
#[ApiFilter(SearchFilter::class, properties: ['dietCategories.name' => 'exact'])]
#[ApiFilter(RangeFilter::class, properties: ['price', 'calories', 'protein', 'fat', 'carbs'])]
#[ApiResource(order: ['id' => 'DESC'])]
#[ApiResource(
    operations: [],
    normalizationContext: ['groups' => ['read']],
    denormalizationContext: ['groups' => ['create', 'update']],
    graphQlOperations: [
        new QueryCollection(security: "is_granted('PUBLIC_ACCESS')"),
        new Query(security: "is_granted('PUBLIC_ACCESS') and (object.getOwner() == null or object.getOwner() == user)"),
        new Mutation(
            security: "is_granted('ROLE_ADMIN') or (is_granted('ROLE_RESTAURANT') and object.getRestaurant().getOwner() == user) or is_granted('ROLE_CUSTOMER')",
            securityPostDenormalize: "is_granted('ROLE_ADMIN') or (is_granted('ROLE_RESTAURANT') and object.getRestaurant().getOwner() == user) or (is_granted('ROLE_CUSTOMER') and object.getOwner() == user)",
            name: 'create'
        ),
        new Mutation(security: "is_granted('ROLE_ADMIN') or (is_granted('ROLE_RESTAURANT') and object.getRestaurant().getOwner() == user) or (is_granted('ROLE_CUSTOMER') and object.getOwner() == user)", name: 'update'),
        new DeleteMutation(security: "is_granted('ROLE_ADMIN') or (is_granted('ROLE_RESTAURANT') and object.getRestaurant().getOwner() == user) or (is_granted('ROLE_CUSTOMER') and object.getOwner() == user)", name: 'delete')
    ],
)]
class MealPlan implements ImageUploadableInterface
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\ManyToMany(targetEntity: Meal::class, inversedBy: 'mealPlans')]
    #[ApiProperty(readableLink: true, writableLink: false)]
    #[Groups(['read', 'create', 'update'])]
    #[Assert\Count(
        min: 1,
        max: 5,
        minMessage: 'You must select at least one meal.',
        maxMessage: 'You cannot select more than 5 meals in a plan.'
    )]
    private Collection $meals;

    #[ORM\ManyToMany(targetEntity: DietCategory::class, inversedBy: 'mealPlans')]
    #[ApiProperty(readableLink: true, writableLink: false)]
    #[Groups(['read', 'create', 'update'])]
    private Collection $dietCategories;

    #[ORM\Column(length: 50)]
    #[Assert\NotBlank]
    #[Assert\Length(max: 50)]
    #[Groups(['read', 'create', 'update'])]
    private ?string $name = null;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    #[Assert\Length(max: 1000)]
    #[Groups(['read', 'create', 'update'])]
    private ?string $description = null;

    #[ORM\Column(type: Types::INTEGER, nullable: true)]
    #[Groups(['read'])]
    private ?int $price = null;

    #[ORM\Column(type: Types::FLOAT, nullable: true)]
    #[Groups(['read'])]
    private ?float $calories = null;

    #[ORM\Column(type: Types::FLOAT, nullable: true)]
    #[Groups(['read'])]
    private ?float $protein = null;

    #[ORM\Column(type: Types::FLOAT, nullable: true)]
    #[Groups(['read'])]
    private ?float $fat = null;

    #[ORM\Column(type: Types::FLOAT, nullable: true)]
    #[Groups(['read'])]
    private ?float $carbs = null;

    #[ORM\ManyToOne(inversedBy: 'mealPlans')]
    #[ORM\JoinColumn(onDelete: 'CASCADE')]
    #[ApiProperty(readableLink: true, writableLink: false)]
    #[Groups(['read', 'create'])]
    private ?Restaurant $restaurant = null;

    #[Vich\UploadableField(mapping: 'meal_plan_image', fileNameProperty: 'imagePath')]
    #[Assert\File(
        maxSize: '2M',
        mimeTypes: ['image/jpeg', 'image/png', 'image/webp'],
    )]
    private ?File $imageFile = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $imagePath = null;

    #[ORM\Column(type: Types::DATETIME_MUTABLE, nullable: true)]
    private ?DateTimeInterface $updatedAt;

    #[ORM\ManyToOne(inversedBy: 'customMealPlans')]
    #[ORM\JoinColumn(nullable: true, onDelete: 'CASCADE')]
    #[ApiProperty(readableLink: true, writableLink: false)]
    #[Groups(['read', 'create'])]
    private ?User $owner = null;

    public function __construct()
    {
        $this->meals = new ArrayCollection();
        $this->dietCategories = new ArrayCollection();
        $this->updatedAt = new DateTimeImmutable();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    #[Groups(['read'])]
    public function getImageUrl(): ?string
    {
        if ($this->getImagePath()) {
            return '/images/meal_plans/' . $this->getImagePath();
        }

        if ($this->meals->count() > 0) {
            $firstMeal = $this->meals->first();
            if ($firstMeal instanceof Meal) {
                return $firstMeal->getImageUrl();
            }
        }

        return null;
    }

    public function getImageFile(): ?File
    {
        return $this->imageFile;
    }

    public function setImageFile(?File $imageFile): static
    {
        $this->imageFile = $imageFile;
        if (null !== $imageFile) {
            $this->updatedAt = new DateTimeImmutable();
        }

        return $this;
    }

    public function getImagePath(): ?string
    {
        return $this->imagePath;
    }

    public function setImagePath(?string $imagePath): static
    {
        $this->imagePath = $imagePath;

        return $this;
    }

    /**
     * @return Collection<int, DietCategory>
     */
    public function getDietCategories(): Collection
    {
        return $this->dietCategories;
    }

    public function addDietCategory(DietCategory $dietCategory): static
    {
        if (!$this->dietCategories->contains($dietCategory)) {
            $this->dietCategories->add($dietCategory);
        }

        return $this;
    }

    public function removeDietCategory(DietCategory $dietCategory): static
    {
        $this->dietCategories->removeElement($dietCategory);

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

    public function clearMeals(): static
    {
        $this->meals->clear();

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

    public function getDescription(): ?string
    {
        return $this->description;
    }

    public function setDescription(?string $description): static
    {
        $this->description = $description;

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

    public function getPrice(): ?int
    {
        return $this->price;
    }

    public function setPrice(?int $price): self
    {
        $this->price = $price;
        return $this;
    }

    public function getCalories(): ?float
    {
        return $this->calories;
    }

    public function setCalories(?float $calories): self
    {
        $this->calories = $calories;
        return $this;
    }

    public function getProtein(): ?float
    {
        return $this->protein;
    }

    public function setProtein(?float $protein): self
    {
        $this->protein = $protein;
        return $this;
    }

    public function getFat(): ?float
    {
        return $this->fat;
    }

    public function setFat(?float $fat): self
    {
        $this->fat = $fat;
        return $this;
    }

    public function getCarbs(): ?float
    {
        return $this->carbs;
    }

    public function setCarbs(?float $carbs): self
    {
        $this->carbs = $carbs;
        return $this;
    }

    #[ORM\PrePersist]
    #[ORM\PreUpdate]
    public function calculateTotals(): void
    {
        $this->price = 0;
        $this->calories = 0.0;
        $this->protein = 0.0;
        $this->fat = 0.0;
        $this->carbs = 0.0;

        foreach ($this->meals as $meal) {
            $this->price += $meal->getPrice();
            $this->calories += $meal->getCalories();
            $this->protein += $meal->getProtein();
            $this->fat += $meal->getFat();
            $this->carbs += $meal->getCarbs();
        }
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
}
