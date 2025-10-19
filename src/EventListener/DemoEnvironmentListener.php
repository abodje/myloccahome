<?php

namespace App\EventListener;

use Symfony\Component\HttpKernel\Event\RequestEvent;
use Symfony\Component\HttpKernel\KernelEvents;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;
use Doctrine\ORM\EntityManagerInterface;
use App\Entity\Organization;

class DemoEnvironmentListener implements EventSubscriberInterface
{
    private TokenStorageInterface $tokenStorage;
    private EntityManagerInterface $entityManager;

    public function __construct(TokenStorageInterface $tokenStorage, EntityManagerInterface $entityManager)
    {
        $this->tokenStorage = $tokenStorage;
        $this->entityManager = $entityManager;
    }

    public static function getSubscribedEvents(): array
    {
        return [
            KernelEvents::REQUEST => ['onKernelRequest', 10],
        ];
    }

    public function onKernelRequest(RequestEvent $event): void
    {
        $request = $event->getRequest();

        // Vérifier si on est dans une route de démo
        if (preg_match('/^\/demo\/([a-zA-Z0-9]{8,})/', $request->getPathInfo(), $matches)) {
            $demoCode = $matches[1];

            // Vérifier si l'utilisateur est connecté
            $token = $this->tokenStorage->getToken();
            if (!$token || !$token->getUser()) {
                return;
            }

            $user = $token->getUser();

            // Vérifier si c'est un utilisateur valide
            if (!method_exists($user, 'getId')) {
                return;
            }

            // Stocker le code de démo en session pour les autres parties de l'application
            $request->getSession()->set('demo_code', $demoCode);
            $request->getSession()->set('demo_user_id', $user->getId());

            // Marquer la requête comme étant dans un environnement de démo
            $request->attributes->set('_demo_environment', true);
            $request->attributes->set('_demo_code', $demoCode);

            // Ajouter des informations de démo aux attributs de la requête
            $request->attributes->set('_demo_user', $user);
        }

        // Pour TOUTES les routes, vérifier si on a un code de démo en session
        $demoCode = $request->getSession()->get('demo_code');
        if ($demoCode && !$request->attributes->has('_demo_environment')) {
            $request->attributes->set('_demo_environment', true);
            $request->attributes->set('_demo_code', $demoCode);

            // Récupérer l'utilisateur de démo depuis la session
            $demoUserId = $request->getSession()->get('demo_user_id');
            if ($demoUserId) {
                $demoUser = $this->entityManager->getRepository(\App\Entity\User::class)->find($demoUserId);
                if ($demoUser) {
                    $request->attributes->set('_demo_user', $demoUser);
                }
            }
        }
    }
}
