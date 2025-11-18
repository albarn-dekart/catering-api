<?php
namespace App\Entity;

use ApiPlatform\Doctrine\Orm\Filter\OrderFilter;
use ApiPlatform\Doctrine\Orm\Filter\SearchFilter;
use ApiPlatform\Metadata\ApiFilter;
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
use App\Repository\MealRepository;
use App\State\RestaurantOwnedStateProcessor; // 💡 Use the custom processor
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
        // Public Read Operations
        // ----------------------------------------
        new GetCollection(
            normalizationContext: ['groups' => ['meal:read']]
        ),
        new Get(
            normalizationContext: ['groups' => ['meal:read:detailed']]
        ),
        new GetCollection(
            uriTemplate: '/restaurants/{restaurantId}/meals',
            uriVariables: [
                'restaurantId' => new Link(
                    toProperty: 'restaurant',
                    fromClass: Meal::class
                ),
            ],
            normalizationContext: ['groups' => ['meal:read:detailed']]
        ),
        new GetCollection(
            uriTemplate: '/meal_plans/{mealPlanId}/meals',
            uriVariables: [
                'mealPlanId' => new Link(
                    toProperty: 'mealPlans',
                    fromClass: Meal::class
                ),
            ],
            normalizationContext: ['groups' => ['meal:read:detailed']]
        ),

        // ----------------------------------------
        // Owner/Admin Write Operations
        // ----------------------------------------
        new Post(
            denormalizationContext: ['groups' => ['meal:write']],
            security: "is_granted('ROLE_ADMIN') or is_granted('ROLE_RESTAURANT')",
            processor: RestaurantOwnedStateProcessor::class // 💡 Auto-set restaurant
        ),
        new Patch(
        // Check if Admin OR if the object's restaurant is owned by the user
            denormalizationContext: ['groups' => ['meal:write']],
            security: "is_granted('ROLE_ADMIN') or object.getRestaurant().isOwnedBy(user)",
        ),
        new Delete(
            security: "is_granted('ROLE_ADMIN') or object.getRestaurant().isOwnedBy(user)"
        ),

        // ----------------------------------------
        // FILE UPLOAD Operation (Custom)
        // ----------------------------------------
        new Post(
            uriTemplate: '/meals/{id}/image',
            controller: ImageUploadController::class,
            openapi: new Operation(
                summary: 'Uploads an image for a Meal',
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
    normalizationContext: ['groups' => ['meal:read']]
)]
// Add filters for public browsing and sorting
#[ApiFilter(SearchFilter::class, properties: ['name' => 'partial', 'restaurant' => 'exact'])]
#[ApiFilter(OrderFilter::class, properties: ['price', 'calories', 'name'])]
#[ORM\Entity(repositoryClass: MealRepository::class)]
#[Vich\Uploadable]
class Meal implements ImageUploadableInterface
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    #[Groups(['meal:read', 'meal:read:detailed', 'meal_plan:read_detailed'])]
    private ?int $id = null;

    #[ORM\Column(length: 50)]
    #[Assert\NotBlank]
    #[Groups(['meal:read', 'meal:read:detailed', 'meal:write', 'meal_plan:read_detailed'])]
    private ?string $name = null;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    #[Groups(['meal:read', 'meal:read:detailed', 'meal:write', 'meal_plan:read_detailed'])]
    private ?string $description = null;

    // ... (Nutritional fields: calories, protein, fat, carbs) ...
    #[ORM\Column(type: Types::FLOAT)]
    #[Groups(['meal:read:detailed', 'meal:write', 'meal_plan:read_detailed'])]
    private ?float $calories = null;

    #[ORM\Column(type: Types::FLOAT)]
    #[Groups(['meal:read:detailed', 'meal:write', 'meal_plan:read_detailed'])]
    private ?float $protein = null;

    #[ORM\Column(type: Types::FLOAT)]
    #[Groups(['meal:read:detailed', 'meal:write', 'meal_plan:read_detailed'])]
    private ?float $fat = null;

    #[ORM\Column(type: Types::FLOAT)]
    #[Groups(['meal:read:detailed', 'meal:write', 'meal_plan:read_detailed'])]
    private ?float $carbs = null;

    #[ORM\Column]
    #[Assert\GreaterThanOrEqual(value: 0)]
    #[Groups(['meal:read', 'meal:read:detailed', 'meal:write', 'meal_plan:read_detailed'])]
    private ?int $price = null; //

    #[ORM\ManyToOne(inversedBy: 'meals')]
    #[ORM\JoinColumn(nullable: false)]
    #[Groups(['meal:read', 'meal:read:detailed', 'meal:write'])]
    private ?Restaurant $restaurant = null;

    #[ORM\ManyToMany(targetEntity: MealPlan::class, mappedBy: 'meals')]
    #[Groups(['meal:read:detailed'])]
    private Collection $mealPlans;

    // --- Image Upload Fields ---
    #[Vich\UploadableField(mapping: 'meal_image', fileNameProperty: 'imagePath')]
    private ?File $imageFile = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $imagePath = null;

    #[ORM\Column(type: Types::DATETIME_MUTABLE, nullable: true)]
    private ?DateTimeInterface $updatedAt;

    public function __construct()
    {
        $this->mealPlans = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
    }
    #[Groups(['meal:read', 'meal:read:detailed', 'meal_plan:read_detailed'])]
    public function getImageUrl(): ?string
    {
        if ($this->getImagePath()) {
            return '/images/meals/' . $this->getImagePath();
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

    public function getPrice(): ?int
    {
        return $this->price;
    }

    public function setPrice(int $price): static
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
}