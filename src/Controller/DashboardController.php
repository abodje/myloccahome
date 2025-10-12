<?php

namespace App\Controller;

use App\Repository\PropertyRepository;
use App\Repository\TenantRepository;
use App\Repository\LeaseRepository;
use App\Repository\PaymentRepository;
use App\Repository\MaintenanceRequestRepository;
use App\Repository\ExpenseRepository;
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

        return $this->render('dashboard/index.html.twig', [
            'stats' => $stats,
            'monthly_revenue' => $monthlyRevenue,
            'monthly_expenses' => $monthlyExpenses,
            'net_income' => $monthlyRevenue - $monthlyExpenses,
            'recent_payments' => $recentPayments,
            'recent_maintenance' => $recentMaintenanceRequests,
            'urgent_requests' => $urgentRequests,
            'overdue_payments' => $overduePayments,
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
}
