<?php

namespace App\Controller;

use App\Repository\PropertyRepository;
use App\Repository\TenantRepository;
use App\Repository\LeaseRepository;
use App\Repository\PaymentRepository;
use App\Repository\MaintenanceRequestRepository;
use App\Repository\ExpenseRepository;
use App\Repository\AccountingEntryRepository;
use App\Repository\ConversationRepository;
use App\Service\DashboardAnalyticsService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

class DashboardController extends AbstractController
{
    #[Route('/', name: 'app_dashboard')]
    public function index(
        PropertyRepository $propertyRepo,
        TenantRepository $tenantRepo,
        LeaseRepository $leaseRepo,
        PaymentRepository $paymentRepo,
        MaintenanceRequestRepository $maintenanceRepo,
        ExpenseRepository $expenseRepo,
        AccountingEntryRepository $accountingRepo,
        ConversationRepository $conversationRepo,
        DashboardAnalyticsService $analyticsService
    ): Response {
        /** @var \App\Entity\User|null $user */
        $user = $this->getUser();

        // Debug: Log user roles for troubleshooting
        if ($user) {
            error_log('DashboardController: User roles: ' . json_encode($user->getRoles()));
        }

        // Adapter les données selon le rôle de l'utilisateur
        if ($user && in_array('ROLE_ADMIN', $user->getRoles())) {
            error_log('DashboardController: Using adminDashboard');
            return $this->adminDashboard($user, $propertyRepo, $tenantRepo, $leaseRepo, $paymentRepo, $maintenanceRepo, $expenseRepo, $accountingRepo, $conversationRepo, $analyticsService);
        } elseif ($user && in_array('ROLE_MANAGER', $user->getRoles())) {
            error_log('DashboardController: Using managerDashboard');
            return $this->managerDashboard($user, $propertyRepo, $tenantRepo, $leaseRepo, $paymentRepo, $maintenanceRepo, $expenseRepo, $accountingRepo, $conversationRepo);
        } elseif ($user && in_array('ROLE_TENANT', $user->getRoles())) {
            error_log('DashboardController: Using tenantDashboard');
            return $this->tenantDashboard($user, $propertyRepo, $leaseRepo, $paymentRepo, $maintenanceRepo, $accountingRepo, $conversationRepo);
        } else {
            // Dashboard par défaut pour les utilisateurs sans rôle spécifique
            error_log('DashboardController: Using defaultDashboard');
            return $this->defaultDashboard($propertyRepo, $tenantRepo, $leaseRepo, $paymentRepo, $maintenanceRepo, $expenseRepo);
        }
    }

