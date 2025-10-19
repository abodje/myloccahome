<?php

namespace App\Service;

use App\Entity\Organization;
use App\Entity\Subscription;
use App\Entity\Plan;
use Doctrine\ORM\EntityManagerInterface;

/**
 * Service de gestion de l'accès aux fonctionnalités selon les plans d'abonnement
 */
class FeatureAccessService
{
    private EntityManagerInterface $entityManager;

    public function __construct(EntityManagerInterface $entityManager)
    {
        $this->entityManager = $entityManager;
    }

    /**
     * Vérifie si une organisation a accès à une fonctionnalité spécifique
     */
    public function hasAccess(Organization $organization, string $feature): bool
    {
        // Récupérer l'abonnement actif de l'organisation
        $subscription = $organization->getActiveSubscription();

        if (!$subscription) {
            return false; // Pas d'abonnement = pas d'accès
        }

        $plan = $subscription->getPlan();

        if (!$plan) {
            return false; // Pas de plan = pas d'accès
        }

        // Récupérer les fonctionnalités du plan
        $planFeatures = $plan->getFeatures();

        if (!is_array($planFeatures)) {
            return false;
        }

        // Vérifier si la fonctionnalité est incluse dans le plan
        return in_array($feature, $planFeatures);
    }

    /**
     * Retourne toutes les fonctionnalités disponibles pour une organisation
     */
    public function getAvailableFeatures(Organization $organization): array
    {
        $subscription = $organization->getActiveSubscription();

        if (!$subscription) {
            return [];
        }

        $plan = $subscription->getPlan();

        if (!$plan) {
            return [];
        }

        $features = $plan->getFeatures();

        return is_array($features) ? $features : [];
    }

    /**
     * Retourne les limites du plan pour une organisation
     */
    public function getPlanLimits(Organization $organization): array
    {
        $subscription = $organization->getActiveSubscription();

        if (!$subscription) {
            return [];
        }

        $plan = $subscription->getPlan();

        if (!$plan) {
            return [];
        }

        return [
            'max_properties' => $plan->getMaxProperties(),
            'max_tenants' => $plan->getMaxTenants(),
            'max_users' => $plan->getMaxUsers(),
            'max_documents' => $plan->getMaxDocuments(),
        ];
    }

    /**
     * Vérifie si une organisation a atteint une limite spécifique
     */
    public function hasReachedLimit(Organization $organization, string $limitType): bool
    {
        $limits = $this->getPlanLimits($organization);
        $currentCount = $this->getCurrentCount($organization, $limitType);

        return $currentCount >= ($limits[$limitType] ?? 0);
    }

    /**
     * Retourne le nombre actuel d'éléments d'un type donné pour une organisation
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
     * Retourne les fonctionnalités manquantes pour un plan
     */
    public function getMissingFeatures(Organization $organization): array
    {
        $allFeatures = $this->getAllAvailableFeatures();
        $availableFeatures = $this->getAvailableFeatures($organization);

        return array_diff($allFeatures, $availableFeatures);
    }

    /**
     * Retourne toutes les fonctionnalités disponibles dans le système
     */
    private function getAllAvailableFeatures(): array
    {
        return [
            'dashboard',
            'properties_management',
            'tenants_management',
            'lease_management',
            'payment_tracking',
            'documents',
            'maintenance_requests',
            'accounting',
            'reports',
            'advanced_reports',
            'api_access',
            'custom_branding',
            'priority_support',
            'multi_company',
            'environment_management',
            'advanced_analytics',
            'white_label',
            'sso',
        ];
    }
}
