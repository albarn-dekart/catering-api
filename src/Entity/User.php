<?php

namespace App\Entity;

use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\Delete;
use ApiPlatform\Metadata\Get;
use ApiPlatform\Metadata\GetCollection;
use ApiPlatform\Metadata\Patch;
use ApiPlatform\Metadata\Post;
use App\Repository\UserRepository;
use App\State\MeStateProvider;
use App\State\UserProcessor;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Bridge\Doctrine\Validator\Constraints\UniqueEntity;
use Symfony\Component\Security\Core\User\PasswordAuthenticatedUserInterface;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Component\Serializer\Annotation\Groups;
use Symfony\Component\Validator\Constraints as Assert;

#[ApiResource(
    operations: [
        // ----------------------------------------
        // Admin Operations (Full Power)
        // ----------------------------------------
        new GetCollection(
            normalizationContext: ['groups' => ['user:read:admin']],
            security: "is_granted('ROLE_ADMIN')"
        ),
        new Get(
            uriTemplate: '/users/{id}',
            normalizationContext: ['groups' => ['user:read:admin']],
            security: "is_granted('ROLE_ADMIN')"
        ),
        new Patch(
            uriTemplate: '/users/{id}',
            normalizationContext: ['groups' => ['user:update:admin']],
            security: "is_granted('ROLE_ADMIN')"
        ),

        // ----------------------------------------
        // User "Me" Operations (Self-Management)
        // ----------------------------------------
        new Get(
            uriTemplate: '/me', // A common alias for the current user
            normalizationContext: ['groups' => ['user:read:self']],
            provider: MeStateProvider::class
        ),
        new Patch(
            uriTemplate: '/me', // Patch the current user
            normalizationContext: ['groups' => ['user:read:self']],
            denormalizationContext: ['groups' => ['user:update:self']],
            provider: MeStateProvider::class,
            processor: UserProcessor::class // Handles password/data changes
        ),

        // ----------------------------------------
        // Public Registration
        // ----------------------------------------
        new Post(
            uriTemplate: '/register', // Customer registration
            normalizationContext: ['groups' => ['user:read:self']],
            denormalizationContext: ['groups' => ['user:create:customer']],
            validationContext: ['groups' => ['user:create']], // Use existing validation
            processor: UserProcessor::class
        ),
        new Post(
            uriTemplate: '/register/owner', // Restaurant Owner registration
            normalizationContext: ['groups' => ['user:read:self']],
            denormalizationContext: ['groups' => ['user:create:restaurant']],
            validationContext: ['groups' => ['user:create:restaurant']], // Use existing validation
            processor: UserProcessor::class
        ),

        // ----------------------------------------
        // Admin Delete
        // ----------------------------------------
        new Delete(
            security: "is_granted('ROLE_ADMIN')"
        )
        // 💡 Password change should be a custom operation
    ],
    // Default normalization is admin-only for security
    normalizationContext: ['groups' => ['user:read:admin']],
)]
#[ORM\Entity(repositoryClass: UserRepository::class)]
#[ORM\Table(name: '`user`')]
#[UniqueEntity(fields: ['email'], message: 'There is already an account with this email')]
class User implements UserInterface, PasswordAuthenticatedUserInterface
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    #[Groups(['user:read:admin', 'user:read:self'])]
    private ?int $id = null;

    #[ORM\Column(length: 180, unique: true)]
    #[Assert\NotBlank(groups: ['user:create', 'user:create:restaurant'])]
    #[Assert\Email(groups: ['user:create', 'user:create:restaurant'])]
    #[Groups(['user:read:admin', 'user:read:self', 'user:create:customer', 'user:create:restaurant', 'user:update:self'])]
    private ?string $email = null;

    #[Assert\NotBlank(groups: ['user:create', 'user:create:restaurant'])]
    #[Assert\Regex(
        pattern: "/^(?=.*[A-Z])(?=.*\d).{8,}$/",
        message: 'Password must be 8+ chars with 1 uppercase and 1 digit.',
        groups: ['user:create', 'user:create:restaurant']
    )]
    #[Groups(['user:create:customer', 'user:create:restaurant', 'user:change_password'])]
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
        groups: ['user:create', 'user:write']
    )]
    #[Groups(['user:read:admin', 'user:read:self', 'user:update:admin'])]
    private array $roles = [];

    #[ORM\OneToOne(mappedBy: 'user', cascade: ['persist', 'remove'])]
    #[Groups(['user:read:self', 'user:update:self'])]
    #[Assert\Valid]
    private ?RecipientDetails $recipientDetails = null;

    #[ORM\OneToOne(mappedBy: 'owner', cascade: ['persist', 'remove'])]
    #[Assert\Valid]
    #[Groups(['user:read:self', 'user:create:restaurant'])]
    private ?Restaurant $restaurant = null;

    #[ORM\OneToMany(targetEntity: Order::class, mappedBy: 'customer', orphanRemoval: true)]
    private Collection $orders;

    #[ORM\OneToMany(targetEntity: Delivery::class, mappedBy: 'driver')]
    private Collection $deliveries;


    public function __construct()
    {
        $this->orders = new ArrayCollection();
        $this->deliveries = new ArrayCollection();
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

    public function getRecipientDetails(): ?RecipientDetails
    {
        return $this->recipientDetails;
    }

    public function setRecipientDetails(RecipientDetails $recipientDetails): static
    {
        // set the owning side of the relation if necessary
        if ($recipientDetails->getUser() !== $this) {
            $recipientDetails->setUser($this);
        }

        $this->recipientDetails = $recipientDetails;

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
        // unset the owning side of the relation if necessary
        if (null === $restaurant && null !== $this->restaurant) {
            $this->restaurant->setOwner(null);
        }

        // set the owning side of the relation if necessary
        if (null !== $restaurant && $restaurant->getOwner() !== $this) {
            $restaurant->setOwner($this);
        }

        $this->restaurant = $restaurant;

        return $this;
    }
}