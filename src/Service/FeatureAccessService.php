<?php

namespace App\Service;

use App\Entity\Organization;
use App\Entity\User;

/**
 * Service pour gérer l'accès aux fonctionnalités selon le plan d'abonnement
 */
class FeatureAccessService
{
    /**
     * Mapping des fonctionnalités vers leurs noms d'affichage
     */
    private const FEATURE_LABELS = [
        'dashboard' => 'Tableau de bord personnalisé',
        'properties_management' => 'Gestion des propriétés',
        'tenants_management' => 'Gestion des locataires',
        'lease_management' => 'Gestion des baux',
        'payment_tracking' => 'Suivi des paiements',
        'documents' => 'Gestion des documents',
        'accounting' => 'Comptabilité avancée',
        'maintenance_requests' => 'Demandes de maintenance',
        'online_payments' => 'Paiements en ligne (CinetPay)',
        'advance_payments' => 'Paiements anticipés (Acomptes)',
        'reports' => 'Rapports et statistiques',
        'email_notifications' => 'Notifications par email',
        'sms_notifications' => 'Notifications par SMS',
        'custom_branding' => 'Personnalisation (Logo, Couleurs)',
        'api_access' => 'Accès API REST',
        'priority_support' => 'Support prioritaire',
        'multi_currency' => 'Multi-devises',
        'rent_receipts' => 'Quittances de loyer automatiques',
        'payment_notices' => 'Avis d\'échéances automatiques',
        'messaging' => 'Messagerie interne',
        'tasks_automation' => 'Automatisation des tâches',
    ];

    /**
     * Mapping des fonctionnalités vers les routes/sections de l'app
     */
    private const FEATURE_ROUTES = [
        'accounting' => ['app_accounting_index'],
        'maintenance_requests' => ['app_maintenance_request_index', 'app_maintenance_request_new'],
        'online_payments' => ['app_online_payment_pay_rent', 'app_online_payment_pay_advance'],
        'advance_payments' => ['app_advance_payment_index', 'app_advance_payment_new'],
        'reports' => ['app_admin_reports'],
        'sms_notifications' => ['app_admin_orange_sms_settings'],
        'custom_branding' => ['app_admin_branding'],
        'api_access' => ['app_api_index'],
    ];

    public function __construct(
        private SettingsService $settingsService
    ) {
    }

    /**
     * Vérifie si une organisation a accès à une fonctionnalité
     */
    public function hasAccess(Organization $organization, string $feature): bool
    {
        // Récupérer les fonctionnalités de l'organisation
        $orgFeatures = $organization->getFeatures() ?? [];

        // Vérifier si la fonctionnalité est dans la liste
        return in_array($feature, $orgFeatures);
    }

    /**
     * Vérifie si un utilisateur a accès à une fonctionnalité
     */
    public function userHasAccess(?User $user, string $feature): bool
    {
        if (!$user || !$user->getOrganization()) {
            return false;
        }

        return $this->hasAccess($user->getOrganization(), $feature);
    }

    /**
     * Retourne le label d'une fonctionnalité
     */
    public function getFeatureLabel(string $feature): string
    {
        return self::FEATURE_LABELS[$feature] ?? ucfirst(str_replace('_', ' ', $feature));
    }

    /**
     * Retourne toutes les fonctionnalités avec leurs labels
     */
    public function getAllFeatures(): array
    {
        return self::FEATURE_LABELS;
    }

    /**
     * Retourne les fonctionnalités d'une organisation avec leurs labels
     */
    public function getOrganizationFeatures(Organization $organization): array
    {
        $features = $organization->getFeatures() ?? [];
        $result = [];

        foreach ($features as $feature) {
            $result[$feature] = $this->getFeatureLabel($feature);
        }

        return $result;
    }

    /**
     * Vérifie si une organisation a atteint une limite
     */
    public function hasReachedLimit(Organization $organization, string $resourceType): bool
    {
        $currentCount = match($resourceType) {
            'properties' => $organization->getProperties()->count(),
            'tenants' => $organization->getTenants()->count(),
            'users' => $organization->getUsers()->count(),
            'documents' => 0, // TODO: Implémenter le comptage
            default => 0
        };

        $maxLimit = $organization->getSetting('max_' . $resourceType);

        // null = illimité
        if ($maxLimit === null) {
            return false;
        }

        return $currentCount >= $maxLimit;
    }

