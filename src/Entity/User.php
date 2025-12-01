<?php

namespace App\Entity;

use ApiPlatform\Metadata\ApiProperty;
use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\GraphQl\DeleteMutation;
use ApiPlatform\Metadata\GraphQl\Mutation;
use ApiPlatform\Metadata\GraphQl\Query;
use ApiPlatform\Metadata\GraphQl\QueryCollection;
use App\Repository\UserRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Bridge\Doctrine\Validator\Constraints\UniqueEntity;
use Symfony\Component\Security\Core\User\PasswordAuthenticatedUserInterface;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Component\Serializer\Annotation\Groups;
use Symfony\Component\Validator\Constraints as Assert;

#[ORM\Entity(repositoryClass: UserRepository::class)]
#[ORM\Table(name: '`user`')]
#[UniqueEntity(fields: ['email'], message: 'There is already an account with this email')]
#[ApiResource(
    operations: [],
    normalizationContext: ['groups' => ['read']],
    denormalizationContext: ['groups' => ['create', 'update']],
    graphQlOperations: [
        new QueryCollection(security: "is_granted('ROLE_ADMIN')"),
        new Mutation(
            security: "is_granted('PUBLIC_ACCESS')",
            validationContext: ['groups' => ['create']],
            name: 'create'
        ),
        new Query(security: "is_granted('IS_AUTHENTICATED_FULLY') and object == user or is_granted('ROLE_ADMIN')"),
        new Mutation(
            security: "is_granted('IS_AUTHENTICATED_FULLY') and object == user or is_granted('ROLE_ADMIN')",
            validationContext: ['groups' => ['update']],
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
    #[Assert\NotBlank(groups: ['create', 'user:create:restaurant'])]
    #[Assert\Email(groups: ['create', 'user:create:restaurant'])]
    #[Groups(['read', 'create', 'update'])]
    private ?string $email = null;

    #[Assert\NotBlank(groups: ['create', 'user:create:restaurant'])]
    #[Assert\Regex(
        pattern: "/^(?=.*[A-Z])(?=.*\d).{8,}$/",
        message: 'Password must be 8+ chars with 1 uppercase and 1 digit.',
        groups: ['create', 'user:create:restaurant']
    )]
    #[Groups(['create', 'update'])]
    private ?string $plainPassword = null;

    #[Assert\NotBlank(groups: ['user:change_password'])]
    #[Groups(['user:change_password'])]
    private ?string $currentPassword = null;

    #[ORM\Column]
    private ?string $password = null;

    #[ORM\Column]
    #[Assert\Choice(
        choices: ['ROLE_CUSTOMER', 'ROLE_ADMIN', 'ROLE_DRIVER', 'ROLE_RESTAURANT'],
        multiple: true,
        groups: ['create', 'user:write']
    )]
    #[Groups(['read', 'create', 'update'])]
    private array $roles = [];

    /**
     * For ROLE_RESTAURANT: their owned restaurant
     * For ROLE_DRIVER: the restaurant they work for
     * Null for customers and admins
     */
    #[ORM\ManyToOne(targetEntity: Restaurant::class, inversedBy: 'users')]
    #[ORM\JoinColumn(nullable: true, onDelete: 'SET NULL')]
    #[Assert\Valid]
    #[ApiProperty(readableLink: true, writableLink: false)]
    #[Groups(['read', 'create', 'update'])]
    private ?Restaurant $restaurant = null;

    #[ORM\OneToMany(targetEntity: Order::class, mappedBy: 'customer', orphanRemoval: true)]
    #[ApiProperty(readableLink: true)]
    #[Groups(['read'])]
    private Collection $orders;

    #[ORM\OneToMany(targetEntity: Delivery::class, mappedBy: 'driver')]
    #[ApiProperty(readableLink: true)]
    #[Groups(['read'])]
    private Collection $deliveries;

    #[ORM\OneToMany(targetEntity: Address::class, mappedBy: 'user', orphanRemoval: true)]
    #[ApiProperty(readableLink: true)]
    #[Groups(['read'])]
    private Collection $addresses;


    public function __construct()
    {
        $this->orders = new ArrayCollection();
        $this->deliveries = new ArrayCollection();
        $this->addresses = new ArrayCollection();
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

    public function eraseCredentials(): void
    {
        // If you store any temporary, sensitive data on the user, clear it here
        $this->plainPassword = null;
    }

    public function getPlainPassword(): ?string
    {
        return $this->plainPassword;
    }

    public function setPlainPassword(?string $plainPassword): self
    {
        $this->plainPassword = $plainPassword;

        return $this;
    }

    public function getCurrentPassword(): ?string
    {
        return $this->currentPassword;
    }

    public function setCurrentPassword(?string $currentPassword): self
    {
        $this->currentPassword = $currentPassword;

        return $this;
    }

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
            $delivery->setDriver($this);
        }

        return $this;
    }

    public function removeDelivery(Delivery $delivery): static
    {
        if ($this->deliveries->removeElement($delivery)) {
            // set the owning side to null (unless already changed)
            if ($delivery->getDriver() === $this) {
                $delivery->setDriver(null);
            }
        }

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
}