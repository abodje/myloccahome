<?php

namespace App\Service;

use App\Repository\PaymentRepository;
use App\Repository\PropertyRepository;
use App\Repository\LeaseRepository;
use App\Repository\ExpenseRepository;
use App\Repository\MaintenanceRequestRepository;
use Doctrine\ORM\EntityManagerInterface;

/**
 * Service pour générer les statistiques et analyses du dashboard
 */
class DashboardAnalyticsService
{
    public function __construct(
        private EntityManagerInterface $entityManager,
        private PaymentRepository $paymentRepo,
        private PropertyRepository $propertyRepo,
        private LeaseRepository $leaseRepo,
        private ExpenseRepository $expenseRepo,
        private MaintenanceRequestRepository $maintenanceRepo
    ) {
    }

    /**
     * Génère les données pour le graphique des revenus mensuels (12 derniers mois)
     */
    public function getMonthlyRevenueChartData(): array
    {
        $data = [
            'labels' => [],
            'revenue' => [],
            'expenses' => [],
            'net' => []
        ];

        for ($i = 11; $i >= 0; $i--) {
            $date = new \DateTime();
            $date->modify("-{$i} months");
            $startDate = clone $date;
            $startDate->modify('first day of this month');
            $endDate = clone $date;
            $endDate->modify('last day of this month');

            $revenue = $this->paymentRepo->getTotalRevenueByPeriod($startDate, $endDate);
            $expenses = $this->expenseRepo->getTotalExpensesByPeriod($startDate, $endDate);

            $data['labels'][] = $date->format('M Y');
            $data['revenue'][] = (float) $revenue;
            $data['expenses'][] = (float) $expenses;
            $data['net'][] = (float) ($revenue - $expenses);
        }

        return $data;
    }

    /**
     * Calcule le taux d'occupation des biens
     */
    public function getOccupancyRate(): array
    {
        $totalProperties = $this->propertyRepo->count([]);
        $occupiedProperties = $this->propertyRepo->count(['status' => 'Occupé']);

        $rate = $totalProperties > 0 ? ($occupiedProperties / $totalProperties) * 100 : 0;

        return [
            'rate' => round($rate, 1),
            'occupied' => $occupiedProperties,
            'total' => $totalProperties,
            'available' => $totalProperties - $occupiedProperties
        ];
    }

    /**
     * Génère les statistiques de paiements
     */
    public function getPaymentStatistics(): array
    {
        $currentMonth = new \DateTime('first day of this month');
        $nextMonth = new \DateTime('first day of next month');
        $lastMonth = new \DateTime('first day of last month');

        $currentMonthRevenue = $this->paymentRepo->getTotalRevenueByPeriod($currentMonth, $nextMonth);
        $lastMonthRevenue = $this->paymentRepo->getTotalRevenueByPeriod($lastMonth, $currentMonth);

        $evolution = $lastMonthRevenue > 0
            ? (($currentMonthRevenue - $lastMonthRevenue) / $lastMonthRevenue) * 100
            : 0;

        return [
            'current_month_revenue' => $currentMonthRevenue,
            'last_month_revenue' => $lastMonthRevenue,
            'evolution_percentage' => round($evolution, 1),
            'pending_count' => $this->paymentRepo->count(['status' => 'En attente']),
            'overdue_count' => count($this->paymentRepo->findOverdue()),
            'overdue_amount' => $this->paymentRepo->getTotalOverdueAmount(),
            'paid_this_month' => $this->paymentRepo->countPaidThisMonth(),
        ];
    }

    /**
     * Prévisions de trésorerie (3 prochains mois)
     */
    public function getCashFlowForecast(): array
    {
        $forecast = [];

        for ($i = 0; $i < 3; $i++) {
            $date = new \DateTime();
            $date->modify("+{$i} months");
            $startDate = clone $date;
            $startDate->modify('first day of this month');
            $endDate = clone $date;
            $endDate->modify('last day of this month');

            // Revenus prévus (loyers en attente)
            $expectedRevenue = $this->paymentRepo->getExpectedRevenueByPeriod($startDate, $endDate);

            // Dépenses moyennes (basé sur les 3 derniers mois)
            $avgExpenses = $this->expenseRepo->getAverageMonthlyExpenses(3);

            $forecast[] = [
                'month' => $date->format('M Y'),
                'expected_revenue' => $expectedRevenue,
                'estimated_expenses' => $avgExpenses,
                'projected_net' => $expectedRevenue - $avgExpenses
            ];
        }

        return $forecast;
    }

