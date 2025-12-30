<?php

namespace App\Entity;

use ApiPlatform\Metadata\ApiProperty;
use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\GraphQl\DeleteMutation;
use ApiPlatform\Metadata\GraphQl\Mutation;
use ApiPlatform\Metadata\GraphQl\Query;
use ApiPlatform\Metadata\GraphQl\QueryCollection;
use App\ApiResource\ImageUploadableInterface;
use App\Repository\RestaurantRepository;
use DateTimeImmutable;
use DateTimeInterface;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\Common\Collections\Selectable;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\HttpFoundation\File\File;
use Symfony\Component\Serializer\Attribute\Groups;
use Symfony\Component\Validator\Constraints as Assert;
use Vich\UploaderBundle\Mapping\Attribute as Vich;
use Doctrine\Common\Collections\Criteria;

use ApiPlatform\Metadata\ApiFilter;
use ApiPlatform\Doctrine\Orm\Filter\SearchFilter;
use App\Filter\RestaurantSearchFilter;

#[ORM\Entity(repositoryClass: RestaurantRepository::class)]
#[Vich\Uploadable]
#[ApiFilter(RestaurantSearchFilter::class)]
#[ApiFilter(SearchFilter::class, properties: ['restaurantCategories.name' => 'exact'])]
#[ApiResource(
    operations: [],
    normalizationContext: ['groups' => ['read']],
    denormalizationContext: ['groups' => ['create', 'update']],
    graphQlOperations: [
        new QueryCollection(security: "is_granted('PUBLIC_ACCESS')"),
        new Query(security: "is_granted('PUBLIC_ACCESS')"),
        new Mutation(security: "is_granted('ROLE_ADMIN')", name: 'create'),
        new Mutation(security: "is_granted('ROLE_ADMIN') or (is_granted('ROLE_RESTAURANT') and object.getOwner() == user)", name: 'update'),
        new DeleteMutation(security: "is_granted('ROLE_ADMIN')", name: 'delete')
    ],
)]
class Restaurant implements ImageUploadableInterface
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 50)]
    #[Assert\NotBlank]
    #[Assert\Length(max: 50)]
    #[Groups(['read', 'create', 'update'])]
    private ?string $name = null;

    #[Vich\UploadableField(mapping: 'restaurant_image', fileNameProperty: 'imagePath')]
    #[Assert\File(
        maxSize: '2M',
        mimeTypes: ['image/jpeg', 'image/png', 'image/webp'],
    )]
    private ?File $imageFile = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $imagePath = null;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    #[Groups(['read', 'create', 'update'])]
    private ?string $description = null;

    #[ORM\Column(type: Types::INTEGER, options: ['default' => 0])]
    #[Groups(['read', 'create', 'update'])]
    private int $deliveryPrice = 0;

    #[ORM\Column(length: 20, nullable: true)]
    #[Groups(['read', 'create', 'update'])]
    #[Assert\Length(max: 20)]
    #[Assert\Regex(
        pattern: '/^(\+48)?\s?(\d{3}[-\s]?\d{3}[-\s]?\d{3}|\d{9})$/',
        message: 'Phone number must be valid (e.g. 123456789 or +48 123 456 789)'
    )]
    private ?string $phoneNumber = null;

    #[ORM\Column(length: 255, nullable: true)]
    #[Groups(['read', 'create', 'update'])]
    #[Assert\Email]
    private ?string $email = null;

    #[ORM\Column(length: 255, nullable: true)]
    #[Groups(['read', 'create', 'update'])]
    #[Assert\NotBlank]
    private ?string $city = null;

    #[ORM\Column(length: 255, nullable: true)]
    #[Groups(['read', 'create', 'update'])]
    #[Assert\NotBlank]
    private ?string $street = null;

    #[ORM\Column(length: 10, nullable: true)]
    #[Groups(['read', 'create', 'update'])]
    #[Assert\NotBlank]
    #[Assert\Regex(
        pattern: '/^\d{2}-\d{3}$/',
        message: 'Zip code must be in XX-XXX format (e.g. 12-345)'
    )]
    private ?string $zipCode = null;

    #[ORM\Column(length: 10, nullable: true)]
    #[Groups(['read', 'create', 'update'])]
    #[Assert\Length(min: 10, max: 10)]
    #[Assert\Regex(
        pattern: '/^\d{10}$/',
        message: 'NIP must consist of exactly 10 digits'
    )]
    private ?string $nip = null;

    /**
     * The restaurant owner (user with ROLE_RESTAURANT)
     */
    #[ORM\OneToOne(targetEntity: User::class, inversedBy: 'ownedRestaurant')]
    #[ORM\JoinColumn(nullable: true, onDelete: 'SET NULL')]
    #[ApiProperty(readableLink: true, writableLink: false)]
    #[Groups(['read', 'create', 'update'])]
    private ?User $owner = null;

    /**
     * Drivers assigned to this restaurant
     */
    #[ORM\ManyToMany(targetEntity: User::class)]
    #[ORM\JoinTable(name: 'restaurant_drivers')]
    #[ApiProperty(readableLink: true, writableLink: false)]
    #[Groups(['read', 'create', 'update'])]
    private Collection $drivers;

    #[ORM\ManyToMany(targetEntity: RestaurantCategory::class, inversedBy: 'restaurants')]
    #[ApiProperty(readableLink: true, writableLink: false)]
    #[Groups(['read', 'create', 'update'])]
    private Collection $restaurantCategories;

    #[ORM\OneToMany(targetEntity: Meal::class, mappedBy: 'restaurant', orphanRemoval: true)]
    #[ORM\OrderBy(['id' => 'DESC'])]
    #[ApiProperty(readableLink: true, writableLink: false)]
    #[Groups(['read'])]
    private Collection $meals;

    /**
     * @var Collection<int, MealPlan>&Selectable<int, MealPlan>
     */
    #[ORM\OneToMany(targetEntity: MealPlan::class, mappedBy: 'restaurant', orphanRemoval: true)]
    #[ORM\OrderBy(['id' => 'DESC'])]
    #[ApiProperty(readableLink: true, writableLink: false)]
    #[Groups(['read'])]
    private Collection $mealPlans;



    #[ORM\OneToMany(targetEntity: Order::class, mappedBy: 'restaurant', orphanRemoval: true)]
    #[ORM\OrderBy(['id' => 'DESC'])]
    #[ApiProperty(readableLink: true, writableLink: false)]
    #[Groups(['read'])]
    private Collection $orders;

    #[ORM\Column(type: Types::DATETIME_MUTABLE, nullable: true)]
    private ?DateTimeInterface $updatedAt;

    public function __construct()
    {
        $this->drivers = new ArrayCollection();
        $this->restaurantCategories = new ArrayCollection();
        $this->meals = new ArrayCollection();
        $this->mealPlans = new ArrayCollection();

        $this->orders = new ArrayCollection();
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
            return '/images/restaurants/' . $this->getImagePath();
        }
        return null;
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

    public function getDeliveryPrice(): int
    {
        return $this->deliveryPrice;
    }

    public function setDeliveryPrice(int $deliveryPrice): static
    {
        $this->deliveryPrice = $deliveryPrice;

        return $this;
    }

    public function getPhoneNumber(): ?string
    {
        return $this->phoneNumber;
    }

    public function setPhoneNumber(?string $phoneNumber): static
    {
        $this->phoneNumber = $phoneNumber;

        return $this;
    }

    public function getEmail(): ?string
    {
        return $this->email;
    }

    public function setEmail(?string $email): static
    {
        $this->email = $email;

        return $this;
    }

    public function getCity(): ?string
    {
        return $this->city;
    }

    public function setCity(?string $city): static
    {
        $this->city = $city;

        return $this;
    }

    public function getStreet(): ?string
    {
        return $this->street;
    }

    public function setStreet(?string $street): static
    {
        $this->street = $street;

        return $this;
    }

    public function getZipCode(): ?string
    {
        return $this->zipCode;
    }

    public function setZipCode(?string $zipCode): static
    {
        $this->zipCode = $zipCode;

        return $this;
    }

    public function getNip(): ?string
    {
        return $this->nip;
    }

    public function setNip(?string $nip): static
    {
        $this->nip = $nip;

        return $this;
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

    /**
     * @return Collection<int, User>
     */
    public function getDrivers(): Collection
    {
        return $this->drivers;
    }

    public function addDriver(User $driver): static
    {
        if (!$this->drivers->contains($driver)) {
            $this->drivers->add($driver);
        }

        return $this;
    }

    public function removeDriver(User $driver): static
    {
        $this->drivers->removeElement($driver);

        return $this;
    }

    /**
     * Helper method used by API Platform security expressions.
     */
    public function isOwnedBy(User $user): bool
    {
        return $this->getOwner() === $user;
    }

    /**
     * @return Collection<int, RestaurantCategory>
     */
    public function getRestaurantCategories(): Collection
    {
        return $this->restaurantCategories;
    }

    public function addRestaurantCategory(RestaurantCategory $restaurantCategory): static
    {
        if (!$this->restaurantCategories->contains($restaurantCategory)) {
            $this->restaurantCategories->add($restaurantCategory);
        }

        return $this;
    }

    public function removeRestaurantCategory(RestaurantCategory $restaurantCategory): static
    {
        $this->restaurantCategories->removeElement($restaurantCategory);

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
        /** @var Selectable&Collection $mealPlans */
        $mealPlans = $this->mealPlans;
        $criteria = Criteria::create()
            ->where(Criteria::expr()->isNull('owner'))
            ->orderBy(['id' => 'DESC']);
        return $mealPlans->matching($criteria);
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
