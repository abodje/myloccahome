<?php

namespace App\Service;

use Symfony\Bundle\SecurityBundle\Security;

/**
 * Service de gestion des menus avec ACL (Access Control List)
 * Gère l'affichage des menus selon les rôles des utilisateurs ET les fonctionnalités du plan
 */
class MenuService
{
    public function __construct(
        private Security $security,
        private SettingsService $settingsService,
        private FeatureAccessService $featureAccessService
    ) {
    }

    /**
     * Retourne la structure complète du menu avec les permissions
     */
    public function getMenuStructure(): array
    {
        return [
            'dashboard' => [
                'label' => 'Mon tableau de bord',
                'icon' => 'bi-speedometer2',
                'route' => 'app_dashboard',
                'roles' => ['ROLE_USER', 'ROLE_TENANT', 'ROLE_MANAGER', 'ROLE_ADMIN'],
                'order' => 1,
            ],
            'maintenance_requests' => [
                'label' => 'Mes demandes',
                'icon' => 'bi-tools',
                'route' => 'app_maintenance_request_index',
                'roles' => ['ROLE_USER', 'ROLE_TENANT', 'ROLE_MANAGER', 'ROLE_ADMIN'],
                'order' => 2,
                'badge' => 'pending_requests', // Compteur dynamique
                'required_feature' => 'maintenance_requests', // ✅ Nécessite plan Professional+
            ],
            'properties' => [
                'label' => 'Mes biens',
                'icon' => 'bi-building',
                'route' => 'app_property_index',
                'roles' => ['ROLE_USER', 'ROLE_TENANT', 'ROLE_MANAGER', 'ROLE_ADMIN'],
                'order' => 3,
            ],
            'tenants' => [
                'label' => 'Locataires',
                'icon' => 'bi-people',
                'route' => 'app_tenant_index',
                'roles' => ['ROLE_MANAGER', 'ROLE_ADMIN'],
                'order' => 4,
            ],
            'leases' => [
                'label' => 'Baux',
                'icon' => 'bi-file-text',
                'route' => 'app_lease_index',
                'roles' => ['ROLE_MANAGER', 'ROLE_ADMIN'],
                'order' => 5,
            ],
            'payments' => [
                'label' => 'Mes paiements',
                'icon' => 'bi-credit-card',
                'route' => 'app_payment_index',
                'roles' => ['ROLE_USER', 'ROLE_TENANT', 'ROLE_MANAGER', 'ROLE_ADMIN'],
                'order' => 6,
            ],
            'advance_payments' => [
                'label' => 'Acomptes',
                'icon' => 'bi-piggy-bank',
                'route' => 'app_advance_payment_index',
                'roles' => ['ROLE_TENANT'],
                'order' => 6.5,
                'visible_condition' => 'allow_advance_payments',
            ],
            'accounting' => [
                'label' => 'Ma comptabilité',
                'icon' => 'bi-calculator',
                'route' => 'app_accounting_index',
                'roles' => ['ROLE_USER', 'ROLE_TENANT', 'ROLE_MANAGER', 'ROLE_ADMIN'],
                'order' => 7,
                'required_feature' => 'accounting', // ✅ Nécessite plan Professional+
            ],
            'documents' => [
                'label' => 'Mes documents',
                'icon' => 'bi-folder',
                'route' => 'app_document_index',
                'roles' => ['ROLE_USER', 'ROLE_TENANT', 'ROLE_MANAGER', 'ROLE_ADMIN'],
                'order' => 8,
            ],
            'messages' => [
                'label' => 'Messagerie',
                'icon' => 'bi-chat-dots',
                'route' => 'app_message_index',
                'roles' => ['ROLE_USER', 'ROLE_TENANT', 'ROLE_MANAGER', 'ROLE_ADMIN'],
                'order' => 9,
                'badge_type' => 'danger',
                'badge_value' => 'unread_count',
            ],
            'subscription' => [
                'label' => 'Mon Abonnement',
                'icon' => 'bi-credit-card-2-back',
                'route' => 'app_subscription_dashboard',
                'roles' => ['ROLE_ADMIN'],
                'order' => 9.5,
            ],
            'divider_admin' => [
                'type' => 'divider',
                'label' => 'ADMINISTRATION',
                'roles' => ['ROLE_MANAGER', 'ROLE_ADMIN'],
                'order' => 100,
            ],
            'admin_dashboard' => [
                'label' => 'Administration',
                'icon' => 'bi-gear',
                'route' => 'app_admin_dashboard',
                'roles' => ['ROLE_ADMIN'],
                'order' => 101,
            ],
            'admin_users' => [
                'label' => 'Utilisateurs',
                'icon' => 'bi-person-badge',
                'route' => 'app_admin_users',
                'roles' => ['ROLE_ADMIN'],
                'order' => 102,
            ],
            'admin_tasks' => [
                'label' => 'Tâches automatisées',
                'icon' => 'bi-clock-history',
                'route' => 'app_admin_task_index',
                'roles' => ['ROLE_ADMIN'],
                'order' => 103,
            ],
            'admin_email_templates' => [
                'label' => 'Templates emails',
                'icon' => 'bi-envelope',
                'route' => 'app_admin_email_template_index',
                'roles' => ['ROLE_ADMIN'],
                'order' => 104,
            ],
            'admin_menus' => [
                'label' => 'Gestion des menus',
                'icon' => 'bi-menu-button-wide',
                'route' => 'app_admin_menu_index',
                'roles' => ['ROLE_ADMIN'],
                'order' => 104,
            ],
            'admin_contract_config' => [
                'label' => 'Configuration contrats',
                'icon' => 'bi-file-earmark-text',
                'route' => 'app_admin_contract_config_index',
                'roles' => ['ROLE_ADMIN'],
                'order' => 104,
            ],
            'admin_settings' => [
                'label' => 'Paramètres',
                'icon' => 'bi-sliders',
                'route' => 'app_admin_settings_index',
                'roles' => ['ROLE_ADMIN'],
                'order' => 105,
                'submenu' => [
                    'settings_app' => [
                        'label' => 'Application',
                        'route' => 'app_admin_app_settings',
                        'roles' => ['ROLE_ADMIN'],
                    ],
                    'settings_currencies' => [
                        'label' => 'Devises',
                        'route' => 'app_admin_currencies',
                        'roles' => ['ROLE_ADMIN'],
                    ],
                    'settings_email' => [
                        'label' => 'Email',
                        'route' => 'app_admin_email_settings',
                        'roles' => ['ROLE_ADMIN'],
                    ],
                    'settings_payment' => [
                        'label' => 'Paiements',
                        'route' => 'app_admin_payment_settings',
                        'roles' => ['ROLE_ADMIN'],
                    ],
                    'settings_cinetpay' => [
                        'label' => '💳 Paiement en ligne',
                        'route' => 'app_admin_cinetpay_settings',
                        'roles' => ['ROLE_ADMIN'],
                    ],
                    'settings_orange_sms' => [
                        'label' => '📱 Orange SMS',
                        'route' => 'app_admin_orange_sms_settings',
                        'roles' => ['ROLE_ADMIN'],
                    ],
                    'settings_maintenance_system' => [
                        'label' => 'Maintenance système',
                        'route' => 'app_admin_maintenance',
                        'roles' => ['ROLE_ADMIN'],
                    ],
                ],
            ],
            'admin_reports' => [
                'label' => 'Rapports',
                'icon' => 'bi-graph-up',
                'route' => 'app_admin_reports',
                'roles' => ['ROLE_MANAGER', 'ROLE_ADMIN'],
                'order' => 106,
            ],
        ];
    }