    /**
     * Dashboard pour les administrateurs
     */
    private function adminDashboard(
        $user,
        PropertyRepository $propertyRepo,
        TenantRepository $tenantRepo,
        LeaseRepository $leaseRepo,
        PaymentRepository $paymentRepo,
        MaintenanceRequestRepository $maintenanceRepo,
        ExpenseRepository $expenseRepo,
        AccountingEntryRepository $accountingRepo,
        ConversationRepository $conversationRepo,
        DashboardAnalyticsService $analyticsService
    ): Response {

        // Filtrer selon l'organisation/société de l'utilisateur
        $organization = $user->getOrganization();
        $company = $user->getCompany();

        error_log("DashboardController - Admin: organization=" . ($organization ? $organization->getName() : 'null') . ", company=" . ($company ? $company->getName() : 'null'));

        // Statistiques filtrées selon l'organisation/société
        $stats = $this->getFilteredStats($user, $propertyRepo, $tenantRepo, $leaseRepo, $paymentRepo, $maintenanceRepo, $conversationRepo);

        // Revenus et dépenses du mois
        $currentMonth = new \DateTime('first day of this month');
        $nextMonth = new \DateTime('first day of next month');

        $monthlyRevenue = $paymentRepo->getTotalRevenueByPeriod($currentMonth, $nextMonth);
        $monthlyExpenses = $expenseRepo->getTotalExpensesByPeriod($currentMonth, $nextMonth);

        // Dernières activités
        $recentPayments = $paymentRepo->findBy([], ['createdAt' => 'DESC'], 5);
        $recentMaintenanceRequests = $maintenanceRepo->findBy([], ['createdAt' => 'DESC'], 5);
        $urgentRequests = $maintenanceRepo->findUrgentPending();
        $overduePayments = $paymentRepo->findOverdue();

        // 📊 NOUVELLES DONNÉES ANALYTIQUES
        try {
            $monthlyRevenueChart = $analyticsService->getMonthlyRevenueChartData();
            $occupancyRate = $analyticsService->getOccupancyRate();
            $paymentStatistics = $analyticsService->getPaymentStatistics();
            $cashFlowForecast = $analyticsService->getCashFlowForecast();
            $propertiesByType = $analyticsService->getPropertiesByType();
            $leaseExpirationStats = $analyticsService->getLeaseExpirationStats();
            $globalKPIs = $analyticsService->getGlobalKPIs();
            $yearComparison = $analyticsService->getYearComparison();
        } catch (\Exception $e) {
            // Fallback en cas d'erreur
            $monthlyRevenueChart = ['labels' => [], 'revenue' => [], 'expenses' => [], 'net' => []];
            $occupancyRate = ['rate' => 0, 'occupied' => 0, 'total' => 0, 'available' => 0];
            $paymentStatistics = ['current_month_revenue' => 0, 'evolution_percentage' => 0];
            $cashFlowForecast = [];
            $propertiesByType = [];
            $leaseExpirationStats = ['expiring_30_days' => 0, 'expiring_60_days' => 0, 'expiring_90_days' => 0];
            $globalKPIs = ['occupancy_rate' => 0, 'collection_rate' => 0, 'revenue_growth' => 0];
            $yearComparison = ['current_year' => 0, 'last_year' => 0, 'evolution' => 0];
        }

        return $this->render('dashboard/admin.html.twig', [
            'stats' => $stats,
            'monthly_revenue' => $monthlyRevenue,
            'monthly_expenses' => $monthlyExpenses,
            'net_income' => $monthlyRevenue - $monthlyExpenses,
            'recent_payments' => $recentPayments,
            'recent_maintenance' => $recentMaintenanceRequests,
            'urgent_requests' => $urgentRequests,
            'overdue_payments' => $overduePayments,
            'user_role' => 'admin',

            // 📊 Nouvelles données analytiques
            'monthly_revenue_chart' => $monthlyRevenueChart,
            'occupancy_rate' => $occupancyRate,
            'payment_statistics' => $paymentStatistics,
            'cash_flow_forecast' => $cashFlowForecast,
            'properties_by_type' => $propertiesByType,
            'lease_expiration_stats' => $leaseExpirationStats,
            'global_kpis' => $globalKPIs,
            'year_comparison' => $yearComparison,
        ]);
    }

