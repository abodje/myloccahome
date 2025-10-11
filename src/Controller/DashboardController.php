<?php

namespace App\Controller;

use App\Repository\PropertyRepository;
use App\Repository\TenantRepository;
use App\Repository\RentalContractRepository;
use App\Repository\PaymentRepository;
use App\Repository\MaintenanceRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

class DashboardController extends AbstractController
{
    #[Route('/', name: 'app_dashboard')]
    public function index(
        PropertyRepository $propertyRepository,
        TenantRepository $tenantRepository,
        RentalContractRepository $contractRepository,
        PaymentRepository $paymentRepository,
        MaintenanceRepository $maintenanceRepository
    ): Response {
        // Récupération des statistiques générales
        $propertyStats = $propertyRepository->getStatistics();
        $tenantStats = $tenantRepository->getStatistics();
        $contractStats = $contractRepository->getStatistics();
        $paymentStats = $paymentRepository->getStatistics();
        $maintenanceStats = $maintenanceRepository->getStatistics();

        // Contrats expirant bientôt (dans 30 jours)
        $expiringContracts = $contractRepository->findExpiringContracts(30);

        // Paiements en retard
        $overduePayments = $paymentRepository->findOverduePayments();

        // Maintenances urgentes
        $urgentMaintenances = $maintenanceRepository->findUrgent();

        // Revenus mensuels (6 derniers mois)
        $monthlyRevenues = [];
        for ($i = 5; $i >= 0; $i--) {
            $date = new \DateTime("-{$i} months");
            $year = (int)$date->format('Y');
            $month = (int)$date->format('m');
            $monthlyRevenues[] = [
                'period' => $date->format('Y-m'),
                'label' => $date->format('M Y'),
                'revenue' => $paymentRepository->getMonthlyRevenue($year, $month)
            ];
        }

        // Activités récentes (dernières maintenances et paiements)
        $recentMaintenances = $maintenanceRepository->findBy([], ['createdAt' => 'DESC'], 5);
        $recentPayments = $paymentRepository->findBy(['status' => 'paid'], ['paymentDate' => 'DESC'], 5);

        return $this->render('dashboard/index.html.twig', [
            'propertyStats' => $propertyStats,
            'tenantStats' => $tenantStats,
            'contractStats' => $contractStats,
            'paymentStats' => $paymentStats,
            'maintenanceStats' => $maintenanceStats,
            'expiringContracts' => $expiringContracts,
            'overduePayments' => $overduePayments,
            'urgentMaintenances' => $urgentMaintenances,
            'monthlyRevenues' => $monthlyRevenues,
            'recentMaintenances' => $recentMaintenances,
            'recentPayments' => $recentPayments,
        ]);
    }
}