<?php

namespace App\Controller;

use App\Entity\AccountingEntry;
use App\Form\AccountingEntryType;
use App\Repository\AccountingEntryRepository;
use App\Service\AccountingService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/ma-comptabilite')]
class AccountingController extends AbstractController
{
    #[Route('/', name: 'app_accounting_index', methods: ['GET'])]
    public function index(AccountingEntryRepository $accountingRepository, Request $request): Response
    {
        /** @var \App\Entity\User|null $user */
        $user = $this->getUser();
        $type = $request->query->get('type');
        $category = $request->query->get('category');
        $year = $request->query->get('year', date('Y'));
        $month = $request->query->get('month');

        // Filtrer les écritures selon le rôle de l'utilisateur
        if ($user && in_array('ROLE_TENANT', $user->getRoles())) {
            // Si l'utilisateur est un locataire, ne montrer que ses écritures
            $tenant = $user->getTenant();
            if ($tenant) {
                $entries = $accountingRepository->findByTenantWithFilters($tenant->getId(), $type, $category, $year, $month);
                $stats = $accountingRepository->getTenantStatistics($tenant->getId());
            } else {
                $entries = [];
                $stats = [
                    'total_credits' => 0,
                    'total_debits' => 0,
                    'balance' => 0,
                    'current_month_credits' => 0,
                    'current_month_debits' => 0,
                ];
            }
        } elseif ($user && in_array('ROLE_MANAGER', $user->getRoles())) {
            // Si l'utilisateur est un gestionnaire, montrer les écritures de ses propriétés
            $owner = $user->getOwner();
            if ($owner) {
                $entries = $accountingRepository->findByManagerWithFilters($owner->getId(), $type, $category, $year, $month);
                $stats = $accountingRepository->getManagerStatistics($owner->getId());
            } else {
                $entries = $accountingRepository->findWithFilters($type, $category, $year, $month);
                $stats = $accountingRepository->getAccountingStatistics();
            }
        } elseif ($user && (in_array('ROLE_ADMIN', $user->getRoles()) || in_array('ROLE_SUPER_ADMIN', $user->getRoles()))) {
            // Pour les admins, filtrer selon l'organisation/société
            $organization = $user->getOrganization();
            $company = $user->getCompany();

            error_log("AccountingController - Admin: organization=" . ($organization ? $organization->getName() : 'null') . ", company=" . ($company ? $company->getName() : 'null'));

            if ($company) {
                // Admin avec société spécifique : filtrer par société
                $entries = $accountingRepository->findByCompanyWithFilters($company, $type, $category, $year, $month);
                $stats = $accountingRepository->getCompanyStatistics($company);
                error_log("AccountingController - Filtered by company: " . $company->getName());
            } elseif ($organization) {
                // Admin avec organisation : filtrer par organisation
                $entries = $accountingRepository->findByOrganizationWithFilters($organization, $type, $category, $year, $month);
                $stats = $accountingRepository->getOrganizationStatistics($organization);
                error_log("AccountingController - Filtered by organization: " . $organization->getName());
            } else {
                // Super Admin sans organisation/société : toutes les écritures
                $entries = $accountingRepository->findWithFilters($type, $category, $year, $month);
                $stats = $accountingRepository->getAccountingStatistics();
                error_log("AccountingController - Super Admin: showing all entries");
            }
        } else {
            // Pour les autres rôles, montrer toutes les écritures
            $entries = $accountingRepository->findWithFilters($type, $category, $year, $month);
            $stats = $accountingRepository->getAccountingStatistics();
        }

        // Passer une variable pour indiquer si c'est la vue locataire
        $isTenantView = $user && in_array('ROLE_TENANT', $user->getRoles());

        return $this->render('accounting/index.html.twig', [
            'entries' => $entries,
            'stats' => $stats,
            'current_type' => $type,
            'current_category' => $category,
            'current_year' => $year,
            'current_month' => $month,
            'is_tenant_view' => $isTenantView,
        ]);
    }

    #[Route('/nouvelle-ecriture', name: 'app_accounting_new', methods: ['GET', 'POST'])]
    public function new(Request $request, EntityManagerInterface $entityManager): Response
    {
        $entry = new AccountingEntry();
        $form = $this->createForm(AccountingEntryType::class, $entry);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->persist($entry);
            $entityManager->flush();

            // Recalculer les soldes courants
            $entityManager->getRepository(AccountingEntry::class)->recalculateRunningBalances();

            $this->addFlash('success', 'L\'écriture comptable a été créée avec succès.');

            return $this->redirectToRoute('app_accounting_index');
        }