    /**
     * Dashboard pour les gestionnaires
     */
    private function managerDashboard(
        $user,
        PropertyRepository $propertyRepo,
        TenantRepository $tenantRepo,
        LeaseRepository $leaseRepo,
        PaymentRepository $paymentRepo,
        MaintenanceRequestRepository $maintenanceRepo,
        ExpenseRepository $expenseRepo,
        AccountingEntryRepository $accountingRepo,
        ConversationRepository $conversationRepo
    ): Response {
        $owner = $user->getOwner();

        if (!$owner) {
            // Si pas de propriétaire associé, dashboard vide
            error_log('DashboardController: managerDashboard fallback case - no owner');
            return $this->render('dashboard/manager.html.twig', [
                'stats' => [
                    'properties' => ['total' => 0, 'occupied' => 0, 'available' => 0],
                    'tenants' => ['total' => 0, 'active' => 0],
                    'leases' => ['active' => 0, 'expiring_soon' => 0],
                    'payments' => ['pending' => 0, 'overdue' => 0, 'monthly_income' => 0],
                    'maintenance' => ['pending' => 0, 'urgent' => 0, 'overdue' => 0],
                    'messages' => ['unread' => 0, 'total' => 0]
                ],
                'recent_payments' => [],
                'recent_maintenance' => [],
                'urgent_requests' => [],
                'overdue_payments' => [],
                'user_role' => 'manager'
            ]);
        }

        // Statistiques pour les propriétés du gestionnaire
        $managerProperties = $propertyRepo->findBy(['owner' => $owner]);
        $managerLeases = $leaseRepo->findByManager($owner->getId());

        $stats = [
            'properties' => [
                'total' => count($managerProperties),
                'occupied' => $propertyRepo->count(['owner' => $owner, 'status' => 'Occupé']),
                'available' => $propertyRepo->count(['owner' => $owner, 'status' => 'Libre']),
            ],
            'tenants' => [
                'total' => count($tenantRepo->findByManager($owner->getId())),
                'active' => count($tenantRepo->findWithActiveLeasesByManager($owner->getId())),
            ],
            'leases' => [
                'active' => count($managerLeases),
                'expiring_soon' => count($leaseRepo->findExpiringSoonByManager($owner->getId())),
            ],
            'payments' => [
                'pending' => count($paymentRepo->findByManagerWithFilters($owner->getId(), 'En attente')),
                'overdue' => count($paymentRepo->findOverdueByManager($owner->getId())),
                'monthly_income' => $paymentRepo->getMonthlyIncomeByManager($owner->getId()),
            ],
            'maintenance' => [
                'pending' => count($maintenanceRepo->findByManagerWithFilters($owner->getId(), 'Nouvelle')),
                'urgent' => count($maintenanceRepo->findUrgentPendingByManager($owner->getId())),
                'overdue' => count($maintenanceRepo->findOverdueByManager($owner->getId())),
            ],
            'messages' => [
                'unread' => count($conversationRepo->findWithUnreadMessages($user)),
                'total' => count($conversationRepo->findByUser($user)),
            ]
        ];

        // Activités récentes pour le gestionnaire
        $recentPayments = $paymentRepo->findByManagerWithFilters($owner->getId(), null, null, null, date('Y'), date('n'));
        $recentMaintenanceRequests = $maintenanceRepo->findByManagerWithFilters($owner->getId());
        $urgentRequests = $maintenanceRepo->findUrgentPendingByManager($owner->getId());
        $overduePayments = $paymentRepo->findOverdueByManager($owner->getId());

        error_log('DashboardController: managerDashboard normal case - owner exists, urgent_requests count: ' . count($urgentRequests));

        return $this->render('dashboard/manager.html.twig', [
            'stats' => $stats,
            'recent_payments' => array_slice($recentPayments, 0, 5),
            'recent_maintenance' => array_slice($recentMaintenanceRequests, 0, 5),
            'urgent_requests' => $urgentRequests,
            'overdue_payments' => $overduePayments,
            'user_role' => 'manager'
        ]);
    }

