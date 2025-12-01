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
use Symfony\Component\Serializer\Annotation\Groups;
use Symfony\Component\Validator\Constraints as Assert;
use Vich\UploaderBundle\Mapping\Annotation as Vich;

#[ORM\Entity(repositoryClass: MealPlanRepository::class)]
#[Vich\Uploadable]
#[ApiResource(
    operations: [],
    normalizationContext: ['groups' => ['read']],
    denormalizationContext: ['groups' => ['create', 'update']],
    graphQlOperations: [
        new QueryCollection(security: "is_granted('PUBLIC_ACCESS')"),
        new Query(security: "is_granted('PUBLIC_ACCESS')"),
        new Mutation(security: "is_granted('ROLE_ADMIN') or (is_granted('ROLE_RESTAURANT') and object.getRestaurant() == user.getRestaurant())", name: 'create'),
        new Mutation(security: "is_granted('ROLE_ADMIN') or (is_granted('ROLE_RESTAURANT') and object.getRestaurant() == user.getRestaurant())", name: 'update'),
        new DeleteMutation(security: "is_granted('ROLE_ADMIN') or (is_granted('ROLE_RESTAURANT') and object.getRestaurant() == user.getRestaurant())", name: 'delete')
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

    #[ORM\ManyToOne(inversedBy: 'mealPlans')]
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

    /**
     * Expose the calculated price.
     */
    #[ApiProperty(
        description: 'The total price of the meal plan, calculated from the sum of the prices of its meals.',
        writable: false,
        schema: [
            'type' => 'number',
            'format' => 'float',
            'example' => 123.45,
        ]
    )]
    #[Groups(['read'])]
    public function getPrice(): ?int
    {
        $price = 0;
        foreach ($this->meals as $meal) {
            $price += $meal->getPrice();
        }
        return $price;
    }
}