    /**
     * Retourne les informations de limite pour un type de ressource
     */
    public function getLimitInfo(Organization $organization, string $resourceType): array
    {
        $current = match($resourceType) {
            'properties' => $organization->getProperties()->count(),
            'tenants' => $organization->getTenants()->count(),
            'users' => $organization->getUsers()->count(),
            'documents' => 0, // TODO
            default => 0
        };

        $max = $organization->getSetting('max_' . $resourceType);

        return [
            'current' => $current,
            'max' => $max,
            'percentage' => $max ? ($current / $max) * 100 : 0,
            'remaining' => $max ? max(0, $max - $current) : null,
            'is_unlimited' => $max === null,
            'is_reached' => $max ? $current >= $max : false,
        ];
    }

    /**
     * Retourne le message de blocage pour une fonctionnalité
     */
    public function getFeatureBlockMessage(string $feature, Organization $organization): string
    {
        $planName = $organization->getActiveSubscription()?->getPlan()->getName() ?? 'Freemium';

        $messages = [
            'accounting' => "La comptabilité avancée est disponible à partir du plan Professional. Vous êtes actuellement sur le plan {$planName}.",
            'online_payments' => "Les paiements en ligne sont disponibles à partir du plan Professional.",
            'advance_payments' => "Les paiements anticipés sont disponibles à partir du plan Professional.",
            'sms_notifications' => "Les notifications SMS sont disponibles uniquement dans le plan Enterprise.",
            'custom_branding' => "La personnalisation de votre marque est disponible dans le plan Enterprise.",
            'api_access' => "L'accès API est disponible uniquement dans le plan Enterprise.",
        ];

        return $messages[$feature] ?? "Cette fonctionnalité n'est pas disponible dans votre plan actuel.";
    }

    /**
     * Retourne le plan minimum requis pour une fonctionnalité
     */
    public function getRequiredPlan(string $feature): string
    {
        $requirements = [
            'dashboard' => 'freemium',
            'properties_management' => 'freemium',
            'tenants_management' => 'freemium',
            'lease_management' => 'freemium',
            'payment_tracking' => 'freemium',
            'documents' => 'starter',
            'accounting' => 'professional',
            'maintenance_requests' => 'professional',
            'online_payments' => 'professional',
            'advance_payments' => 'professional',
            'reports' => 'professional',
            'email_notifications' => 'professional',
            'sms_notifications' => 'enterprise',
            'custom_branding' => 'enterprise',
            'api_access' => 'enterprise',
            'priority_support' => 'enterprise',
            'multi_currency' => 'enterprise',
        ];

        return $requirements[$feature] ?? 'starter';
    }

    /**
     * Retourne les icônes pour chaque fonctionnalité
     */
    public function getFeatureIcon(string $feature): string
    {
        $icons = [
            'dashboard' => 'bi-speedometer2',
            'properties_management' => 'bi-building',
            'tenants_management' => 'bi-people',
            'lease_management' => 'bi-file-text',
            'payment_tracking' => 'bi-credit-card',
            'documents' => 'bi-folder',
            'accounting' => 'bi-calculator',
            'maintenance_requests' => 'bi-tools',
            'online_payments' => 'bi-credit-card-2-front',
            'advance_payments' => 'bi-piggy-bank',
            'reports' => 'bi-graph-up',
            'email_notifications' => 'bi-envelope',
            'sms_notifications' => 'bi-chat-dots',
            'custom_branding' => 'bi-palette',
            'api_access' => 'bi-code-slash',
            'priority_support' => 'bi-headset',
            'multi_currency' => 'bi-currency-exchange',
            'rent_receipts' => 'bi-file-earmark-pdf',
            'payment_notices' => 'bi-file-earmark-text',
            'messaging' => 'bi-chat-left-dots',
            'tasks_automation' => 'bi-robot',
        ];

        return $icons[$feature] ?? 'bi-check-circle';
    }
}