    /**
     * Dashboard pour les locataires
     */
    private function tenantDashboard(
        $user,
        PropertyRepository $propertyRepo,
        LeaseRepository $leaseRepo,
        PaymentRepository $paymentRepo,
        MaintenanceRequestRepository $maintenanceRepo,
        AccountingEntryRepository $accountingRepo,
        ConversationRepository $conversationRepo
    ): Response {
        $tenant = $user->getTenant();

        if (!$tenant) {
            // Si pas de locataire associé, dashboard vide
            return $this->render('dashboard/tenant.html.twig', [
                'stats' => [
                    'properties' => ['total' => 0],
                    'payments' => ['pending' => 0, 'overdue' => 0],
                    'maintenance' => ['pending' => 0, 'urgent' => 0],
                    'accounting' => ['balance' => 0, 'monthly_credits' => 0, 'monthly_debits' => 0],
                    'messages' => ['unread' => 0, 'total' => 0]
                ],
                'user_role' => 'tenant'
            ]);
        }

        // Statistiques pour le locataire
        $tenantProperties = $propertyRepo->findByTenantWithFilters($tenant->getId());
        $tenantLeases = $leaseRepo->findBy(['tenant' => $tenant, 'status' => 'Actif']);

        $stats = [
            'properties' => [
                'total' => count($tenantProperties),
            ],
            'leases' => [
                'active' => count($tenantLeases),
                'expiring_soon' => count($leaseRepo->findExpiringSoonByTenant($tenant->getId())),
            ],
            'payments' => [
                'pending' => count($paymentRepo->findByTenantWithFilters($tenant->getId(), 'En attente')),
                'overdue' => count($paymentRepo->findOverdueByTenant($tenant->getId())),
            ],
            'maintenance' => [
                'pending' => count($maintenanceRepo->findByTenantWithFilters($tenant->getId(), 'Nouvelle')),
                'urgent' => count($maintenanceRepo->findUrgentPendingByTenant($tenant->getId())),
            ],
            'accounting' => [
                'balance' => $accountingRepo->getTenantStatistics($tenant->getId())['balance'] ?? 0,
                'monthly_credits' => $accountingRepo->getTenantStatistics($tenant->getId())['current_month_credits'] ?? 0,
                'monthly_debits' => $accountingRepo->getTenantStatistics($tenant->getId())['current_month_debits'] ?? 0,
            ],
            'messages' => [
                'unread' => count($conversationRepo->findWithUnreadMessages($user)),
                'total' => count($conversationRepo->findByUser($user)),
            ]
        ];

        // Activités récentes pour le locataire
        $recentPayments = $paymentRepo->findByTenantWithFilters($tenant->getId());
        $recentMaintenanceRequests = $maintenanceRepo->findByTenantWithFilters($tenant->getId());
        $overduePayments = $paymentRepo->findOverdueByTenant($tenant->getId());

        return $this->render('dashboard/tenant.html.twig', [
            'stats' => $stats,
            'recent_payments' => array_slice($recentPayments, 0, 5),
            'recent_maintenance' => array_slice($recentMaintenanceRequests, 0, 5),
            'overdue_payments' => $overduePayments,
            'user_role' => 'tenant'
        ]);
    }

    /**
     * Dashboard par défaut
     */
    private function defaultDashboard(
        PropertyRepository $propertyRepo,
        TenantRepository $tenantRepo,
        LeaseRepository $leaseRepo,
        PaymentRepository $paymentRepo,
        MaintenanceRequestRepository $maintenanceRepo,
        ExpenseRepository $expenseRepo
    ): Response {
        // Statistiques générales
        $stats = [
            'properties' => [
                'total' => $propertyRepo->count([]),
                'occupied' => $propertyRepo->count(['status' => 'Occupé']),
                'available' => $propertyRepo->count(['status' => 'Libre']),
            ],
            'tenants' => [
                'total' => $tenantRepo->count([]),
                'active' => count($tenantRepo->findWithActiveLeases()),
            ],
            'leases' => [
                'active' => $leaseRepo->count(['status' => 'Actif']),
                'expiring_soon' => count($leaseRepo->findExpiringSoon()),
            ],
            'payments' => [
                'pending' => $paymentRepo->count(['status' => 'En attente']),
                'overdue' => count($paymentRepo->findOverdue()),
                'monthly_income' => $paymentRepo->getMonthlyIncome(),
            ],
            'maintenance' => [
                'pending' => $maintenanceRepo->count(['status' => 'Nouvelle']),
                'urgent' => count($maintenanceRepo->findUrgentPending()),
                'overdue' => count($maintenanceRepo->findOverdue()),
            ],
        ];

        // Revenus et dépenses du mois pour le dashboard par défaut
        $currentMonth = new \DateTime('first day of this month');
        $nextMonth = new \DateTime('first day of next month');

        $monthlyRevenue = $paymentRepo->getTotalRevenueByPeriod($currentMonth, $nextMonth);
        $monthlyExpenses = $expenseRepo->getTotalExpensesByPeriod($currentMonth, $nextMonth);

        // Dernières activités
        $recentPayments = $paymentRepo->findBy([], ['createdAt' => 'DESC'], 5);
        $recentMaintenanceRequests = $maintenanceRepo->findBy([], ['createdAt' => 'DESC'], 5);
        $urgentRequests = $maintenanceRepo->findUrgentPending();
        $overduePayments = $paymentRepo->findOverdue();

        return $this->render('dashboard/index.html.twig', [
            'stats' => $stats,
            'monthly_revenue' => $monthlyRevenue,
            'net_income' => $monthlyRevenue - $monthlyExpenses,
            'recent_payments' => $recentPayments,
            'recent_maintenance' => $recentMaintenanceRequests,
            'urgent_requests' => $urgentRequests,
            'overdue_payments' => $overduePayments,
            'user_role' => 'default'
        ]);
    }

