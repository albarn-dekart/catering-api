<?php

namespace App\Entity;

use App\Repository\UserRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Bridge\Doctrine\Validator\Constraints\UniqueEntity;
use Symfony\Component\Security\Core\User\PasswordAuthenticatedUserInterface;
use Symfony\Component\Security\Core\User\UserInterface;
use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\Get;
use ApiPlatform\Metadata\GetCollection;
use ApiPlatform\Metadata\Post;
use ApiPlatform\Metadata\Patch;
use ApiPlatform\Metadata\Delete;
use Symfony\Component\Serializer\Annotation\Groups;
use Symfony\Component\Validator\Constraints as Assert;

#[ApiResource(
    operations: [
        new GetCollection(security: "is_granted('ROLE_ADMIN')"),
        new Get(security: "is_granted('ROLE_ADMIN') or object == user"),
        new Patch(security: "is_granted('ROLE_ADMIN') or object == user"),
        new Delete(security: "is_granted('ROLE_ADMIN')")
    ],
    normalizationContext: ['groups' => ['user:read']],
    denormalizationContext: ['groups' => ['user:write']]
)]
#[ORM\Entity(repositoryClass: UserRepository::class)]
#[ORM\Table(name: '`user`')]
#[UniqueEntity(fields: ['email'], message: 'There is already an account with this email')]
class User implements UserInterface, PasswordAuthenticatedUserInterface
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 180, unique: true)]
    #[Groups(['user:read', 'user:create'])]
    #[Assert\NotBlank(groups: ['user:create'])]
    #[Assert\Email(groups: ['user:create'])]
    private ?string $email = null;

    #[ORM\Column]
    #[Groups(['user:create', 'user:write'])]
    #[Assert\NotBlank(groups: ['user:create'])]
    #[Assert\Regex(
        pattern: "/^(?=.*[A-Z])(?=.*\d).{8,}$/",
        message: "Password must be 8+ chars with 1 uppercase and 1 digit.",
        groups: ['user:create']
    )]
    private ?string $password = null;

    #[ORM\Column]
    #[Groups(['user:read', 'user:write'])]
    #[Assert\Choice(
        choices: ['ROLE_USER', 'ROLE_ADMIN', 'ROLE_DRIVER', 'ROLE_RESTAURANT'],
        multiple: true,
        groups: ['user:create', 'user:write']
    )]
    private array $roles = [];

    #[ORM\OneToOne(mappedBy: 'user', cascade: ['persist', 'remove'])]
    #[Groups(['user:read', 'user:write'])]
    private ?RecipientDetails $recipientDetails = null;

    #[ORM\OneToOne(mappedBy: 'owner', cascade: ['persist', 'remove'])]
    #[Groups(['user:read'])]
    private ?Restaurant $restaurant = null;

    #[ORM\OneToMany(targetEntity: Order::class, mappedBy: 'customer', orphanRemoval: true)]
    #[Groups(['user:read'])]
    private Collection $orders;

    #[ORM\OneToMany(targetEntity: Delivery::class, mappedBy: 'driver')]
    #[Groups(['user:read'])]
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

    public function setEmail(string $email): static
    {
        $this->email = $email;

        return $this;
    }

    public function getUserIdentifier(): string
    {
        return (string)$this->email;
    }

    public function getRoles(): array
    {
        $roles = $this->roles;
        // guarantee every user at least has ROLE_USER
        $roles[] = 'ROLE_USER';

        return array_unique($roles);
    }

    public function setRoles(array $roles): static
    {
        $this->roles = $roles;

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

    public function eraseCredentials(): void
    {
        // If you store any temporary, sensitive data on the user, clear it here
        // $this->plainPassword = null;
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

    public function getRestaurant(): ?Restaurant
    {
        return $this->restaurant;
    }

    public function setRestaurant(?Restaurant $restaurant): static
    {
        // unset the owning side of the relation if necessary
        if ($restaurant === null && $this->restaurant !== null) {
            $this->restaurant->setOwner(null);
        }

        // set the owning side of the relation if necessary
        if ($restaurant !== null && $restaurant->getOwner() !== $this) {
            $restaurant->setOwner($this);
        }

        $this->restaurant = $restaurant;

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
}
