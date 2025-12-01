<?php

namespace App\Entity;

use ApiPlatform\Metadata\ApiProperty;
use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\GraphQl\DeleteMutation;
use ApiPlatform\Metadata\GraphQl\Mutation;
use ApiPlatform\Metadata\GraphQl\Query;
use ApiPlatform\Metadata\GraphQl\QueryCollection;
use App\Repository\OrderItemRepository;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Serializer\Annotation\Groups;
use Symfony\Component\Validator\Constraints as Assert;

#[ORM\Entity(repositoryClass: OrderItemRepository::class)]
#[ApiResource(
    operations: [],
    normalizationContext: ['groups' => ['read']],
    graphQlOperations: [
        new QueryCollection(security: "is_granted('ROLE_ADMIN') or is_granted('ROLE_RESTAURANT') or is_granted('ROLE_CUSTOMER')"),
        new Query(security: "is_granted('ROLE_ADMIN') or object.getOrder().getCustomer() == user or object.getOrder().getRestaurant() == user.getRestaurant()"),
        new Mutation(security: "is_granted('ROLE_ADMIN')", name: 'update'),
        new DeleteMutation(security: "is_granted('ROLE_ADMIN')", name: 'delete')
    ],
)]
class OrderItem
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\ManyToOne(inversedBy: 'orderItems')]
    #[ORM\JoinColumn(nullable: true)]
    #[ApiProperty(readableLink: true)]
    #[Groups(['read'])]
    private ?Order $order = null;

    #[ORM\ManyToOne]
    #[ORM\JoinColumn(nullable: false)]
    #[ApiProperty(readableLink: true, writableLink: false)]
    #[Groups(['read', 'create'])]
    private ?MealPlan $mealPlan = null;

    #[ORM\Column]
    #[Assert\NotNull]
    #[Assert\GreaterThanOrEqual(value: 1)]
    #[Groups(['read', 'create'])]
    private ?int $quantity = null;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getOrder(): ?Order
    {
        return $this->order;
    }

    public function setOrder(?Order $order): static
    {
        $this->order = $order;

        return $this;
    }

    public function getMealPlan(): ?MealPlan
    {
        return $this->mealPlan;
    }

    public function setMealPlan(MealPlan $mealPlan): static
    {
        $this->mealPlan = $mealPlan;

        return $this;
    }

    public function getQuantity(): ?int
    {
        return $this->quantity;
    }

    public function setQuantity(int $quantity): static
    {
        $this->quantity = $quantity;

        return $this;
    }
}
