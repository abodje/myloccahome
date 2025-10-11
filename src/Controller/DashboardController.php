<?php

namespace App\Controller;

use App\Repository\PropertyRepository;
use App\Repository\TenantRepository;
use App\Repository\RentalContractRepository;
use App\Repository\PaymentRepository;
use App\Repository\MaintenanceRequestRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

class DashboardController extends AbstractController
{
    #[Route('/', name: 'app_dashboard')]
    public function index(
        PropertyRepository $propertyRepo,
        TenantRepository $tenantRepo,
        RentalContractRepository $contractRepo,
        PaymentRepository $paymentRepo,
        MaintenanceRequestRepository $maintenanceRepo
    ): Response {
        // Récupération des statistiques
        $propertyStats = $propertyRepo->getPropertyStats();
        $tenantStats = $tenantRepo->getTenantStats();
        $contractStats = $contractRepo->getContractStats();
        $paymentStats = $paymentRepo->getPaymentStats();
        $maintenanceStats = $maintenanceRepo->getMaintenanceStats();

        // Récupération des données pour les alertes
        $overduePayments = $paymentRepo->findOverduePayments();
        $paymentsDueSoon = $paymentRepo->findPaymentsDueSoon(7);
        $contractsEndingSoon = $contractRepo->findContractsEndingSoon(30);
        $urgentMaintenance = $maintenanceRepo->findUrgentRequests();

        // Calcul du chiffre d'affaires mensuel
        $currentMonth = new \DateTime();
        $monthlyRevenue = $paymentRepo->getMonthlyRevenue($currentMonth);
        $maintenanceCosts = $maintenanceRepo->getMonthlyCosts($currentMonth);

        return $this->render('dashboard/index.html.twig', [
            'propertyStats' => $propertyStats,
            'tenantStats' => $tenantStats,
            'contractStats' => $contractStats,
            'paymentStats' => $paymentStats,
            'maintenanceStats' => $maintenanceStats,
            'overduePayments' => $overduePayments,
            'paymentsDueSoon' => $paymentsDueSoon,
            'contractsEndingSoon' => $contractsEndingSoon,
            'urgentMaintenance' => $urgentMaintenance,
            'monthlyRevenue' => $monthlyRevenue,
            'maintenanceCosts' => $maintenanceCosts,
            'netProfit' => $monthlyRevenue - $maintenanceCosts,
        ]);
    }
}