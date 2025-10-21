<?php

namespace App\Controller;

use App\Entity\Settings;
use App\Repository\SettingsRepository;
use App\Repository\PropertyRepository;
use App\Repository\TenantRepository;
use App\Repository\LeaseRepository;
use App\Repository\PaymentRepository;
use App\Repository\MaintenanceRequestRepository;
use App\Repository\DocumentRepository;
use App\Service\AccountingService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/admin')]
class AdminController extends AbstractController
{
    #[Route('/', name: 'app_admin_dashboard', methods: ['GET'])]
    public function dashboard(
        PropertyRepository $propertyRepo,
        TenantRepository $tenantRepo,
        LeaseRepository $leaseRepo,
        PaymentRepository $paymentRepo,
        MaintenanceRequestRepository $maintenanceRepo,
        DocumentRepository $documentRepo
    ): Response {
        // Statistiques globales pour l'admin
        $globalStats = [
            'properties' => $propertyRepo->getStatistics(),
            'tenants' => $tenantRepo->getStatistics(),
            'leases' => $leaseRepo->getStatistics(),
            'payments' => $paymentRepo->getStatistics(),
            'maintenance' => $maintenanceRepo->getStatistics(),
            'documents' => $documentRepo->getStatistics(),
        ];

        // Activité récente
        $recentActivity = [
            'new_tenants' => $tenantRepo->findBy([], ['createdAt' => 'DESC'], 5),
            'new_properties' => $propertyRepo->findBy([], ['createdAt' => 'DESC'], 5),
            'recent_maintenance' => $maintenanceRepo->findBy([], ['createdAt' => 'DESC'], 5),
            'overdue_payments' => $paymentRepo->findOverdue(),
        ];

        return $this->render('admin/dashboard.html.twig', [
            'stats' => $globalStats,
            'activity' => $recentActivity,
        ]);
    }

    #[Route('/parametres', name: 'app_admin_settings', methods: ['GET', 'POST'])]
    public function settings(Request $request, SettingsRepository $settingsRepo, EntityManagerInterface $em): Response
    {
        if ($request->isMethod('POST')) {
            $settingsData = $request->request->all();

            foreach ($settingsData as $key => $value) {
                if (strpos($key, 'setting_') === 0) {
                    $settingKey = substr($key, 8); // Enlever le préfixe 'setting_'
                    $settingsRepo->setValue($settingKey, $value);
                }
            }

            $this->addFlash('success', 'Les paramètres ont été mis à jour avec succès.');
            return $this->redirectToRoute('app_admin_settings');
        }

        $settingsByCategory = $settingsRepo->findAllGroupedByCategory();

        // Si aucun paramètre n'existe, créer les paramètres par défaut
        if (empty($settingsByCategory)) {
            $this->createDefaultSettings($em);
            $settingsByCategory = $settingsRepo->findAllGroupedByCategory();
        }

        return $this->render('admin/settings.html.twig', [
            'settings_by_category' => $settingsByCategory,
        ]);
    }

    // Route supprimée - remplacée par Admin/UserController

    #[Route('/rapports', name: 'app_admin_reports', methods: ['GET'])]
    public function reports(
        PropertyRepository $propertyRepo,
        PaymentRepository $paymentRepo,
        MaintenanceRequestRepository $maintenanceRepo
    ): Response {
        // Données pour les rapports
        $currentMonth = new \DateTime('first day of this month');
        $lastMonth = new \DateTime('first day of last month');
        $currentYear = new \DateTime('first day of January this year');

        $reports = [
            'monthly_revenue' => $paymentRepo->getTotalRevenueByPeriod($currentMonth, new \DateTime('last day of this month')),
            'last_month_revenue' => $paymentRepo->getTotalRevenueByPeriod($lastMonth, new \DateTime('last day of last month')),
            'yearly_revenue' => $paymentRepo->getTotalRevenueByPeriod($currentYear, new \DateTime('last day of December this year')),
            'maintenance_costs' => $maintenanceRepo->getTotalCostByPeriod($currentMonth, new \DateTime('last day of this month')),
            'occupancy_rate' => $propertyRepo->getStatistics()['occupancy_rate'] ?? 0,
        ];

        return $this->render('admin/reports.html.twig', [
            'reports' => $reports,
        ]);
    }

