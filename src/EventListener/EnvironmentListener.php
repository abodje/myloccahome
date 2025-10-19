<?php

namespace App\EventListener;

use App\Repository\EnvironmentRepository;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpKernel\Event\RequestEvent;
use Symfony\Component\HttpKernel\KernelEvents;

class EnvironmentListener implements EventSubscriberInterface
{
    private EnvironmentRepository $environmentRepository;

    public function __construct(EnvironmentRepository $environmentRepository)
    {
        $this->environmentRepository = $environmentRepository;
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

        // Vérifier si on est dans une route d'environnement
        if (preg_match('/^\/env\/([a-zA-Z0-9\-]+)/', $request->getPathInfo(), $matches)) {
            $envCode = $matches[1];

            // Chercher l'environnement
            $environment = $this->environmentRepository->findBySubdomain($envCode);

            if ($environment && $environment->isActive()) {
                // Stocker l'environnement dans les attributs de la requête
                $request->attributes->set('_environment', $environment);
                $request->attributes->set('_environment_code', $envCode);

                // Stocker en session pour les autres requêtes
                $request->getSession()->set('current_environment', $envCode);
                $request->getSession()->set('current_environment_id', $environment->getId());
            }
        }

        // Pour les autres routes, vérifier si on a un environnement en session
        $currentEnvCode = $request->getSession()->get('current_environment');
        if ($currentEnvCode && !$request->attributes->has('_environment')) {
            $environment = $this->environmentRepository->findBySubdomain($currentEnvCode);
            if ($environment && $environment->isActive()) {
                $request->attributes->set('_environment', $environment);
                $request->attributes->set('_environment_code', $currentEnvCode);
            }
        }
    }
}
