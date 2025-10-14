<?php

namespace App\EventSubscriber;

use App\Service\AuditLogService;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\Security\Http\Event\LoginSuccessEvent;
use Symfony\Component\Security\Http\Event\LogoutEvent;

/**
 * Subscriber pour logger automatiquement certaines actions
 */
class AuditLogSubscriber implements EventSubscriberInterface
{
    public function __construct(
        private AuditLogService $auditLogService
    ) {
    }

    public static function getSubscribedEvents(): array
    {
        return [
            LoginSuccessEvent::class => 'onLoginSuccess',
            LogoutEvent::class => 'onLogout',
        ];
    }

    /**
     * Log automatique lors de la connexion
     */
    public function onLoginSuccess(LoginSuccessEvent $event): void
    {
        $user = $event->getUser();

        if (method_exists($user, 'getUserIdentifier')) {
            $this->auditLogService->logLogin($user->getUserIdentifier());
        }
    }

    /**
     * Log automatique lors de la déconnexion
     */
    public function onLogout(LogoutEvent $event): void
    {
        $this->auditLogService->logLogout();
    }
}