    #[Route('/maintenance-systeme', name: 'app_admin_maintenance', methods: ['GET', 'POST'])]
    public function systemMaintenance(Request $request, AccountingService $accountingService): Response
    {
        if ($request->isMethod('POST')) {
            $action = $request->request->get('action');

            switch ($action) {
                case 'sync_accounting':
                    $accountingService->synchronizeAllEntries();
                    $this->addFlash('success', 'Synchronisation comptable effectuée avec succès.');
                    break;

                case 'recalculate_balances':
                    $accountingService->recalculateRunningBalances();
                    $this->addFlash('success', 'Soldes recalculés avec succès.');
                    break;

                case 'clear_cache':
                    // En production, vous pourriez exécuter des commandes système
                    $this->addFlash('success', 'Cache vidé avec succès.');
                    break;

                default:
                    $this->addFlash('error', 'Action non reconnue.');
            }

            return $this->redirectToRoute('app_admin_maintenance');
        }

        return $this->render('admin/maintenance.html.twig');
    }

    #[Route('/logs', name: 'app_admin_logs', methods: ['GET'])]
    public function logs(): Response
    {
        // Simulation de logs pour la démo
        $logs = [
            [
                'timestamp' => new \DateTime('-1 hour'),
                'level' => 'INFO',
                'message' => 'Nouveau locataire créé : Kouame ABODJE',
                'context' => 'TenantController'
            ],
            [
                'timestamp' => new \DateTime('-2 hours'),
                'level' => 'INFO',
                'message' => 'Paiement marqué comme payé : 654.69€',
                'context' => 'PaymentController'
            ],
            [
                'timestamp' => new \DateTime('-3 hours'),
                'level' => 'WARNING',
                'message' => 'Tentative de suppression d\'une propriété avec contrat actif',
                'context' => 'PropertyController'
            ],
            [
                'timestamp' => new \DateTime('-1 day'),
                'level' => 'INFO',
                'message' => 'Synchronisation comptable automatique effectuée',
                'context' => 'AccountingService'
            ]
        ];

        return $this->render('admin/logs.html.twig', [
            'logs' => $logs,
        ]);
    }

    private function createDefaultSettings(EntityManagerInterface $em): void
    {
        $defaultSettings = [
            // Paramètres généraux
            ['key' => 'app_name', 'value' => 'LOKAPRO', 'category' => 'GENERAL', 'description' => 'Nom de l\'application', 'type' => 'STRING'],
            ['key' => 'company_name', 'value' => 'LOKAPRO Gestion', 'category' => 'GENERAL', 'description' => 'Nom de la société', 'type' => 'STRING'],
            ['key' => 'company_address', 'value' => '123 Avenue de la République, 69000 Lyon', 'category' => 'GENERAL', 'description' => 'Adresse de la société', 'type' => 'STRING'],
            ['key' => 'company_phone', 'value' => '04 72 00 00 00', 'category' => 'GENERAL', 'description' => 'Téléphone de la société', 'type' => 'STRING'],
            ['key' => 'company_email', 'value' => 'contact@app.lokapro.tech', 'category' => 'GENERAL', 'description' => 'Email de contact', 'type' => 'STRING'],

            // Paramètres de paiement
            ['key' => 'default_rent_due_day', 'value' => '1', 'category' => 'PAYMENT', 'description' => 'Jour d\'échéance par défaut', 'type' => 'INTEGER'],
            ['key' => 'late_fee_rate', 'value' => '5.00', 'category' => 'PAYMENT', 'description' => 'Taux de pénalité de retard (%)', 'type' => 'FLOAT'],
            ['key' => 'auto_generate_rent', 'value' => '1', 'category' => 'PAYMENT', 'description' => 'Génération automatique des loyers', 'type' => 'BOOLEAN'],

            // Paramètres email
            ['key' => 'smtp_host', 'value' => 'localhost', 'category' => 'EMAIL', 'description' => 'Serveur SMTP', 'type' => 'STRING'],
            ['key' => 'smtp_port', 'value' => '587', 'category' => 'EMAIL', 'description' => 'Port SMTP', 'type' => 'INTEGER'],
            ['key' => 'email_from', 'value' => 'noreply@app.lokapro.tech', 'category' => 'EMAIL', 'description' => 'Email expéditeur', 'type' => 'STRING'],

            // Paramètres maintenance
            ['key' => 'auto_assign_maintenance', 'value' => '0', 'category' => 'MAINTENANCE', 'description' => 'Attribution automatique des demandes', 'type' => 'BOOLEAN'],
            ['key' => 'urgent_notification', 'value' => '1', 'category' => 'MAINTENANCE', 'description' => 'Notifications pour demandes urgentes', 'type' => 'BOOLEAN'],
        ];

        foreach ($defaultSettings as $settingData) {
            $setting = new Settings();
            $setting->setSettingKey($settingData['key'])
                   ->setSettingValue($settingData['value'])
                   ->setCategory($settingData['category'])
                   ->setDescription($settingData['description'])
                   ->setDataType($settingData['type']);

            $em->persist($setting);
        }

        $em->flush();
    }
}