    #[Route('/analytics', name: 'app_dashboard_analytics')]
    public function analytics(
        DashboardAnalyticsService $analyticsService
    ): Response {

        try {
            $monthlyRevenueChart = $analyticsService->getMonthlyRevenueChartData();
            $occupancyRate = $analyticsService->getOccupancyRate();
            $paymentStatistics = $analyticsService->getPaymentStatistics();
            $cashFlowForecast = $analyticsService->getCashFlowForecast();
            $propertiesByType = $analyticsService->getPropertiesByType();
            $leaseExpirationStats = $analyticsService->getLeaseExpirationStats();
            $globalKPIs = $analyticsService->getGlobalKPIs();
            $yearComparison = $analyticsService->getYearComparison();
        } catch (\Exception $e) {
            // Fallback en cas d'erreur
            $this->addFlash('warning', 'Certaines statistiques ne sont pas disponibles : ' . $e->getMessage());

            $monthlyRevenueChart = ['labels' => [], 'revenue' => [], 'expenses' => [], 'net' => []];
            $occupancyRate = ['rate' => 0, 'occupied' => 0, 'total' => 0, 'available' => 0];
            $paymentStatistics = [
                'current_month_revenue' => 0,
                'evolution_percentage' => 0,
                'overdue_count' => 0,
                'overdue_amount' => 0
            ];
            $cashFlowForecast = [];
            $propertiesByType = [];
            $leaseExpirationStats = [
                'expiring_30_days' => 0,
                'expiring_60_days' => 0,
                'expiring_90_days' => 0,
                'total_active' => 1
            ];
            $globalKPIs = [
                'occupancy_rate' => 0,
                'collection_rate' => 0,
                'revenue_growth' => 0
            ];
            $yearComparison = ['current_year' => 0, 'last_year' => 0, 'evolution' => 0];
        }

        return $this->render('dashboard/admin_analytics.html.twig', [
            'monthly_revenue_chart' => $monthlyRevenueChart,
            'occupancy_rate' => $occupancyRate,
            'payment_statistics' => $paymentStatistics,
            'cash_flow_forecast' => $cashFlowForecast,
            'properties_by_type' => $propertiesByType,
            'lease_expiration_stats' => $leaseExpirationStats,
            'global_kpis' => $globalKPIs,
            'year_comparison' => $yearComparison,
        ]);
    }

    #[Route('/tableau-de-bord', name: 'app_dashboard_full')]
    public function fullDashboard(
        PropertyRepository $propertyRepo,
        PaymentRepository $paymentRepo,
        MaintenanceRequestRepository $maintenanceRepo
    ): Response {
        // Graphiques et statistiques détaillées
        $monthlyData = [];
        for ($i = 11; $i >= 0; $i--) {
            $date = new \DateTime();
            $date->modify("-{$i} months");
            $startDate = clone $date;
            $startDate->modify('first day of this month');
            $endDate = clone $date;
            $endDate->modify('last day of this month');

            $monthlyData[] = [
                'month' => $date->format('M Y'),
                'revenue' => $paymentRepo->getTotalRevenueByPeriod($startDate, $endDate),
                'maintenance_cost' => $maintenanceRepo->getTotalCostByPeriod($startDate, $endDate),
            ];
        }

        $propertyStats = $propertyRepo->getStatistics();
        $maintenanceStats = $maintenanceRepo->getStatistics();

        return $this->render('dashboard/full.html.twig', [
            'monthly_data' => $monthlyData,
            'property_stats' => $propertyStats,
            'maintenance_stats' => $maintenanceStats,
        ]);
    }

