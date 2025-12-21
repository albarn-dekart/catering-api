<?php

namespace App\Entity;

use ApiPlatform\Metadata\ApiProperty;
use ApiPlatform\Metadata\ApiResource;
use App\ApiResource\ImageUploadableInterface;
use ApiPlatform\Metadata\GraphQl\DeleteMutation;
use ApiPlatform\Metadata\GraphQl\Mutation;
use ApiPlatform\Metadata\GraphQl\Query;
use ApiPlatform\Metadata\GraphQl\QueryCollection;
use App\Repository\MealRepository;
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

use ApiPlatform\Metadata\ApiFilter;
use ApiPlatform\Doctrine\Orm\Filter\RangeFilter; // Added RangeFilter
use App\Filter\MealSearchFilter;

#[ORM\Entity(repositoryClass: MealRepository::class)]
#[Vich\Uploadable]
#[ApiFilter(MealSearchFilter::class)]
#[ApiFilter(RangeFilter::class, properties: ['price', 'calories', 'protein', 'fat', 'carbs'])]
#[ApiResource(order: ['id' => 'DESC'])]
#[ApiResource(
    operations: [],
    normalizationContext: ['groups' => ['read']],
    denormalizationContext: ['groups' => ['create', 'update']],
    graphQlOperations: [
        new QueryCollection(security: "is_granted('PUBLIC_ACCESS')"),
        new Query(security: "is_granted('PUBLIC_ACCESS')"),
        new Mutation(security: "is_granted('ROLE_ADMIN') or (is_granted('ROLE_RESTAURANT') and object.getRestaurant().getOwner() == user)", name: 'create'),
        new Mutation(security: "is_granted('ROLE_ADMIN') or (is_granted('ROLE_RESTAURANT') and object.getRestaurant().getOwner() == user)", name: 'update'),
        new DeleteMutation(security: "is_granted('ROLE_ADMIN') or (is_granted('ROLE_RESTAURANT') and object.getRestaurant().getOwner() == user)", name: 'delete')
    ],
)]
class Meal implements ImageUploadableInterface
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 50)]
    #[Assert\NotBlank]
    #[Groups(['read', 'create', 'update'])]
    private ?string $name = null;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    #[Groups(['read', 'create', 'update'])]
    private ?string $description = null;

    #[ORM\Column(type: Types::FLOAT)]
    #[Groups(['read', 'create', 'update'])]
    private ?float $calories = null;

    #[ORM\Column(type: Types::FLOAT)]
    #[Groups(['read', 'create', 'update'])]
    private ?float $protein = null;

    #[ORM\Column(type: Types::FLOAT)]
    #[Groups(['read', 'create', 'update'])]
    private ?float $fat = null;

    #[ORM\Column(type: Types::FLOAT)]
    #[Groups(['read', 'create', 'update'])]
    private ?float $carbs = null;

    #[ORM\Column(type: Types::INTEGER)]
    #[Assert\GreaterThanOrEqual(value: 0)]
    #[Groups(['create', 'update', 'read'])]
    private ?int $price = null; // Changed to int

    #[ORM\ManyToOne(inversedBy: 'meals')]
    #[ORM\JoinColumn(nullable: false)]
    #[ApiProperty(readableLink: true, writableLink: false)]
    #[Groups(['read', 'create'])]
    private ?Restaurant $restaurant = null;

    #[ORM\ManyToMany(targetEntity: MealPlan::class, mappedBy: 'meals')]
    #[ApiProperty(readableLink: true)]
    #[Groups(['read'])]
    private Collection $mealPlans;

    // --- Image Upload Fields ---
    #[Vich\UploadableField(mapping: 'meal_image', fileNameProperty: 'imagePath')]
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
        $this->mealPlans = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    #[Groups(['read'])]
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
