<?php

namespace App\EventSubscriber;

use App\Entity\AuditLog;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\Security\Http\Event\InteractiveLoginEvent;
use Symfony\Component\Security\Http\SecurityEvents;

class LoginAuditSubscriber implements EventSubscriberInterface
{
    public function __construct(
        private EntityManagerInterface $em,
        private RequestStack $requestStack
    ) {}

    public static function getSubscribedEvents(): array
    {
        return [
            SecurityEvents::INTERACTIVE_LOGIN => 'onInteractiveLogin',
        ];
    }

    public function onInteractiveLogin(InteractiveLoginEvent $event): void
    {
        $user = $event->getAuthenticationToken()->getUser();
        if (!is_object($user)) {
            return;
        }

        $request = $this->requestStack->getCurrentRequest();
        $log = new AuditLog();
        $log->setAction('login_success');
        $log->setEntityType('User');
        $log->setEntityId(method_exists($user, 'getId') ? $user->getId() : null);
        $log->setDetails('Connexion réussie');
        $log->setUser($user);
        $log->setIp($request ? $request->getClientIp() : null);
        $this->em->persist($log);
        $this->em->flush();
    }
}
