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

    /**
     * Vérifie si un utilisateur a accès à une fonctionnalité
     */
    public function userHasAccess($user, string $feature): bool
    {
        if (!$user || !method_exists($user, 'getOrganization')) {
            return false;
        }

        $organization = $user->getOrganization();
        if (!$organization) {
            return false;
        }

        return $this->hasAccess($organization, $feature);
    }

    /**
     * Retourne le label d'une fonctionnalité
     */
    public function getFeatureLabel(string $feature): string
    {
        $labels = [
            'dashboard' => 'Tableau de bord',
            'properties_management' => 'Gestion des biens',
            'tenants_management' => 'Gestion des locataires',
            'lease_management' => 'Gestion des baux',
            'payment_tracking' => 'Suivi des paiements',
            'documents' => 'Gestion documentaire',
            'maintenance_requests' => 'Demandes de maintenance',
            'accounting' => 'Comptabilité',
            'reports' => 'Rapports standards',
            'advanced_reports' => 'Rapports avancés',
            'api_access' => 'Accès API',
            'custom_branding' => 'Personnalisation de marque',
            'priority_support' => 'Support prioritaire',
            'multi_company' => 'Multi-sociétés',
            'environment_management' => 'Gestion des environnements',
            'advanced_analytics' => 'Analyses avancées',
            'white_label' => 'Marque blanche',
            'sso' => 'Authentification unique (SSO)',
        ];

        return $labels[$feature] ?? ucfirst(str_replace('_', ' ', $feature));
    }

    /**
     * Retourne l'icône Bootstrap d'une fonctionnalité
     */
    public function getFeatureIcon(string $feature): string
    {
        $icons = [
            'dashboard' => 'bi-speedometer2',
            'properties_management' => 'bi-building',
            'tenants_management' => 'bi-people',
            'lease_management' => 'bi-file-text',
            'payment_tracking' => 'bi-cash-coin',
            'documents' => 'bi-folder',
            'maintenance_requests' => 'bi-tools',
            'accounting' => 'bi-calculator',
            'reports' => 'bi-graph-up',
            'advanced_reports' => 'bi-pie-chart',
            'api_access' => 'bi-code-slash',
            'custom_branding' => 'bi-palette',
            'priority_support' => 'bi-headset',
            'multi_company' => 'bi-building-gear',
            'environment_management' => 'bi-server',
            'advanced_analytics' => 'bi-bar-chart',
            'white_label' => 'bi-tag',
            'sso' => 'bi-key',
        ];

        return $icons[$feature] ?? 'bi-check-circle';
    }

    /**
     * Retourne le message de blocage pour une fonctionnalité
     */
    public function getFeatureBlockMessage(string $feature, Organization $organization): string
    {
        $featureLabel = $this->getFeatureLabel($feature);
        $requiredPlan = $this->getRequiredPlan($feature);

        return "La fonctionnalité « {$featureLabel} » nécessite le plan {$requiredPlan}. " .
               "Veuillez mettre à niveau votre abonnement pour y accéder.";
    }

    /**
     * Retourne le plan minimum requis pour une fonctionnalité
     */
    public function getRequiredPlan(string $feature): string
    {
        $requirements = [
            'dashboard' => 'Freemium',
            'properties_management' => 'Freemium',
            'tenants_management' => 'Freemium',
            'lease_management' => 'Basic',
            'payment_tracking' => 'Basic',
            'documents' => 'Basic',
            'maintenance_requests' => 'Professional',
            'accounting' => 'Professional',
            'reports' => 'Professional',
            'advanced_reports' => 'Enterprise',
            'api_access' => 'Enterprise',
            'custom_branding' => 'Enterprise',
            'priority_support' => 'Enterprise',
            'multi_company' => 'Enterprise',
            'environment_management' => 'Enterprise',
            'advanced_analytics' => 'Enterprise',
            'white_label' => 'Enterprise',
            'sso' => 'Enterprise',
        ];

        return $requirements[$feature] ?? 'Professional';
    }

    /**
     * Retourne les informations de limite pour une ressource donnée
     */
    public function getLimitInfo(Organization $organization, string $resourceType): array
    {
        $limits = $this->getPlanLimits($organization);
        $currentCount = 0;

        // Convertir le type de ressource en clé de limite
        $limitKeyMap = [
            'properties' => 'max_properties',
            'tenants' => 'max_tenants',
            'users' => 'max_users',
            'documents' => 'max_documents',
        ];

        $limitKey = $limitKeyMap[$resourceType] ?? null;
        $max = $limitKey ? ($limits[$limitKey] ?? null) : null;

        if ($limitKey) {
            $currentCount = $this->getCurrentCount($organization, $limitKey);
        }

        $isUnlimited = $max === null || $max === -1;
        $percentage = $isUnlimited || $max === 0 ? 0 : min(100, ($currentCount / $max) * 100);

        return [
            'current' => $currentCount,
            'max' => $isUnlimited ? null : $max,
            'is_unlimited' => $isUnlimited,
            'percentage' => round($percentage, 1),
        ];
    }

    /**
     * Retourne les fonctionnalités de l'organisation avec leurs labels
     */
    public function getOrganizationFeatures(Organization $organization): array
    {
        $features = $this->getAvailableFeatures($organization);
        $result = [];

        foreach ($features as $feature) {
            $result[$feature] = $this->getFeatureLabel($feature);
        }

        return $result;
    }
}