    /**
     * Retourne uniquement les menus accessibles à l'utilisateur connecté
     */
    public function getAuthorizedMenu(): array
    {
        $allMenus = $this->getMenuStructure();
        $authorizedMenus = [];

        foreach ($allMenus as $key => $menu) {
            if ($this->canAccessMenuItem($menu)) {
                // Filtrer les sous-menus si présents
                if (isset($menu['submenu'])) {
                    $authorizedSubmenu = [];
                    foreach ($menu['submenu'] as $subKey => $subItem) {
                        if ($this->canAccessMenuItem($subItem)) {
                            $authorizedSubmenu[$subKey] = $subItem;
                        }
                    }
                    $menu['submenu'] = $authorizedSubmenu;

                    // Ne pas afficher le menu parent si aucun sous-menu n'est accessible
                    if (empty($authorizedSubmenu)) {
                        continue;
                    }
                }

                $authorizedMenus[$key] = $menu;
            }
        }

        // Trier par ordre
        uasort($authorizedMenus, function($a, $b) {
            return ($a['order'] ?? 999) <=> ($b['order'] ?? 999);
        });

        return $authorizedMenus;
    }

    /**
     * Vérifie si l'utilisateur peut accéder à un élément de menu
     */
    public function canAccessMenuItem(array $menuItem): bool
    {
        // Les dividers sont toujours accessibles s'ils ont des éléments enfants accessibles
        if (($menuItem['type'] ?? null) === 'divider') {
            return $this->hasAnyRole($menuItem['roles'] ?? []);
        }

        // Vérifier les rôles requis
        if (!isset($menuItem['roles']) || empty($menuItem['roles'])) {
            return false;
        }

        // Vérifier les rôles
        if (!$this->hasAnyRole($menuItem['roles'])) {
            return false;
        }

        // Vérifier la condition de visibilité (paramètre système)
        if (isset($menuItem['visible_condition'])) {
            $settingValue = $this->settingsService->get($menuItem['visible_condition'], false);
            if (!$settingValue) {
                return false;
            }
        }

        // ✅ NOUVEAU : Vérifier la fonctionnalité requise selon le plan d'abonnement
        if (isset($menuItem['required_feature'])) {
            /** @var \App\Entity\User|null $user */
            $user = $this->security->getUser();

            if (!$user || !method_exists($user, 'getOrganization') || !$user->getOrganization()) {
                return false; // Pas d'organization = pas d'accès
            }

            if (!$this->featureAccessService->hasAccess($user->getOrganization(), $menuItem['required_feature'])) {
                return false; // Fonctionnalité non disponible dans le plan
            }
        }

        return true;
    }