    /**
     * Récupère les statistiques filtrées selon le rôle et l'organisation/société de l'utilisateur
     */
    private function getFilteredStats(
        $user,
        PropertyRepository $propertyRepo,
        TenantRepository $tenantRepo,
        LeaseRepository $leaseRepo,
        PaymentRepository $paymentRepo,
        MaintenanceRequestRepository $maintenanceRepo,
        ConversationRepository $conversationRepo
    ): array {
        $organization = $user->getOrganization();
        $company = $user->getCompany();

        // Initialiser les statistiques
        $stats = [
            'properties' => ['total' => 0, 'occupied' => 0, 'available' => 0],
            'tenants' => ['total' => 0, 'active' => 0],
            'leases' => ['active' => 0, 'expiring_soon' => 0],
            'payments' => ['pending' => 0, 'overdue' => 0, 'monthly_income' => 0],
            'maintenance' => ['pending' => 0, 'urgent' => 0, 'overdue' => 0],
            'messages' => ['unread' => 0, 'total' => 0]
        ];

        try {
            // Filtrer les propriétés
            if ($company) {
                // Admin avec société spécifique
                $stats['properties']['total'] = $propertyRepo->count(['company' => $company]);
                $stats['properties']['occupied'] = $propertyRepo->count(['company' => $company, 'status' => 'Occupé']);
                $stats['properties']['available'] = $propertyRepo->count(['company' => $company, 'status' => 'Libre']);

                $stats['tenants']['total'] = $tenantRepo->count(['company' => $company]);
                $stats['leases']['active'] = $leaseRepo->count(['status' => 'Actif']); // TODO: filtrer par company

                error_log("DashboardController - Filtered by company: " . $company->getName());
            } elseif ($organization) {
                // Admin avec organisation
                $stats['properties']['total'] = $propertyRepo->count(['organization' => $organization]);
                $stats['properties']['occupied'] = $propertyRepo->count(['organization' => $organization, 'status' => 'Occupé']);
                $stats['properties']['available'] = $propertyRepo->count(['organization' => $organization, 'status' => 'Libre']);

                $stats['tenants']['total'] = $tenantRepo->count(['organization' => $organization]);
                $stats['leases']['active'] = $leaseRepo->count(['status' => 'Actif']); // TODO: filtrer par organization

                error_log("DashboardController - Filtered by organization: " . $organization->getName());
            } else {
                // Super Admin sans organisation/société : toutes les données
                $stats['properties']['total'] = $propertyRepo->count([]);
                $stats['properties']['occupied'] = $propertyRepo->count(['status' => 'Occupé']);
                $stats['properties']['available'] = $propertyRepo->count(['status' => 'Libre']);

                $stats['tenants']['total'] = $tenantRepo->count([]);
                $stats['leases']['active'] = $leaseRepo->count(['status' => 'Actif']);

                error_log("DashboardController - Super Admin: showing all data");
            }

            // Autres statistiques (pour l'instant non filtrées, à améliorer selon les besoins)
            $stats['tenants']['active'] = count($tenantRepo->findWithActiveLeases());
            $stats['leases']['expiring_soon'] = count($leaseRepo->findExpiringSoon());
            $stats['payments']['pending'] = $paymentRepo->count(['status' => 'En attente']);
            $stats['payments']['overdue'] = count($paymentRepo->findOverdue());
            $stats['payments']['monthly_income'] = $paymentRepo->getMonthlyIncome();
            $stats['maintenance']['pending'] = $maintenanceRepo->count(['status' => 'Nouvelle']);
            $stats['maintenance']['urgent'] = count($maintenanceRepo->findUrgentPending());
            $stats['maintenance']['overdue'] = count($maintenanceRepo->findOverdue());
            $stats['messages']['unread'] = count($conversationRepo->findWithUnreadMessages($user));
            $stats['messages']['total'] = count($conversationRepo->findByUser($user));

        } catch (\Exception $e) {
            error_log("DashboardController - Error getting filtered stats: " . $e->getMessage());
            // En cas d'erreur, retourner des stats vides
        }

        return $stats;
    }
}
