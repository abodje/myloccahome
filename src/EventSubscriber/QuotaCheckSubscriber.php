<?php

namespace App\EventSubscriber;

use App\Event\ResourceQuotaCheckEvent;
use App\Service\QuotaUpgradeService;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

/**
 * EventSubscriber pour vérifier les quotas avant la création de ressources
 */
class QuotaCheckSubscriber implements EventSubscriberInterface
{
    public function __construct(
        private QuotaUpgradeService $quotaUpgradeService
    ) {
    }

    public static function getSubscribedEvents(): array
    {
        return [
            ResourceQuotaCheckEvent::class => 'onResourceQuotaCheck',
        ];
    }

    public function onResourceQuotaCheck(ResourceQuotaCheckEvent $event): void
    {
        $organization = $event->getOrganization();
        $resourceType = $event->getResourceType();
        
        // Convertir le type de ressource pour le service
        $resourceTypeMap = [
            'properties' => 'properties',
            'tenants' => 'tenants',
            'users' => 'users',
            'documents' => 'documents',
        ];

        $serviceResourceType = $resourceTypeMap[$resourceType] ?? $resourceType;

        // Vérifier si la ressource peut être ajoutée
        $canAdd = $this->quotaUpgradeService->canAddResource($organization, $serviceResourceType);

        if (!$canAdd['allowed']) {
            // Bloquer l'action
            $event->setAllowed(false);
            $event->setRedirectRoute($event->getRedirectRouteName());

            // Ajouter les messages flash
            $event->addFlashMessage('warning', sprintf(
                'Vous avez atteint la limite de %d %s avec votre plan actuel.',
                $canAdd['limit'],
                $canAdd['type']
            ));

            if (isset($canAdd['upgrade_recommendation'])) {
                $recommendation = $canAdd['upgrade_recommendation'];
                $newLimit = $recommendation['recommended_plan']['limits']['max_' . $serviceResourceType] ?? 'illimité';
                
                $event->addFlashMessage('info', sprintf(
                    'Passez au plan %s pour augmenter cette limite à %s %s.',
                    $recommendation['recommended_plan']['name'],
                    $newLimit,
                    $canAdd['type']
                ));

                // Stocker l'ID du plan recommandé dans un attribut de la session pour utilisation ultérieure
                if (isset($recommendation['recommended_plan']['id'])) {
                    $event->addFlashMessage('upgrade_plan_id', (string)$recommendation['recommended_plan']['id']);
                }
            }
        }
    }
}

