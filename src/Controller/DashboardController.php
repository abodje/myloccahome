<?php

namespace App\Controller;

use App\Repository\PropertyRepository;
use App\Repository\TenantRepository;
use App\Repository\LeaseRepository;
use App\Repository\PaymentRepository;
use App\Repository\ExpenseRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class DashboardController extends AbstractController
{
    #[Route('/', name: 'app_dashboard')]
    public function index(
        PropertyRepository $propertyRepository,
        TenantRepository $tenantRepository,
        LeaseRepository $leaseRepository,
        PaymentRepository $paymentRepository,
        ExpenseRepository $expenseRepository
    ): Response {
        // Récupération des statistiques
        $propertyStats = $propertyRepository->getStatistics();
        $tenantStats = $tenantRepository->getStatistics();
        $leaseStats = $leaseRepository->getStatistics();
        $paymentStats = $paymentRepository->getStatistics();
        $expenseStats = $expenseRepository->getStatistics();

        // Récupération des données pour les widgets
        $overduePayments = $paymentRepository->findOverdue();
        $endingLeases = $leaseRepository->findEndingSoon(30);
        $recentExpenses = $expenseRepository->findByDateRange(
            new \DateTime('-30 days'),
            new \DateTime()
        );

        return $this->render('dashboard/index.html.twig', [
            'propertyStats' => $propertyStats,
            'tenantStats' => $tenantStats,
            'leaseStats' => $leaseStats,
            'paymentStats' => $paymentStats,
            'expenseStats' => $expenseStats,
            'overduePayments' => $overduePayments,
            'endingLeases' => $endingLeases,
            'recentExpenses' => array_slice($recentExpenses, 0, 10),
        ]);
    }
}