<?php

namespace App\Entity;

use ApiPlatform\Metadata\ApiProperty;
use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\GraphQl\DeleteMutation;
use ApiPlatform\Metadata\GraphQl\Mutation;
use ApiPlatform\Metadata\GraphQl\Query;
use ApiPlatform\Metadata\GraphQl\QueryCollection;
use App\Repository\AddressRepository;
use App\State\AddressProcessor;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Serializer\Annotation\Groups;
use Symfony\Component\Validator\Constraints as Assert;

#[ORM\Entity(repositoryClass: AddressRepository::class)]
#[ApiResource(
    operations: [],
    normalizationContext: ['groups' => ['read']],
    denormalizationContext: ['groups' => ['create', 'update']],
    graphQlOperations: [
        new QueryCollection(security: "is_granted('ROLE_CUSTOMER') and object.getUser() == user or is_granted('ROLE_ADMIN')"),
        new Query(security: "is_granted('ROLE_CUSTOMER') and object.getUser() == user or is_granted('ROLE_ADMIN')"),
        new Mutation(
            security: "is_granted('ROLE_CUSTOMER') or is_granted('ROLE_ADMIN')",
            validationContext: ['groups' => ['create']],
            name: 'create',
            processor: AddressProcessor::class
        ),
        new Mutation(
            security: "is_granted('ROLE_CUSTOMER') and object.getUser() == user or is_granted('ROLE_ADMIN')",
            validationContext: ['groups' => ['update']],
            name: 'update',
            processor: AddressProcessor::class
        ),
        new DeleteMutation(
            security: "is_granted('ROLE_CUSTOMER') and object.getUser() == user or is_granted('ROLE_ADMIN')",
            name: 'delete'
        )
    ]
)]
class Address
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\ManyToOne(inversedBy: 'addresses')]
    #[ORM\JoinColumn(nullable: false)]
    #[ApiProperty(readableLink: true, writableLink: false)]
    #[Groups(['create'])]
    private ?User $user = null;

    #[ORM\Column(length: 255)]
    #[Assert\NotBlank(groups: ['create', 'update'])]
    #[Groups(['read', 'create', 'update', 'read'])]
    private ?string $firstName = null;

    #[ORM\Column(length: 255)]
    #[Assert\NotBlank(groups: ['create', 'update'])]
    #[Groups(['read', 'create', 'update', 'read'])]
    private ?string $lastName = null;

    #[ORM\Column(length: 255)]
    #[Assert\NotBlank(groups: ['create', 'update'])]
    #[Groups(['read', 'create', 'update', 'read'])]
    private ?string $phoneNumber = null;

    #[ORM\Column(length: 255)]
    #[Assert\NotBlank(groups: ['create', 'update'])]
    #[Groups(['read', 'create', 'update', 'read'])]
    private ?string $street = null;

    #[ORM\Column(length: 255, nullable: true)]
    #[Groups(['read', 'create', 'update', 'read'])]
    private ?string $apartment = null;

    #[ORM\Column(length: 255)]
    #[Assert\NotBlank(groups: ['create', 'update'])]
    #[Groups(['read', 'create', 'update', 'read'])]
    private ?string $city = null;

    #[ORM\Column(length: 255)]
    #[Assert\NotBlank(groups: ['create', 'update'])]
    #[Groups(['read', 'create', 'update', 'read'])]
    private ?string $zipCode = null;

    #[ORM\Column]
    #[Groups(['read', 'create', 'update', 'read'])]
    private bool $isDefault = false;

    public function getId(): ?int
    {
        return $this->id;
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

    public function getFirstName(): ?string
    {
        return $this->firstName;
    }

    public function setFirstName(string $firstName): static
    {
        $this->firstName = $firstName;

        return $this;
    }

    public function getLastName(): ?string
    {
        return $this->lastName;
    }

    public function setLastName(string $lastName): static
    {
        $this->lastName = $lastName;

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

    public function getStreet(): ?string
    {
        return $this->street;
    }

    public function setStreet(string $street): static
    {
        $this->street = $street;

        return $this;
    }

    public function getApartment(): ?string
    {
        return $this->apartment;
    }

    public function setApartment(?string $apartment): static
    {
        $this->apartment = $apartment;

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

    public function getZipCode(): ?string
    {
        return $this->zipCode;
    }

    public function setZipCode(string $zipCode): static
    {
        $this->zipCode = $zipCode;

        return $this;
    }

    public function getIsDefault(): bool
    {
        return $this->isDefault;
    }

    public function setIsDefault(bool $isDefault): static
    {
        $this->isDefault = $isDefault;

        return $this;
    }
}
