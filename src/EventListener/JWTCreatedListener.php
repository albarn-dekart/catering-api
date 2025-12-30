<?php

namespace App\EventListener;

use App\Entity\User;
use Lexik\Bundle\JWTAuthenticationBundle\Event\JWTCreatedEvent;
use Symfony\Component\HttpFoundation\RequestStack;

class JWTCreatedListener
{
    private $requestStack;

    public function __construct(RequestStack $requestStack)
    {
        $this->requestStack = $requestStack;
    }

    public function onJWTCreated(JWTCreatedEvent $event): void
    {
        /** @var User|null $user */
        $user = $event->getUser();

        $payload = $event->getData();
        $payload['id'] = $user->getId();
        $payload['email'] = $user->getEmail();
        $payload['roles'] = $user->getRoles();

        $event->setData($payload);
    }
}
