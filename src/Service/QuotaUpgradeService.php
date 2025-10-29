<?php

namespace App\Service;

use App\Entity\Organization;
use App\Entity\Plan;
use App\Repository\PlanRepository;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;

/**
 * Service pour gérer les propositions d'upgrade lorsque les quotas sont atteints
 */
class QuotaUpgradeService
{
    public function __construct(
        private EntityManagerInterface $entityManager,
        private PlanRepository $planRepository,
        private FeatureAccessService $featureAccessService,
        private LoggerInterface $logger
    ) {
    }

    /**
     * Vérifie si l'organisation a atteint un ou plusieurs quotas
     */
    public function checkQuotaLimits(Organization $organization): array
    {
        $reachedLimits = [];
        $limits = $this->featureAccessService->getPlanLimits($organization);

        $limitTypes = ['max_properties', 'max_tenants', 'max_users', 'max_documents'];
        
        foreach ($limitTypes as $limitType) {
            $limitValue = $limits[$limitType] ?? null;
            
            if ($limitValue === null) {
                continue; // Illimité
            }

            $currentCount = $this->getCurrentCount($organization, $limitType);
            
            if ($currentCount >= $limitValue) {
                $reachedLimits[$limitType] = [
                    'current' => $currentCount,
                    'limit' => $limitValue,
                    'type' => $this->getLimitLabel($limitType),
                ];
            }
        }

        return $reachedLimits;
    }

    /**
     * Retourne le plan supérieur recommandé pour une organisation
     */
    public function getRecommendedUpgradePlan(Organization $organization): ?Plan
    {
        $subscription = $organization->getActiveSubscription();
        
        if (!$subscription) {
            return null;
        }

        $currentPlan = $subscription->getPlan();
        
        if (!$currentPlan) {
            return null;
        }

        // Si déjà sur Enterprise, pas de plan supérieur
        if ($currentPlan->getSlug() === 'enterprise') {
            return null;
        }

        // Définir l'ordre des plans
        $planOrder = [
            'freemium' => 1,
            'starter' => 2,
            'professional' => 3,
            'enterprise' => 4,
        ];

        $currentOrder = $planOrder[$currentPlan->getSlug()] ?? 0;
        $nextOrder = $currentOrder + 1;

        // Trouver le plan suivant dans l'ordre
        $allPlans = $this->planRepository->findActivePlans();
        
        foreach ($allPlans as $plan) {
            $planSlug = $plan->getSlug();
            $planOrderValue = $planOrder[$planSlug] ?? 999;
            
            if ($planOrderValue === $nextOrder) {
                return $plan;
            }
        }

        return null;
    }

    /**
     * Vérifie les quotas et retourne les informations pour proposer un upgrade
     */
    public function getUpgradeRecommendation(Organization $organization): ?array
    {
        $reachedLimits = $this->checkQuotaLimits($organization);
        
        if (empty($reachedLimits)) {
            return null; // Aucun quota atteint
        }

        $recommendedPlan = $this->getRecommendedUpgradePlan($organization);
        
        if (!$recommendedPlan) {
            return null; // Pas de plan supérieur disponible
        }

        $currentPlan = $organization->getActiveSubscription()?->getPlan();
        $currentLimits = $this->featureAccessService->getPlanLimits($organization);
        $recommendedLimits = [
            'max_properties' => $recommendedPlan->getMaxProperties(),
            'max_tenants' => $recommendedPlan->getMaxTenants(),
            'max_users' => $recommendedPlan->getMaxUsers(),
            'max_documents' => $recommendedPlan->getMaxDocuments(),
        ];

        return [
            'has_reached_quota' => true,
            'reached_limits' => $reachedLimits,
            'current_plan' => [
                'name' => $currentPlan?->getName(),
                'slug' => $currentPlan?->getSlug(),
                'limits' => $currentLimits,
            ],
            'recommended_plan' => [
                'id' => $recommendedPlan->getId(),
                'name' => $recommendedPlan->getName(),
                'slug' => $recommendedPlan->getSlug(),
                'description' => $recommendedPlan->getDescription(),
                'monthly_price' => $recommendedPlan->getMonthlyPrice(),
                'yearly_price' => $recommendedPlan->getYearlyPrice(),
                'currency' => $recommendedPlan->getCurrency(),
                'limits' => $recommendedLimits,
                'features' => $recommendedPlan->getFeatures(),
            ],
            'upgrade_url' => '/inscription/plans?plan=' . $recommendedPlan->getSlug(),
        ];
    }

    /**
     * Vérifie si une ressource spécifique peut être ajoutée
     */
    public function canAddResource(Organization $organization, string $resourceType): array
    {
        $limitType = 'max_' . $resourceType;
        $limits = $this->featureAccessService->getPlanLimits($organization);
        $limit = $limits[$limitType] ?? null;

        // Si null, c'est illimité
        if ($limit === null) {
            return [
                'allowed' => true,
                'reason' => 'unlimited',
            ];
        }

        $currentCount = $this->getCurrentCount($organization, $limitType);

        if ($currentCount >= $limit) {
            $recommendation = $this->getUpgradeRecommendation($organization);
            
            return [
                'allowed' => false,
                'reason' => 'quota_reached',
                'current' => $currentCount,
                'limit' => $limit,
                'type' => $this->getLimitLabel($limitType),
                'upgrade_recommendation' => $recommendation,
            ];
        }

        return [
            'allowed' => true,
            'current' => $currentCount,
            'limit' => $limit,
            'remaining' => $limit - $currentCount,
        ];
    }

    /**
     * Retourne le nombre actuel d'une ressource
     */
    private function getCurrentCount(Organization $organization, string $limitType): int
    {
        switch ($limitType) {
            case 'max_properties':
                return $this->entityManager->getRepository(\App\Entity\Property::class)
                    ->count(['organization' => $organization]);

            case 'max_tenants':
                return $this->entityManager->getRepository(\App\Entity\Tenant::class)
                    ->count(['organization' => $organization]);

            case 'max_users':
                return $this->entityManager->getRepository(\App\Entity\User::class)
                    ->count(['organization' => $organization]);

            case 'max_documents':
                return $this->entityManager->getRepository(\App\Entity\Document::class)
                    ->count(['organization' => $organization]);

            default:
                return 0;
        }
    }

    /**
     * Retourne le label lisible d'un type de limite
     */
    private function getLimitLabel(string $limitType): string
    {
        return match($limitType) {
            'max_properties' => 'Propriétés',
            'max_tenants' => 'Locataires',
            'max_users' => 'Utilisateurs',
            'max_documents' => 'Documents',
            default => $limitType,
        };
    }
}

