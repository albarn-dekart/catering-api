<?php

namespace App\Entity;

// 💡 ApiResource removed
use App\Repository\RecipientDetailsRepository;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Serializer\Annotation\Groups;
use Symfony\Component\Validator\Constraints as Assert;

#[ORM\Entity(repositoryClass: RecipientDetailsRepository::class)]
// 💡 #[ApiResource] attribute removed
class RecipientDetails
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    #[Groups(['user:read:self'])] // Read-only
    private ?int $id = null;

    #[ORM\Column(length: 50)]
    #[Assert\NotBlank]
    #[Assert\Length(max: 50)]
    #[Groups(['user:read:self', 'user:update:self'])] // Readable and writable
    private ?string $firstName = null;

    #[ORM\Column(length: 50)]
    #[Assert\NotBlank]
    #[Assert\Length(max: 50)]
    #[Groups(['user:read:self', 'user:update:self'])]
    private ?string $secondName = null;

    #[ORM\Column(length: 100)]
    #[Assert\NotBlank]
    #[Assert\Length(max: 100)]
    #[Groups(['user:read:self', 'user:update:self'])]
    private ?string $city = null;

    #[ORM\Column(length: 10)]
    #[Assert\NotBlank]
    #[Assert\Length(max: 10)]
    #[Groups(['user:read:self', 'user:update:self'])]
    private ?string $postCode = null;

    #[ORM\Column(length: 20)]
    #[Assert\NotBlank]
    #[Assert\Length(max: 20)]
    #[Groups(['user:read:self', 'user:update:self'])]
    private ?string $phoneNumber = null;

    #[ORM\Column(length: 255)]
    #[Assert\NotBlank]
    #[Assert\Length(max: 255)]
    #[Groups(['user:read:self', 'user:update:self'])]
    private ?string $address = null;

    #[ORM\OneToOne(inversedBy: 'recipientDetails', cascade: ['persist', 'remove'])]
    #[ORM\JoinColumn(nullable: false)]
    // 💡 No groups to prevent circular reference
    private ?User $user = null;

    // ... All getters and setters ...

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getFirstName(): ?string
    {
        return $this->firstName;
    }

    public function setFirstName(string $firstName): static
    {
        $this->firstName = $firstName;

        return $this;
    }

    public function getSecondName(): ?string
    {
        return $this->secondName;
    }

    public function setSecondName(string $secondName): static
    {
        $this->secondName = $secondName;

        return $this;
    }

    public function getCity(): ?string
    {
        return $this->city;
    }

    public function setCity(string $city): static
    {
        $this->city = $city;

        return $this;
    }

    public function getPostCode(): ?string
    {
        return $this->postCode;
    }

    public function setPostCode(string $postCode): static
    {
        $this->postCode = $postCode;

        return $this;
    }

    public function getPhoneNumber(): ?string
    {
        return $this->phoneNumber;
    }

    public function setPhoneNumber(string $phoneNumber): static
    {
        $this->phoneNumber = $phoneNumber;

        return $this;
    }

    public function getAddress(): ?string
    {
        return $this->address;
    }

    public function setAddress(string $address): static
    {
        $this->address = $address;

        return $this;
    }

    public function getUser(): ?User
    {
        return $this->user;
    }

    public function setUser(?User $user): static
    {
        $this->user = $user;

        return $this;
    }
}