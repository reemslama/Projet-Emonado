<?php

namespace App\EventSubscriber;

use App\Entity\Traits\BlameableTrait;
use App\Entity\User;
use Doctrine\Common\EventSubscriber;
use Doctrine\ORM\Events;
use Doctrine\ORM\Event\LifecycleEventArgs;
use Doctrine\ORM\Event\PreUpdateEventArgs;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;

class BlameableSubscriber implements EventSubscriber
{
    public function __construct(private TokenStorageInterface $tokenStorage)
    {
    }

    public function getSubscribedEvents(): array
    {
        return [
            Events::prePersist,
            Events::preUpdate,
        ];
    }

    public function prePersist(LifecycleEventArgs $args): void
    {
        $entity = $args->getObject();
        $user = $this->getUser();
        if (!$user) {
            return;
        }

        if (method_exists($entity, 'assignCreator') && $entity->getCreatedBy() === null) {
            $entity->assignCreator($user);
        }
        if (method_exists($entity, 'assignUpdater') && $entity->getUpdatedBy() === null) {
            $entity->assignUpdater($user);
        }
    }

    public function preUpdate(PreUpdateEventArgs $args): void
    {
        $entity = $args->getObject();
        $user = $this->getUser();
        if (!$user) {
            return;
        }

        if (method_exists($entity, 'assignUpdater')) {
            $entity->assignUpdater($user);
        }
    }

    private function getUser(): ?User
    {
        $token = $this->tokenStorage->getToken();
        if (!$token) {
            return null;
        }
        $user = $token->getUser();
        return $user instanceof User ? $user : null;
    }
}
