<?php

namespace App\Service;

use App\Repository\LeaseRepository;
use App\Repository\MaintenanceRequestRepository;
use App\Repository\PaymentRepository;
use App\Repository\PropertyRepository;

class ReportService
{
    public function __construct(
        private readonly PropertyRepository $propertyRepository,
        private readonly PaymentRepository $paymentRepository,
        private readonly MaintenanceRequestRepository $maintenanceRepository,
        private readonly LeaseRepository $leaseRepository
    ) {
    }

    public function generateMainReports(?int $organizationId = null, ?int $companyId = null): array
    {
        $currentMonthStart = new \DateTime('first day of this month');
        $currentMonthEnd = new \DateTime('last day of this month');
        $lastMonthStart = new \DateTime('first day of last month');
        $lastMonthEnd = new \DateTime('last day of last month');
        $currentYearStart = new \DateTime('first day of January this year');

        // KPIs principaux
        $monthlyRevenue = (float) $this->paymentRepository->getTotalRevenueByPeriod($currentMonthStart, $currentMonthEnd, $organizationId, $companyId);
        $lastMonthRevenue = (float) $this->paymentRepository->getTotalRevenueByPeriod($lastMonthStart, $lastMonthEnd, $organizationId, $companyId);
        $monthlyExpenses = (float) $this->maintenanceRepository->getTotalCostByPeriod($currentMonthStart, $currentMonthEnd, $organizationId, $companyId);
        $yearlyRevenue = (float) $this->paymentRepository->getTotalRevenueByPeriod($currentYearStart, new \DateTime(), $organizationId, $companyId);

        // Calculs de croissance et de rentabilité
        $revenueGrowth = ($lastMonthRevenue > 0) ? (($monthlyRevenue - $lastMonthRevenue) / $lastMonthRevenue) * 100 : 0;
        $netIncome = $monthlyRevenue - $monthlyExpenses;

        // Statistiques sur les biens et l'occupation
        $propertyStats = $this->propertyRepository->getStatistics($organizationId, $companyId);
        $occupancyRate = $propertyStats['occupancy_rate'] ?? 0;
        $vacancyRate = 100 - $occupancyRate;
        $totalProperties = $propertyStats['total'] ?? 0;
        $occupiedProperties = $propertyStats['occupied'] ?? 0;
        $vacantProperties = $propertyStats['available'] ?? 0;

        // Paiements en retard
        $overduePayments = $this->paymentRepository->findOverdue($organizationId, $companyId);
        $totalOverdueAmount = array_reduce($overduePayments, fn($sum, $p) => $sum + $p->getAmount(), 0);

        // Baux arrivant à expiration (dans les 90 prochains jours)
        $expiringLeases = $this->leaseRepository->findExpiringSoon(90, $organizationId, $companyId);

        return [
            'monthly_revenue' => $monthlyRevenue,
            'last_month_revenue' => $lastMonthRevenue,
            'revenue_growth' => $revenueGrowth,
            'monthly_expenses' => $monthlyExpenses,
            'net_income' => $netIncome,
            'yearly_revenue' => $yearlyRevenue,
            'occupancy_rate' => $occupancyRate,
            'vacancy_rate' => $vacancyRate,
            'occupied_properties' => $occupiedProperties,
            'vacant_properties' => $vacantProperties,
            'total_properties' => $totalProperties,
            'overdue_payments_count' => count($overduePayments),
            'total_overdue_amount' => $totalOverdueAmount,
            'overdue_payments' => $overduePayments,
            'expiring_leases_count' => count($expiringLeases),
            'expiring_leases' => $expiringLeases,
        ];
    }

    public function getChartData(?int $organizationId = null, ?int $companyId = null): array
    {
        $labels = [];
        $revenueData = [];
        $expensesData = [];
        $netIncomeData = [];

        for ($i = 11; $i >= 0; $i--) {
            $date = new \DateTime("first day of -$i month");
            $monthStart = (clone $date)->modify('first day of this month');
            $monthEnd = (clone $date)->modify('last day of this month');

            $labels[] = $date->format('M Y');
            $revenue = (float) $this->paymentRepository->getTotalRevenueByPeriod($monthStart, $monthEnd, $organizationId, $companyId);
            $expenses = (float) $this->maintenanceRepository->getTotalCostByPeriod($monthStart, $monthEnd, $organizationId, $companyId);

            $revenueData[] = $revenue;
            $expensesData[] = $expenses;
            $netIncomeData[] = $revenue - $expenses;
        }

        return [
            'labels' => $labels,
            'revenue' => $revenueData,
            'expenses' => $expensesData,
            'net_income' => $netIncomeData,
        ];
    }
}