    /**
     * Statistiques des biens par type
     */
    public function getPropertiesByType(): array
    {
        $connection = $this->entityManager->getConnection();

        $sql = "SELECT type, COUNT(*) as count,
                       SUM(CASE WHEN status = 'Occupé' THEN 1 ELSE 0 END) as occupied
                FROM property
                WHERE type IS NOT NULL
                GROUP BY type";

        $result = $connection->executeQuery($sql)->fetchAllAssociative();

        return $result;
    }

    /**
     * Tendances de maintenance
     */
    public function getMaintenanceTrends(): array
    {
        $data = [
            'labels' => [],
            'requests' => [],
            'completed' => []
        ];

        for ($i = 5; $i >= 0; $i--) {
            $date = new \DateTime();
            $date->modify("-{$i} months");
            $startDate = clone $date;
            $startDate->modify('first day of this month');
            $endDate = clone $date;
            $endDate->modify('last day of this month');

            $totalRequests = $this->maintenanceRepo->countByPeriod($startDate, $endDate);
            $completedRequests = $this->maintenanceRepo->countCompletedByPeriod($startDate, $endDate);

            $data['labels'][] = $date->format('M Y');
            $data['requests'][] = $totalRequests;
            $data['completed'][] = $completedRequests;
        }

        return $data;
    }

    /**
     * Top 5 des dépenses les plus importantes du mois
     */
    public function getTopExpenses(int $limit = 5): array
    {
        $currentMonth = new \DateTime('first day of this month');
        $nextMonth = new \DateTime('first day of next month');

        return $this->expenseRepo->getTopExpensesByPeriod($currentMonth, $nextMonth, $limit);
    }

    /**
     * Statistiques de contrats expirants
     */
    public function getLeaseExpirationStats(): array
    {
        $expiring30 = count($this->leaseRepo->findExpiringSoon(30));
        $expiring60 = count($this->leaseRepo->findExpiringSoon(60));
        $expiring90 = count($this->leaseRepo->findExpiringSoon(90));

        return [
            'expiring_30_days' => $expiring30,
            'expiring_60_days' => $expiring60,
            'expiring_90_days' => $expiring90,
            'total_active' => $this->leaseRepo->count(['status' => 'Actif'])
        ];
    }

    /**
     * Performance globale (KPIs)
     */
    public function getGlobalKPIs(): array
    {
        $occupancyRate = $this->getOccupancyRate();
        $paymentStats = $this->getPaymentStatistics();

        // Taux de recouvrement
        $totalExpected = $this->paymentRepo->getTotalExpectedThisMonth();
        $totalCollected = $this->paymentRepo->getTotalCollectedThisMonth();
        $collectionRate = $totalExpected > 0 ? ($totalCollected / $totalExpected) * 100 : 0;

        // Délai moyen de paiement
        $avgPaymentDelay = $this->paymentRepo->getAveragePaymentDelay();

        // Taux de satisfaction maintenance (% traités à temps)
        $maintenanceOnTime = $this->maintenanceRepo->countOnTimeThisMonth();
        $maintenanceTotal = $this->maintenanceRepo->countCompletedThisMonth();
        $maintenanceSatisfaction = $maintenanceTotal > 0 ? ($maintenanceOnTime / $maintenanceTotal) * 100 : 0;

        return [
            'occupancy_rate' => $occupancyRate['rate'],
            'collection_rate' => round($collectionRate, 1),
            'avg_payment_delay' => round($avgPaymentDelay, 1),
            'maintenance_satisfaction' => round($maintenanceSatisfaction, 1),
            'revenue_growth' => $paymentStats['evolution_percentage'],
        ];
    }

    /**
     * Comparaison année en cours vs année précédente
     */
    public function getYearComparison(): array
    {
        $currentYearStart = new \DateTime('first day of January this year');
        $currentYearEnd = new \DateTime('last day of December this year');
        $lastYearStart = new \DateTime('first day of January last year');
        $lastYearEnd = new \DateTime('last day of December last year');

        $currentYearRevenue = $this->paymentRepo->getTotalRevenueByPeriod($currentYearStart, $currentYearEnd);
        $lastYearRevenue = $this->paymentRepo->getTotalRevenueByPeriod($lastYearStart, $lastYearEnd);

        $evolution = $lastYearRevenue > 0
            ? (($currentYearRevenue - $lastYearRevenue) / $lastYearRevenue) * 100
            : 0;

        return [
            'current_year' => $currentYearRevenue,
            'last_year' => $lastYearRevenue,
            'evolution' => round($evolution, 1)
        ];
    }
}

