<?php

namespace App\EventListener;

use App\Service\FeatureAccessService;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\EventDispatcher\Attribute\AsEventListener;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpKernel\Event\RequestEvent;
use Symfony\Component\HttpKernel\KernelEvents;
use Symfony\Component\Routing\RouterInterface;

/**
 * Listener pour bloquer l'accès aux fonctionnalités non autorisées selon le plan
 */
#[AsEventListener(event: KernelEvents::REQUEST, priority: 10)]
class FeatureAccessListener
{
    /**
     * Mapping des routes vers les fonctionnalités requises
     */
    private const ROUTE_FEATURES = [
        'app_accounting_index' => 'accounting',
        'app_accounting_entry_new' => 'accounting',
        'app_accounting_entry_show' => 'accounting',
        'app_accounting_entry_edit' => 'accounting',

        'app_maintenance_request_index' => 'maintenance_requests',
        'app_maintenance_request_new' => 'maintenance_requests',
        'app_maintenance_request_show' => 'maintenance_requests',
        'app_maintenance_request_edit' => 'maintenance_requests',

        'app_online_payment_pay_rent' => 'online_payments',
        'app_online_payment_pay_advance' => 'advance_payments',
        'app_advance_payment_index' => 'advance_payments',

        'app_admin_orange_sms_settings' => 'sms_notifications',
        'app_admin_orange_sms_test' => 'sms_notifications',

        'app_admin_branding' => 'custom_branding',
        'app_api_index' => 'api_access',
    ];

    public function __construct(
        private FeatureAccessService $featureAccessService,
        private Security $security,
        private RouterInterface $router,
        private RequestStack $requestStack
    ) {
    }

    public function __invoke(RequestEvent $event): void
    {
        if (!$event->isMainRequest()) {
            return;
        }

        $request = $event->getRequest();
        $route = $request->attributes->get('_route');

        // Vérifier si la route nécessite une fonctionnalité spécifique
        if (!isset(self::ROUTE_FEATURES[$route])) {
            return;
        }

        $requiredFeature = self::ROUTE_FEATURES[$route];
        $user = $this->security->getUser();

        // Si l'utilisateur n'est pas connecté ou n'a pas d'organisation, laisser passer
        // (le système de sécurité gérera l'accès)
        if (!$user || !$user->getOrganization()) {
            return;
        }

        // Vérifier si l'organisation a accès à la fonctionnalité
        if (!$this->featureAccessService->hasAccess($user->getOrganization(), $requiredFeature)) {
            // Bloquer l'accès
            $session = $this->requestStack->getSession();
            $session->getFlashBag()->add(
                'warning',
                $this->featureAccessService->getFeatureBlockMessage($requiredFeature, $user->getOrganization())
            );

            // Rediriger vers le tableau de bord ou la page d'upgrade
            $response = new RedirectResponse($this->router->generate('app_dashboard'));
            $event->setResponse($response);
        }
    }
}

