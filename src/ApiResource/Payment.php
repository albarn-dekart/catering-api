<?php

namespace App\ApiResource;

use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\GraphQl\Mutation;
use App\State\PaymentProcessor;
use App\Entity\Order;
use Symfony\Component\Validator\Constraints as Assert;

#[ApiResource(
    operations: [],
    graphQlOperations: [
        new Mutation(
            name: 'create',
            processor: PaymentProcessor::class
        )
    ]
)]
class Payment
{
    public ?string $id = null;

    #[Assert\NotNull]
    public ?Order $order = null;

    public ?string $sessionId = null;

    public ?string $url = null;
}