    /**
     * Vérifie si l'utilisateur a au moins un des rôles spécifiés
     */
    private function hasAnyRole(array $roles): bool
    {
        if (empty($roles)) {
            return false;
        }

        foreach ($roles as $role) {
            if ($this->security->isGranted($role)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Retourne le nombre de demandes en attente (pour badge)
     */
    public function getPendingRequestsCount(): int
    {
        // TODO: Implémenter le comptage via repository
        // Pour l'instant retourner 0
        return 0;
    }

    /**
     * Vérifie si une route est accessible à l'utilisateur
     */
    public function canAccessRoute(string $route): bool
    {
        $allMenus = $this->getMenuStructure();

        foreach ($allMenus as $menu) {
            if (($menu['route'] ?? null) === $route) {
                return $this->canAccessMenuItem($menu);
            }

            // Vérifier les sous-menus
            if (isset($menu['submenu'])) {
                foreach ($menu['submenu'] as $subItem) {
                    if (($subItem['route'] ?? null) === $route) {
                        return $this->canAccessMenuItem($subItem);
                    }
                }
            }
        }

        return false;
    }

    /**
     * Retourne les permissions d'un utilisateur
     */
    public function getUserPermissions(): array
    {
        $user = $this->security->getUser();

        if (!$user) {
            return [];
        }

        $roles = $user->getRoles();

        return [
            'roles' => $roles,
            'is_admin' => $this->security->isGranted('ROLE_ADMIN'),
            'is_manager' => $this->security->isGranted('ROLE_MANAGER'),
            'is_tenant' => $this->security->isGranted('ROLE_TENANT'),
            'can_manage_users' => $this->security->isGranted('ROLE_ADMIN'),
            'can_manage_properties' => $this->security->isGranted('ROLE_MANAGER') || $this->security->isGranted('ROLE_ADMIN'),
            'can_view_accounting' => $this->security->isGranted('ROLE_MANAGER') || $this->security->isGranted('ROLE_ADMIN'),
            'can_manage_settings' => $this->security->isGranted('ROLE_ADMIN'),
        ];
    }
}

