<?php

namespace App\Entity;

use ApiPlatform\Metadata\ApiProperty;
use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\GraphQl\Query;
use ApiPlatform\Metadata\GraphQl\Mutation;
use ApiPlatform\Metadata\GraphQl\DeleteMutation;
use ApiPlatform\Metadata\GraphQl\QueryCollection;
use App\Repository\UserRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Bridge\Doctrine\Validator\Constraints\UniqueEntity;
use Symfony\Component\Security\Core\User\PasswordAuthenticatedUserInterface;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Component\Serializer\Attribute\Groups;
use Symfony\Component\Validator\Constraints as Assert;
use ApiPlatform\Metadata\ApiFilter;
use App\Filter\UserSearchFilter;

#[ORM\Entity(repositoryClass: UserRepository::class)]
#[ORM\Table(name: '`user`')]
#[UniqueEntity(fields: ['email'], message: 'There is already an account with this email')]
#[ApiFilter(UserSearchFilter::class)]
#[ApiResource(
    operations: [],
    normalizationContext: ['groups' => ['read']],
    denormalizationContext: ['groups' => ['create', 'update']],
    graphQlOperations: [
        new QueryCollection(security: "is_granted('ROLE_ADMIN')"),
        new Query(security: "is_granted('IS_AUTHENTICATED_FULLY') and object == user or is_granted('ROLE_ADMIN')"),
        new Mutation(
            security: "is_granted('ROLE_ADMIN')",
            name: 'update'
        ),
        new DeleteMutation(security: "is_granted('ROLE_ADMIN')", name: 'delete')
    ],
)]
class User implements UserInterface, PasswordAuthenticatedUserInterface
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 180, unique: true)]
    #[Assert\Email(groups: ['create'])]
    #[Groups(['read', 'create', 'update'])]
    private ?string $email = null;

    #[ORM\Column]
    private ?string $password = null;

    #[ORM\Column]
    #[Groups(['read', 'create', 'update'])]
    private array $roles = [];

    #[ORM\OneToMany(targetEntity: Order::class, mappedBy: 'customer')]
    #[ORM\OrderBy(['createdAt' => 'DESC'])]
    #[ApiProperty(readableLink: true, writableLink: false)]
    #[Groups(['read'])]
    private Collection $orders;

    #[ORM\OneToMany(targetEntity: Delivery::class, mappedBy: 'courier')]
    #[ORM\OrderBy(['id' => 'DESC'])]
    #[ApiProperty(readableLink: true, writableLink: false)]
    #[Groups(['read'])]
    private Collection $deliveries;

    #[ORM\OneToMany(targetEntity: Address::class, mappedBy: 'user', orphanRemoval: true)]
    #[ORM\OrderBy(['id' => 'DESC'])]
    #[ApiProperty(readableLink: true, writableLink: false)]
    #[Groups(['read'])]
    private Collection $addresses;

    #[ORM\OneToMany(targetEntity: MealPlan::class, mappedBy: 'owner', orphanRemoval: true)]
    #[ORM\OrderBy(['id' => 'DESC'])]
    #[ApiProperty(readableLink: true, writableLink: false)]
    #[Groups(['read'])]
    private Collection $customMealPlans;

    /**
     * Restaurant owned by this user (for ROLE_RESTAURANT users)
     * This is the inverse side of Restaurant::owner
     */
    #[ORM\OneToOne(targetEntity: Restaurant::class, mappedBy: 'owner')]
    #[ApiProperty(readableLink: true)]
    #[Groups(['read'])]
    private ?Restaurant $ownedRestaurant = null;

    public function __construct()
    {
        $this->orders = new ArrayCollection();
        $this->deliveries = new ArrayCollection();
        $this->addresses = new ArrayCollection();
        $this->customMealPlans = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getEmail(): ?string
    {
        return $this->email;
    }

    /**
     * A visual identifier that represents this user.
     *
     * @see UserInterface
     */
    public function getUserIdentifier(): string
    {
        return (string) $this->email;
    }

    public function setEmail(string $email): static
    {
        $this->email = $email;

        return $this;
    }

    public function getRoles(): array
    {
        $roles = $this->roles;

        return array_unique($roles);
    }

    public function setRoles(array $roles): static
    {
        $this->roles = $roles;

        return $this;
    }

    #[\Deprecated]
    public function eraseCredentials(): void {}

    public function getPassword(): ?string
    {
        return $this->password;
    }

    public function setPassword(string $password): static
    {
        $this->password = $password;

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
            $order->setCustomer($this);
        }

        return $this;
    }

    public function removeOrder(Order $order): static
    {
        if ($this->orders->removeElement($order)) {
            // set the owning side to null (unless already changed)
            if ($order->getCustomer() === $this) {
                $order->setCustomer(null);
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
            $delivery->setCourier($this);
        }

        return $this;
    }

    public function removeDelivery(Delivery $delivery): static
    {
        if ($this->deliveries->removeElement($delivery)) {
            // set the owning side to null (unless already changed)
            if ($delivery->getCourier() === $this) {
                $delivery->setCourier(null);
            }
        }

        return $this;
    }

    /**
     * @return Collection<int, Address>
     */
    public function getAddresses(): Collection
    {
        return $this->addresses;
    }

    public function addAddress(Address $address): static
    {
        if (!$this->addresses->contains($address)) {
            $this->addresses->add($address);
            $address->setUser($this);
        }

        return $this;
    }

    public function removeAddress(Address $address): static
    {
        if ($this->addresses->removeElement($address)) {
            // set the owning side to null (unless already changed)
            if ($address->getUser() === $this) {
                $address->setUser(null);
            }
        }

        return $this;
    }

    /**
     * @return Collection<int, MealPlan>
     */
    public function getCustomMealPlans(): Collection
    {
        return $this->customMealPlans;
    }

    public function addCustomMealPlan(MealPlan $customMealPlan): static
    {
        if (!$this->customMealPlans->contains($customMealPlan)) {
            $this->customMealPlans->add($customMealPlan);
            $customMealPlan->setOwner($this);
        }

        return $this;
    }

    public function removeCustomMealPlan(MealPlan $customMealPlan): static
    {
        if ($this->customMealPlans->removeElement($customMealPlan)) {
            // set the owning side to null (unless already changed)
            if ($customMealPlan->getOwner() === $this) {
                $customMealPlan->setOwner(null);
            }
        }

        return $this;
    }

    /**
     * Get the restaurant owned by this user.
     * This is a convenience method for restaurant owners (ROLE_RESTAURANT).
     */
    public function getOwnedRestaurant(): ?Restaurant
    {
        return $this->ownedRestaurant;
    }

    public function setOwnedRestaurant(?Restaurant $ownedRestaurant): static
    {
        // Unset the owning side of previous restaurant if exists
        if ($this->ownedRestaurant !== null && $this->ownedRestaurant->getOwner() === $this) {
            $this->ownedRestaurant->setOwner(null);
        }

        $this->ownedRestaurant = $ownedRestaurant;

        // Set the owning side of the new restaurant
        if ($ownedRestaurant !== null && $ownedRestaurant->getOwner() !== $this) {
            $ownedRestaurant->setOwner($this);
        }

        return $this;
    }
}
