<?php

namespace App\Controller\Admin;

use App\Service\ExportService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\ResponseHeaderBag;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/admin/exports')]
#[IsGranted('ROLE_ADMIN')]
class ExportController extends AbstractController
{
    public function __construct(
        private ExportService $exportService
    ) {
    }

    #[Route('/', name: 'app_admin_export_index', methods: ['GET'])]
    public function index(): Response
    {
        return $this->render('admin/export/index.html.twig', [
            'exports' => $this->getAvailableExports(),
        ]);
    }

    #[Route('/rapports-financiers', name: 'app_admin_export_financial_reports', methods: ['GET'])]
    public function financialReports(Request $request): Response
    {
        $year = $request->query->get('year', date('Y'));
        $month = $request->query->get('month', date('n'));
        $format = $request->query->get('format', 'excel');

        try {
            $file = $this->exportService->generateFinancialReport($year, $month, $format);

            return $this->file($file, "rapport-financier-{$year}-{$month}.{$format}", ResponseHeaderBag::DISPOSITION_ATTACHMENT);
        } catch (\Exception $e) {
            $this->addFlash('error', 'Erreur lors de la génération du rapport : ' . $e->getMessage());
            return $this->redirectToRoute('app_admin_export_index');
        }
    }

    #[Route('/paiements', name: 'app_admin_export_payments', methods: ['GET'])]
    public function payments(Request $request): Response
    {
        $startDate = $request->query->get('start_date');
        $endDate = $request->query->get('end_date');
        $status = $request->query->get('status', 'all');
        $format = $request->query->get('format', 'excel');

        try {
            $file = $this->exportService->generatePaymentsExport($startDate, $endDate, $status, $format);

            $filename = "paiements-" . ($startDate ?: 'tous') . "-" . ($endDate ?: date('Y-m-d')) . ".{$format}";
            return $this->file($file, $filename, ResponseHeaderBag::DISPOSITION_ATTACHMENT);
        } catch (\Exception $e) {
            $this->addFlash('error', 'Erreur lors de l\'export des paiements : ' . $e->getMessage());
            return $this->redirectToRoute('app_admin_export_index');
        }
    }

    #[Route('/impayes', name: 'app_admin_export_overdue', methods: ['GET'])]
    public function overdue(Request $request): Response
    {
        $format = $request->query->get('format', 'excel');

        try {
            $file = $this->exportService->generateOverduePaymentsExport($format);

            return $this->file($file, "paiements-impayes-" . date('Y-m-d') . ".{$format}", ResponseHeaderBag::DISPOSITION_ATTACHMENT);
        } catch (\Exception $e) {
            $this->addFlash('error', 'Erreur lors de l\'export des impayés : ' . $e->getMessage());
            return $this->redirectToRoute('app_admin_export_index');
        }
    }

    #[Route('/locataires', name: 'app_admin_export_tenants', methods: ['GET'])]
    public function tenants(Request $request): Response
    {
        $includeHistory = $request->query->get('include_history', false);
        $format = $request->query->get('format', 'excel');

        try {
            $file = $this->exportService->generateTenantsExport($includeHistory, $format);

            $filename = "locataires" . ($includeHistory ? "-avec-historique" : "") . "-" . date('Y-m-d') . ".{$format}";
            return $this->file($file, $filename, ResponseHeaderBag::DISPOSITION_ATTACHMENT);
        } catch (\Exception $e) {
            $this->addFlash('error', 'Erreur lors de l\'export des locataires : ' . $e->getMessage());
            return $this->redirectToRoute('app_admin_export_index');
        }
    }

    #[Route('/biens', name: 'app_admin_export_properties', methods: ['GET'])]
    public function properties(Request $request): Response
    {
        $includeInventory = $request->query->get('include_inventory', false);
        $format = $request->query->get('format', 'excel');

        try {
            $file = $this->exportService->generatePropertiesExport($includeInventory, $format);

            $filename = "inventaire-biens" . ($includeInventory ? "-complet" : "") . "-" . date('Y-m-d') . ".{$format}";
            return $this->file($file, $filename, ResponseHeaderBag::DISPOSITION_ATTACHMENT);
        } catch (\Exception $e) {
            $this->addFlash('error', 'Erreur lors de l\'export des biens : ' . $e->getMessage());
            return $this->redirectToRoute('app_admin_export_index');
        }
    }

    #[Route('/baux', name: 'app_admin_export_leases', methods: ['GET'])]
    public function leases(Request $request): Response
    {
        $status = $request->query->get('status', 'all');
        $format = $request->query->get('format', 'excel');

        try {
            $file = $this->exportService->generateLeasesExport($status, $format);

            $filename = "baux-" . ($status === 'all' ? 'tous' : $status) . "-" . date('Y-m-d') . ".{$format}";
            return $this->file($file, $filename, ResponseHeaderBag::DISPOSITION_ATTACHMENT);
        } catch (\Exception $e) {
            $this->addFlash('error', 'Erreur lors de l\'export des baux : ' . $e->getMessage());
            return $this->redirectToRoute('app_admin_export_index');
        }
    }

