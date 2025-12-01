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
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\HttpFoundation\File\File;
use Symfony\Component\Serializer\Annotation\Groups;
use Symfony\Component\Validator\Constraints as Assert;
use Vich\UploaderBundle\Mapping\Annotation as Vich;

#[ORM\Entity(repositoryClass: RestaurantRepository::class)]
#[Vich\Uploadable]
#[ApiResource(
    operations: [],
    normalizationContext: ['groups' => ['read']],
    denormalizationContext: ['groups' => ['create', 'update']],
    graphQlOperations: [
        new QueryCollection(security: "is_granted('PUBLIC_ACCESS')"),
        new Query(security: "is_granted('PUBLIC_ACCESS')"),
        new Mutation(security: "is_granted('ROLE_ADMIN') or (is_granted('ROLE_RESTAURANT') and user.getRestaurant() == null)", name: 'create'),
        new Mutation(security: "is_granted('ROLE_RESTAURANT') and object.getOwner() == user or is_granted('ROLE_ADMIN')", name: 'update'),
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

    // 💡 Add description field for detailed read and write
    #[ORM\Column(type: Types::TEXT, nullable: true)]
    #[Groups(['read', 'create', 'update'])]
    private ?string $description = null;

    /**
     * Collection of users associated with this restaurant
     * - Owner: User with ROLE_RESTAURANT
     * - Drivers: Users with ROLE_DRIVER
     */
    #[ORM\OneToMany(targetEntity: User::class, mappedBy: 'restaurant', cascade: ['persist'])]
    #[ApiProperty(readableLink: true)]
    #[Groups(['read'])]
    private Collection $users;

    #[ORM\ManyToMany(targetEntity: Category::class, inversedBy: 'restaurants')]
    #[ApiProperty(readableLink: true, writableLink: false)]
    #[Groups(['read', 'update'])]
    private Collection $categories;

    #[ORM\OneToMany(targetEntity: Meal::class, mappedBy: 'restaurant', orphanRemoval: true)]
    #[ApiProperty(readableLink: true)]
    #[Groups(['read'])]
    private Collection $meals;

    #[ORM\OneToMany(targetEntity: MealPlan::class, mappedBy: 'restaurant', orphanRemoval: true)]
    #[ApiProperty(readableLink: true)]
    #[Groups(['read'])]
    private Collection $mealPlans;

    #[ORM\OneToMany(targetEntity: Delivery::class, mappedBy: 'restaurant', orphanRemoval: true)]
    #[ApiProperty(readableLink: true)]
    #[Groups(['read'])]
    private Collection $deliveries;

    #[ORM\OneToMany(targetEntity: Order::class, mappedBy: 'restaurant', orphanRemoval: true)]
    #[ApiProperty(readableLink: true)]
    #[Groups(['read'])]
    private Collection $orders;

    #[ORM\Column(type: Types::DATETIME_MUTABLE, nullable: true)]
    private ?DateTimeInterface $updatedAt;

    public function __construct()
    {
        $this->users = new ArrayCollection();
        $this->categories = new ArrayCollection();
        $this->meals = new ArrayCollection();
        $this->mealPlans = new ArrayCollection();
        $this->deliveries = new ArrayCollection();
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

    /**
     * @return Collection<int, User>
     */
    public function getUsers(): Collection
    {
        return $this->users;
    }

    public function addUser(User $user): static
    {
        if (!$this->users->contains($user)) {
            $this->users->add($user);
            $user->setRestaurant($this);
        }

        return $this;
    }

    public function removeUser(User $user): static
    {
        if ($this->users->removeElement($user)) {
            // set the owning side to null (unless already changed)
            if ($user->getRestaurant() === $this) {
                $user->setRestaurant(null);
            }
        }

        return $this;
    }

    /**
     * Helper method to get the restaurant owner (user with ROLE_RESTAURANT)
     * For backward compatibility and convenience
     */
    public function getOwner(): ?User
    {
        foreach ($this->users as $user) {
            if (in_array('ROLE_RESTAURANT', $user->getRoles(), true)) {
                return $user;
            }
        }
        return null;
    }

    /**
     * Helper method to get all drivers assigned to this restaurant
     */
    public function getDrivers(): array
    {
        $drivers = [];
        foreach ($this->users as $user) {
            if (in_array('ROLE_DRIVER', $user->getRoles(), true)) {
                $drivers[] = $user;
            }
        }
        return $drivers;
    }

    /**
     * Helper method used by API Platform security expressions.
     */
    public function isOwnedBy(User $user): bool
    {
        return $this->getOwner() === $user;
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

    /**
     * @return Collection<int, Delivery>
     */
    public function getDeliveries(): Collection
    {
        return $this->deliveries;
    }

    public function addDelivery(Delivery $delivery): static
    {
        if (!$this->deliveries->contains($delivery)) {
            $this->deliveries->add($delivery);
            $delivery->setRestaurant($this);
        }

        return $this;
    }

    public function removeDelivery(Delivery $delivery): static
    {
        if ($this->deliveries->removeElement($delivery)) {
            // set the owning side to null (unless already changed)
            if ($delivery->getRestaurant() === $this) {
                $delivery->setRestaurant(null);
            }
        }

        return $this;
    }
}
