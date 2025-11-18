<?php

namespace App\Entity;

use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\Delete;
use ApiPlatform\Metadata\Get;
use ApiPlatform\Metadata\GetCollection;
use ApiPlatform\Metadata\Patch;
use ApiPlatform\Metadata\Post;
use ApiPlatform\OpenApi\Model\Operation;
use ApiPlatform\OpenApi\Model\RequestBody;
use App\ApiResource\ImageUploadableInterface;
use App\Controller\ImageUploadController;
use App\Repository\RestaurantRepository;
use ArrayObject;
use DateTimeImmutable;
use DateTimeInterface;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\HttpFoundation\File\File;
use Symfony\Component\Serializer\Annotation\Groups; // Use Annotation\Groups
use Symfony\Component\Validator\Constraints as Assert;
use Vich\UploaderBundle\Mapping\Annotation as Vich;

#[ApiResource(
    operations: [
        // ----------------------------------------
        // 📖 Public/Customer Read Operations
        // ----------------------------------------
        new GetCollection(
            normalizationContext: ['groups' => ['restaurant:read']]
        ),
        new Get(
            normalizationContext: ['groups' => ['restaurant:read:detailed']]
        ),

        // ----------------------------------------
        // ✍️ Owner/Admin Write Operations
        // ----------------------------------------
        // POST operation is omitted here as it's handled by User registration (/register/owner)

        new Patch(
        // Only Admin OR the Owner can update the restaurant
            normalizationContext: ['groups' => ['restaurant:read:detailed']],
            denormalizationContext: ['groups' => ['restaurant:write']],
            security: "is_granted('ROLE_ADMIN') or object.isOwnedBy(user)"
        ),

        new Delete(
        // Only Admin can delete a restaurant
            security: "is_granted('ROLE_ADMIN')"
        ),

        // ----------------------------------------
        // 🖼️ FILE UPLOAD Operation (Custom)
        // ----------------------------------------
        new Post(
            uriTemplate: '/restaurants/{id}/image',
            controller: ImageUploadController::class,
            openapi: new Operation(
                summary: 'Uploads an image for a Restaurant',
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
            // Only Admin OR the Owner can upload an image
            security: "is_granted('ROLE_ADMIN') or object.isOwnedBy(user)",
            deserialize: false
        )
    ],
    // Default configuration for collections
    normalizationContext: ['groups' => ['restaurant:read']],
)]
#[ORM\Entity(repositoryClass: RestaurantRepository::class)]
#[Vich\Uploadable]
class Restaurant implements ImageUploadableInterface
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    #[Groups(['restaurant:read', 'restaurant:read:detailed', 'user:read:self', 'order:read', 'order:write', 'meal_plan:read'])]
    private ?int $id = null;

    #[ORM\Column(length: 50)]
    #[Assert\NotBlank]
    #[Assert\Length(max: 50)]
    #[Groups(['restaurant:read', 'restaurant:read:detailed', 'restaurant:write', 'user:create:restaurant', 'order:read', 'meal_plan:read'])]
    private ?string $name = null;

    #[Vich\UploadableField(mapping: 'restaurant_image', fileNameProperty: 'imagePath')]
    #[Assert\File(
        maxSize: '2M',
        mimeTypes: ['image/jpeg', 'image/png', 'image/webp'],
    )]
    private ?File $imageFile = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $imagePath = null;

    // 💡 Add description field for detailed read and write
    #[ORM\Column(type: Types::TEXT, nullable: true)]
    #[Groups(['restaurant:read', 'restaurant:read:detailed', 'restaurant:write'])]
    private ?string $description = null;

    #[ORM\OneToOne(inversedBy: 'restaurant', cascade: ['persist', 'remove'])]
    #[ORM\JoinColumn(nullable: false)]
    // Owner is included in detailed read and required on registration POST
    #[Groups(['restaurant:read:detailed', 'user:read:self', 'user:create:restaurant'])]
    private ?User $owner = null;

    #[ORM\ManyToMany(targetEntity: Category::class, inversedBy: 'restaurants')]
    #[Groups(['restaurant:read', 'restaurant:read:detailed', 'restaurant:write'])]
    private Collection $categories;

    #[ORM\OneToMany(targetEntity: Meal::class, mappedBy: 'restaurant', orphanRemoval: true)]
    #[Groups(['restaurant:read:detailed'])] // Expose related meals on detailed view
    private Collection $meals;

    #[ORM\OneToMany(targetEntity: MealPlan::class, mappedBy: 'restaurant', orphanRemoval: true)]
    #[Groups(['restaurant:read:detailed'])] // Expose related meal plans on detailed view
    private Collection $mealPlans;

    #[ORM\OneToMany(targetEntity: Order::class, mappedBy: 'restaurant', orphanRemoval: true)]
    private Collection $orders; // Only accessible via separate Order endpoints

    #[ORM\Column(type: Types::DATETIME_MUTABLE, nullable: true)]
    private ?DateTimeInterface $updatedAt;

    public function __construct()
    {
        $this->categories = new ArrayCollection();
        $this->meals = new ArrayCollection();
        $this->mealPlans = new ArrayCollection();
        $this->orders = new ArrayCollection();
        $this->updatedAt = new DateTimeImmutable();
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

    public function setOwner(User $owner): static
    {
        $this->owner = $owner;

        return $this;
    }

    /**
     * Helper method used by API Platform security expressions.
     */
    public function isOwnedBy(User $user): bool
    {
        return $this->owner === $user;
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

    // --- Image Upload Interface Methods ---

    public function getImageFile(): ?File
    {
        return $this->imageFile;
    }

    public function setImageFile(?File $imageFile = null): static
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

    #[Groups(['restaurant:read', 'restaurant:read:detailed'])]
    public function getImageUrl(): ?string
    {
        if ($this->getImagePath()) {
            // 💡 Prefix this with your base URL if necessary
            return '/images/restaurants/' . $this->getImagePath();
        }
        return null;
    }

    public function setImagePath(?string $imagePath): static
    {
        $this->imagePath = $imagePath;

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