    #[Route('/declaration-fiscale', name: 'app_admin_export_tax_declaration', methods: ['GET'])]
    public function taxDeclaration(Request $request): Response
    {
        $year = $request->query->get('year', date('Y'));
        $format = $request->query->get('format', 'pdf');

        try {
            $file = $this->exportService->generateTaxDeclaration($year, $format);

            return $this->file($file, "declaration-fiscale-{$year}.{$format}", ResponseHeaderBag::DISPOSITION_ATTACHMENT);
        } catch (\Exception $e) {
            $this->addFlash('error', 'Erreur lors de la génération de la déclaration fiscale : ' . $e->getMessage());
            return $this->redirectToRoute('app_admin_export_index');
        }
    }

    #[Route('/rapport-comptable', name: 'app_admin_export_accounting_report', methods: ['GET'])]
    public function accountingReport(Request $request): Response
    {
        $startDate = $request->query->get('start_date', date('Y-01-01'));
        $endDate = $request->query->get('end_date', date('Y-12-31'));
        $format = $request->query->get('format', 'excel');

        // Récupérer l'organisation et société de l'utilisateur connecté
        $user = $this->getUser();
        $organizationId = $user && method_exists($user, 'getOrganization') && $user->getOrganization() ? $user->getOrganization()->getId() : null;
        $companyId = $user && method_exists($user, 'getCompany') && $user->getCompany() ? $user->getCompany()->getId() : null;

        try {
            $file = $this->exportService->generateAccountingReport($startDate, $endDate, $format, $organizationId, $companyId);

            $filename = "rapport-comptable-{$startDate}-{$endDate}.{$format}";
            return $this->file($file, $filename, ResponseHeaderBag::DISPOSITION_ATTACHMENT);
        } catch (\Exception $e) {
            $this->addFlash('error', 'Erreur lors de la génération du rapport comptable : ' . $e->getMessage());
            return $this->redirectToRoute('app_admin_export_index');
        }
    }

    #[Route('/export-complet', name: 'app_admin_export_complete', methods: ['GET'])]
    public function completeExport(Request $request): Response
    {
        $year = $request->query->get('year', date('Y'));
        $format = $request->query->get('format', 'zip');

        try {
            $file = $this->exportService->generateCompleteExport($year, $format);

            return $this->file($file, "export-complet-mylocca-{$year}.{$format}", ResponseHeaderBag::DISPOSITION_ATTACHMENT);
        } catch (\Exception $e) {
            $this->addFlash('error', 'Erreur lors de l\'export complet : ' . $e->getMessage());
            return $this->redirectToRoute('app_admin_export_index');
        }
    }

    private function getAvailableExports(): array
    {
        return [
            'financial' => [
                'title' => 'Rapports Financiers',
                'description' => 'Paiements, revenus, dépenses par période',
                'icon' => 'bi-graph-up',
                'route' => 'app_admin_export_financial_reports',
                'formats' => ['excel', 'pdf'],
            ],
            'payments' => [
                'title' => 'Export Paiements',
                'description' => 'Liste détaillée des paiements avec filtres',
                'icon' => 'bi-credit-card',
                'route' => 'app_admin_export_payments',
                'formats' => ['excel', 'pdf'],
            ],
            'overdue' => [
                'title' => 'Paiements Impayés',
                'description' => 'Liste des loyers en retard',
                'icon' => 'bi-exclamation-triangle',
                'route' => 'app_admin_export_overdue',
                'formats' => ['excel', 'pdf'],
            ],
            'tenants' => [
                'title' => 'Liste Locataires',
                'description' => 'Données locataires avec historique optionnel',
                'icon' => 'bi-people',
                'route' => 'app_admin_export_tenants',
                'formats' => ['excel', 'pdf'],
            ],
            'properties' => [
                'title' => 'Inventaire Biens',
                'description' => 'Liste complète des propriétés avec inventaire',
                'icon' => 'bi-building',
                'route' => 'app_admin_export_properties',
                'formats' => ['excel', 'pdf'],
            ],
            'leases' => [
                'title' => 'Export Baux',
                'description' => 'Contrats de location par statut',
                'icon' => 'bi-file-text',
                'route' => 'app_admin_export_leases',
                'formats' => ['excel', 'pdf'],
            ],
            'tax_declaration' => [
                'title' => 'Déclaration Fiscale',
                'description' => 'Rapport annuel pour déclaration fiscale',
                'icon' => 'bi-file-earmark-check',
                'route' => 'app_admin_export_tax_declaration',
                'formats' => ['pdf', 'excel'],
            ],
            'accounting' => [
                'title' => 'Rapport Comptable',
                'description' => 'Rapport comptable complet par période',
                'icon' => 'bi-calculator',
                'route' => 'app_admin_export_accounting_report',
                'formats' => ['excel', 'pdf'],
            ],
            'complete' => [
                'title' => 'Export Complet',
                'description' => 'Toutes les données dans un fichier ZIP',
                'icon' => 'bi-archive',
                'route' => 'app_admin_export_complete',
                'formats' => ['zip'],
            ],
        ];
    }
}

