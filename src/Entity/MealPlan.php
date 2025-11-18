<?php

namespace App\Entity;

use ApiPlatform\Metadata\ApiProperty;
use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\Delete;
use ApiPlatform\Metadata\Get;
use ApiPlatform\Metadata\GetCollection;
use ApiPlatform\Metadata\Link;
use ApiPlatform\Metadata\Patch;
use ApiPlatform\Metadata\Post;
use ApiPlatform\OpenApi\Model\Operation;
use ApiPlatform\OpenApi\Model\RequestBody;
use App\ApiResource\ImageUploadableInterface;
use App\Controller\ImageUploadController;
use App\Repository\MealPlanRepository;
use App\State\RestaurantOwnedStateProcessor;
use ArrayObject;
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

#[ApiResource(
    operations: [
        // ----------------------------------------
        // READ Operations (Public)
        // ----------------------------------------
        new Get(
            normalizationContext: ['groups' => ['meal_plan:read_detailed']]
        ),
        new GetCollection(
            uriTemplate: '/restaurants/{restaurantId}/meal_plans',
            uriVariables: [
                'restaurantId' => new Link(
                    toProperty: 'restaurant',
                    fromClass: MealPlan::class
                ),
            ],
            normalizationContext: ['groups' => ['meal_plan:read']]
        ),

        // ----------------------------------------
        // WRITE Operations (Secured)
        // ----------------------------------------
        new Post(
            denormalizationContext: ['groups' => ['meal_plan:write']],
            security: "is_granted('ROLE_RESTAURANT')",
            processor: RestaurantOwnedStateProcessor::class
        ),
        new Patch(
            denormalizationContext: ['groups' => ['meal_plan:write']],
            security: "is_granted('ROLE_ADMIN') or object.getRestaurant().isOwnedBy(user)"
        ),
        new Delete(
            security: "is_granted('ROLE_ADMIN') or object.getRestaurant().isOwnedBy(user)"
        ),

        // ----------------------------------------
        // FILE UPLOAD Operation (Custom)
        // ----------------------------------------
        new Post(
            uriTemplate: '/meal_plans/{id}/image',
            controller: ImageUploadController::class,
            openapi: new Operation(
                summary: 'Uploads an image for a MealPlan',
                requestBody: new RequestBody(
                    content: new ArrayObject([
                        'multipart/form-data' => [
                            'schema' => [
                                'type' => 'object',
                                'properties' => [
                                    'imageFile' => [
                                        'type' => 'string',
                                        'format' => 'binary',
                                        'description' => 'The image file to upload'
                                    ]
                                ]
                            ]
                        ]
                    ])
                )
            ),
            security: "is_granted('ROLE_ADMIN') or object.getRestaurant().isOwnedBy(user)",
            deserialize: false
        )
    ],
    // Default normalization for collections (less data)
    normalizationContext: ['groups' => ['meal_plan:read']],
)]
#[ORM\Entity(repositoryClass: MealPlanRepository::class)]
#[Vich\Uploadable]
class MealPlan implements ImageUploadableInterface
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    #[Groups(['meal_plan:read', 'meal_plan:read_detailed'])]
    private ?int $id = null;

    #[ORM\ManyToMany(targetEntity: Meal::class, inversedBy: 'mealPlans')]
    #[Groups(['meal_plan:read_detailed', 'meal_plan:write'])] // Detailed view only
    private Collection $meals;

    #[ORM\ManyToMany(targetEntity: Category::class, inversedBy: 'mealPlans')]
    #[Groups(['meal_plan:read', 'meal_plan:write'])]
    private Collection $categories;

    #[ORM\Column(length: 50)]
    #[Assert\NotBlank]
    #[Assert\Length(max: 50)]
    #[Groups(['meal_plan:read', 'meal_plan:read_detailed', 'meal_plan:write'])]
    private ?string $name = null;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    #[Assert\Length(max: 1000)]
    #[Groups(['meal_plan:read', 'meal_plan:read_detailed', 'meal_plan:write'])]
    private ?string $description = null;

    #[Vich\UploadableField(mapping: 'meal_plan_image', fileNameProperty: 'imagePath')]
    #[Assert\File(
        maxSize: '2M',
        mimeTypes: ['image/jpeg', 'image/png', 'image/webp'],
    )]
    private ?File $imageFile = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $imagePath = null;

    #[ORM\ManyToOne(inversedBy: 'mealPlans')]
    private ?Restaurant $restaurant = null;

    #[ORM\Column(type: Types::DATETIME_MUTABLE, nullable: true)]
    private ?DateTimeInterface $updatedAt;

    public function __construct()
    {
        $this->meals = new ArrayCollection();
        $this->categories = new ArrayCollection();
        $this->updatedAt = new DateTimeImmutable();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    #[Groups(['meal_plan:read', 'meal_plan:read_detailed'])]
    public function getImageUrl(): ?string
    {
        if ($this->getImagePath()) {
            // 💡 You might need to prefix this with your base URL (e.g., from an env var)
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
     * @return Collection<int, Category>
     */
    public function getCategories(): Collection
    {
        return $this->categories;
    }

    public function addCategory(Category $category): static
    {
        if (!$this->categories->contains($category)) {
            $this->categories->add($category);
        }

        return $this;
    }

    public function removeCategory(Category $category): static
    {
        $this->categories->removeElement($category);

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
    #[Groups(['meal_plan:read', 'meal_plan:read_detailed'])]
    public function getPrice(): ?int
    {
        $price = 0;
        foreach ($this->meals as $meal) {
            $price += $meal->getPrice();
        }
        return $price;
    }
}