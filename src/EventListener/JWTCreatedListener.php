<?php

namespace App\EventListener;

use App\Entity\User;
use Lexik\Bundle\JWTAuthenticationBundle\Event\JWTCreatedEvent;

class JWTCreatedListener
{
    public function onJWTCreated(JWTCreatedEvent $event): void
    {
        /** @var User $user */
        $user = $event->getUser();

        $payload = $event->getData();
        $payload['detailsFilled'] = $user->getRecipientDetails() != null;

        // Set the modified payload back to the event
        $event->setData($payload);
    }
}