        return $this->render('accounting/new.html.twig', [
            'entry' => $entry,
            'form' => $form,
        ]);
    }

    #[Route('/{id}', name: 'app_accounting_show', methods: ['GET'])]
    public function show(AccountingEntry $entry): Response
    {
        return $this->render('accounting/show.html.twig', [
            'entry' => $entry,
        ]);
    }

    #[Route('/{id}/modifier', name: 'app_accounting_edit', methods: ['GET', 'POST'])]
    public function edit(Request $request, AccountingEntry $entry, EntityManagerInterface $entityManager): Response
    {
        $form = $this->createForm(AccountingEntryType::class, $entry);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entry->setUpdatedAt(new \DateTime());
            $entityManager->flush();

            // Recalculer les soldes courants
            $entityManager->getRepository(AccountingEntry::class)->recalculateRunningBalances();

            $this->addFlash('success', 'L\'écriture comptable a été modifiée avec succès.');

            return $this->redirectToRoute('app_accounting_show', ['id' => $entry->getId()]);
        }

        return $this->render('accounting/edit.html.twig', [
            'entry' => $entry,
            'form' => $form,
        ]);
    }

    #[Route('/{id}/supprimer', name: 'app_accounting_delete', methods: ['POST'])]
    public function delete(Request $request, AccountingEntry $entry, EntityManagerInterface $entityManager): Response
    {
        if ($this->isCsrfTokenValid('delete'.$entry->getId(), $request->getPayload()->getString('_token'))) {
            $entityManager->remove($entry);
            $entityManager->flush();

            // Recalculer les soldes courants
            $entityManager->getRepository(AccountingEntry::class)->recalculateRunningBalances();

            $this->addFlash('success', 'L\'écriture comptable a été supprimée avec succès.');
        }

        return $this->redirectToRoute('app_accounting_index');
    }

    #[Route('/export/{format}', name: 'app_accounting_export', methods: ['GET'])]
    public function export(string $format, AccountingEntryRepository $accountingRepository, Request $request): Response
    {
        $startDate = $request->query->get('start_date') ? new \DateTime($request->query->get('start_date')) : new \DateTime('first day of this year');
        $endDate = $request->query->get('end_date') ? new \DateTime($request->query->get('end_date')) : new \DateTime('last day of this year');

        $entries = $accountingRepository->findByDateRange($startDate, $endDate);

        if ($format === 'csv') {
            return $this->exportToCsv($entries, $startDate, $endDate);
        }

        throw $this->createNotFoundException('Format d\'export non supporté');
    }

    #[Route('/recalculer-soldes', name: 'app_accounting_recalculate', methods: ['POST'])]
    public function recalculateBalances(AccountingEntryRepository $accountingRepository): Response
    {
        $accountingRepository->recalculateRunningBalances();

        $this->addFlash('success', 'Les soldes ont été recalculés avec succès.');

        return $this->redirectToRoute('app_accounting_index');
    }

    #[Route('/statistiques', name: 'app_accounting_stats', methods: ['GET'])]
    public function statistics(AccountingEntryRepository $accountingRepository): Response
    {
        $stats = $accountingRepository->getAccountingStatistics();

        // Données pour les graphiques (12 derniers mois)
        $monthlyData = [];
        for ($i = 11; $i >= 0; $i--) {
            $date = new \DateTime();
            $date->modify("-{$i} months");
            $startDate = clone $date;
            $startDate->modify('first day of this month');
            $endDate = clone $date;
            $endDate->modify('last day of this month');

            $monthlyEntries = $accountingRepository->findByDateRange($startDate, $endDate);

            $credits = 0;
            $debits = 0;
            foreach ($monthlyEntries as $entry) {
                if ($entry->isCredit()) {
                    $credits += (float)$entry->getAmount();
                } else {
                    $debits += (float)$entry->getAmount();
                }
            }

            $monthlyData[] = [
                'month' => $date->format('M Y'),
                'credits' => $credits,
                'debits' => $debits,
                'net' => $credits - $debits,
            ];
        }

        return $this->render('accounting/statistics.html.twig', [
            'stats' => $stats,
            'monthly_data' => $monthlyData,
        ]);
    }

    private function exportToCsv(array $entries, \DateTime $startDate, \DateTime $endDate): Response
    {
        $filename = sprintf('comptabilite_%s_%s.csv',
            $startDate->format('Y-m-d'),
            $endDate->format('Y-m-d')
        );

        $response = new Response();
        $response->headers->set('Content-Type', 'text/csv');
        $response->headers->set('Content-Disposition', 'attachment; filename="' . $filename . '"');

        $output = fopen('php://temp', 'w');

        // En-têtes CSV
        fputcsv($output, [
            'Date',
            'Description',
            'Référence',
            'Catégorie',
            'Type',
            'Montant',
            'Solde courant'
        ], ';');

        // Données
        foreach ($entries as $entry) {
            fputcsv($output, [
                $entry->getEntryDate()->format('d/m/Y'),
                $entry->getDescription(),
                $entry->getReference(),
                $entry->getCategory(),
                $entry->getType(),
                $entry->getFormattedAmount(),
                number_format((float)$entry->getRunningBalance(), 2, ',', ' ') . ' €'
            ], ';');
        }

        rewind($output);
        $response->setContent(stream_get_contents($output));
        fclose($output);

        return $response;
    }
}
