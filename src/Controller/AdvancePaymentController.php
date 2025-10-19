<?php

namespace App\Controller;

use App\Entity\AdvancePayment;
use App\Entity\Lease;
use App\Repository\AdvancePaymentRepository;
use App\Repository\LeaseRepository;
use App\Service\AdvancePaymentService;
use App\Service\PaymentSettingsService;
use App\Service\AccountingService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/acomptes')]
class AdvancePaymentController extends AbstractController
{
    #[Route('/', name: 'app_advance_payment_index', methods: ['GET'])]
    public function index(
        AdvancePaymentRepository $advancePaymentRepository,
        PaymentSettingsService $paymentSettings
    ): Response {
        // Vérifier si les paiements anticipés sont activés
        if (!$paymentSettings->isAdvancePaymentAllowed()) {
            $this->addFlash('warning', 'Les paiements anticipés sont désactivés. Contactez l\'administrateur.');
            return $this->redirectToRoute('app_payment_index');
        }

        /** @var \App\Entity\User|null $user */
        $user = $this->getUser();

        // Filtrer les acomptes selon le rôle de l'utilisateur
        if ($user && in_array('ROLE_TENANT', $user->getRoles())) {
            // Si l'utilisateur est un locataire, ne montrer que ses acomptes
            $tenant = $user->getTenant();
            if ($tenant) {
                $advances = $advancePaymentRepository->findByTenant($tenant->getId());
            } else {
                $advances = [];
            }
        } elseif ($user && in_array('ROLE_MANAGER', $user->getRoles())) {
            // Si l'utilisateur est un gestionnaire, montrer les acomptes des locataires qu'il gère
            $owner = $user->getOwner();
            if ($owner) {
                $advances = $advancePaymentRepository->findByManager($owner->getId());
            } else {
                $advances = $advancePaymentRepository->findBy([], ['paidDate' => 'DESC']);
            }
        } elseif ($user && (in_array('ROLE_ADMIN', $user->getRoles()) || in_array('ROLE_SUPER_ADMIN', $user->getRoles()))) {
            // Pour les admins, filtrer selon l'organisation/société
            $organization = $user->getOrganization();
            $company = $user->getCompany();

            error_log("AdvancePaymentController - Admin: organization=" . ($organization ? $organization->getName() : 'null') . ", company=" . ($company ? $company->getName() : 'null'));

            if ($company) {
                // Admin avec société spécifique : filtrer par société
                $advances = $advancePaymentRepository->findByCompany($company);
                error_log("AdvancePaymentController - Filtered by company: " . $company->getName());
            } elseif ($organization) {
                // Admin avec organisation : filtrer par organisation
                $advances = $advancePaymentRepository->findByOrganization($organization);
                error_log("AdvancePaymentController - Filtered by organization: " . $organization->getName());
            } else {
                // Super Admin sans organisation/société : tous les acomptes
                $advances = $advancePaymentRepository->findBy([], ['paidDate' => 'DESC']);
                error_log("AdvancePaymentController - Super Admin: showing all advances");
            }
        } else {
            // Pour les autres rôles, montrer tous les acomptes
            $advances = $advancePaymentRepository->findBy([], ['paidDate' => 'DESC']);
        }

        // Statistiques filtrées selon le rôle
        $stats = $this->calculateFilteredAdvanceStats($advancePaymentRepository, $user);

        return $this->render('advance_payment/index.html.twig', [
            'advances' => $advances,
            'stats' => $stats,
        ]);
    }

    #[Route('/nouveau', name: 'app_advance_payment_new', methods: ['GET', 'POST'])]
    public function new(
        Request $request,
        EntityManagerInterface $entityManager,
        LeaseRepository $leaseRepository,
        AdvancePaymentService $advanceService,
        PaymentSettingsService $paymentSettings,
        AccountingService $accountingService
    ): Response {
        if (!$paymentSettings->isAdvancePaymentAllowed()) {
            $this->addFlash('error', 'Les paiements anticipés sont désactivés.');
            return $this->redirectToRoute('app_payment_index');
        }

        if ($request->isMethod('POST')) {
            $leaseId = $request->request->get('lease_id');
            $amount = (float) $request->request->get('amount');
            $paymentMethod = $request->request->get('payment_method');
            $reference = $request->request->get('reference');
            $notes = $request->request->get('notes');

            // Validation
            $errors = [];

            if (!$leaseId) {
                $errors[] = "Veuillez sélectionner un bail";
            }

            if ($amount <= 0) {
                $errors[] = "Le montant doit être positif";
            }

            $minimumAmount = $paymentSettings->getMinimumAdvanceAmount();
            if ($amount < $minimumAmount) {
                $errors[] = sprintf("Le montant minimum pour un acompte est de %s", number_format($minimumAmount, 2) . '€');
            }

            if (!$paymentMethod) {
                $errors[] = "Veuillez sélectionner un mode de paiement";
            }

            if (!empty($errors)) {
                foreach ($errors as $error) {
                    $this->addFlash('error', $error);
                }
                return $this->redirectToRoute('app_advance_payment_new');
            }

            // Créer l'acompte
            $lease = $leaseRepository->find($leaseId);
            if (!$lease) {
                $this->addFlash('error', 'Bail introuvable');
                return $this->redirectToRoute('app_advance_payment_new');
            }

            try {
                $advance = $advanceService->createAdvancePayment(
                    $lease,
                    $amount,
                    $paymentMethod,
                    $reference,
                    $notes
                );

                // Enregistrer en comptabilité
                $accountingService->recordAdvancePayment($advance);

                // Appliquer automatiquement aux paiements en attente
                $results = $advanceService->applyAdvanceToAllPendingPayments($lease);

                $this->addFlash('success', sprintf(
                    'Acompte de %s enregistré avec succès !',
                    number_format($amount, 2) . '€'
                ));

                if ($results['payments_fully_paid'] > 0) {
                    $this->addFlash('success', sprintf(
                        '✅ %d paiement(s) ont été soldés automatiquement avec cet acompte !',
                        $results['payments_fully_paid']
                    ));
                }

                return $this->redirectToRoute('app_advance_payment_show', ['id' => $advance->getId()]);
            } catch (\Exception $e) {
                $this->addFlash('error', 'Erreur lors de la création : ' . $e->getMessage());
            }
        }

        $activeLeases = $leaseRepository->findBy(['status' => 'Actif'], ['startDate' => 'DESC']);

        return $this->render('advance_payment/new.html.twig', [
            'leases' => $activeLeases,
            'minimum_amount' => $paymentSettings->getMinimumAdvanceAmount(),
        ]);
    }

    #[Route('/{id}', name: 'app_advance_payment_show', methods: ['GET'])]
    public function show(AdvancePayment $advance): Response
    {
        return $this->render('advance_payment/show.html.twig', [
            'advance' => $advance,
        ]);
    }

    #[Route('/{id}/rembourser', name: 'app_advance_payment_refund', methods: ['POST'])]
    public function refund(
        AdvancePayment $advance,
        Request $request,
        AdvancePaymentService $advanceService,
        AccountingService $accountingService
    ): Response {
        if ((float) $advance->getRemainingBalance() <= 0) {
            $this->addFlash('error', 'Cet acompte n\'a plus de solde disponible à rembourser.');
            return $this->redirectToRoute('app_advance_payment_show', ['id' => $advance->getId()]);
        }

        $reason = $request->request->get('reason');

        try {
            $balanceToRefund = (float) $advance->getRemainingBalance();

            // Rembourser l'acompte
            $advanceService->refundAdvancePayment($advance, $reason);

            // Enregistrer en comptabilité
            $accountingService->recordAdvanceRefund($advance, $balanceToRefund, $reason);

            $this->addFlash('success', sprintf(
                'Acompte remboursé : %s',
                number_format($balanceToRefund, 2) . '€'
            ));
        } catch (\Exception $e) {
            $this->addFlash('error', 'Erreur lors du remboursement : ' . $e->getMessage());
        }

        return $this->redirectToRoute('app_advance_payment_show', ['id' => $advance->getId()]);
    }

    #[Route('/{id}/transferer', name: 'app_advance_payment_transfer', methods: ['POST'])]
    public function transfer(
        AdvancePayment $advance,
        Request $request,
        LeaseRepository $leaseRepository,
        AdvancePaymentService $advanceService
    ): Response {
        $newLeaseId = $request->request->get('new_lease_id');
        $reason = $request->request->get('reason');

        if (!$newLeaseId) {
            $this->addFlash('error', 'Veuillez sélectionner le bail de destination');
            return $this->redirectToRoute('app_advance_payment_show', ['id' => $advance->getId()]);
        }

        $newLease = $leaseRepository->find($newLeaseId);
        if (!$newLease) {
            $this->addFlash('error', 'Bail de destination introuvable');
            return $this->redirectToRoute('app_advance_payment_show', ['id' => $advance->getId()]);
        }

        try {
            $newAdvance = $advanceService->transferAdvance($advance, $newLease, $reason);

            $this->addFlash('success', sprintf(
                'Acompte transféré avec succès vers le bail #%d',
                $newLease->getId()
            ));

            return $this->redirectToRoute('app_advance_payment_show', ['id' => $newAdvance->getId()]);
        } catch (\Exception $e) {
            $this->addFlash('error', 'Erreur lors du transfert : ' . $e->getMessage());
            return $this->redirectToRoute('app_advance_payment_show', ['id' => $advance->getId()]);
        }
    }

    #[Route('/bail/{id}', name: 'app_advance_payment_by_lease', methods: ['GET'])]
    public function byLease(
        Lease $lease,
        AdvancePaymentService $advanceService
    ): Response {
        $report = $advanceService->getAdvancePaymentReport($lease);

        return $this->render('advance_payment/by_lease.html.twig', [
            'lease' => $lease,
            'report' => $report,
        ]);
    }

    #[Route('/bail/{id}/appliquer', name: 'app_advance_payment_apply_to_lease', methods: ['POST'])]
    public function applyToLease(
        Lease $lease,
        AdvancePaymentService $advanceService
    ): Response {
        try {
            $results = $advanceService->applyAdvanceToAllPendingPayments($lease);

            if ($results['payments_processed'] > 0) {
                $this->addFlash('success', sprintf(
                    '✅ %d paiement(s) traité(s), %d soldé(s) automatiquement avec un total de %s utilisé',
                    $results['payments_processed'],
                    $results['payments_fully_paid'],
                    number_format($results['total_amount_used'], 2) . '€'
                ));
            } else {
                $this->addFlash('info', 'Aucun paiement en attente à traiter');
            }
        } catch (\Exception $e) {
            $this->addFlash('error', 'Erreur : ' . $e->getMessage());
        }

        return $this->redirectToRoute('app_advance_payment_by_lease', ['id' => $lease->getId()]);
    }

    #[Route('/statistiques', name: 'app_advance_payment_statistics', methods: ['GET'])]
    public function statistics(
        AdvancePaymentRepository $advancePaymentRepository
    ): Response {
        $stats = $advancePaymentRepository->getStatistics();
        $recent = $advancePaymentRepository->findRecent(30);
        $available = $advancePaymentRepository->findByStatus('Disponible');
        $partiallyUsed = $advancePaymentRepository->findByStatus('Utilisé partiellement');

        return $this->render('advance_payment/statistics.html.twig', [
            'stats' => $stats,
            'recent' => $recent,
            'available' => $available,
            'partially_used' => $partiallyUsed,
        ]);
    }

    /**
     * Calcule les statistiques des acomptes filtrées selon le rôle de l'utilisateur
     */
    private function calculateFilteredAdvanceStats(AdvancePaymentRepository $advancePaymentRepository, $user): array
    {
        if ($user && in_array('ROLE_TENANT', $user->getRoles())) {
            // Pour les locataires, calculer les stats sur leurs acomptes seulement
            $tenant = $user->getTenant();
            if ($tenant) {
                $tenantAdvances = $advancePaymentRepository->findByTenant($tenant->getId());

                $stats = [
                    'total_amount' => 0,
                    'available_amount' => 0,
                    'used_amount' => 0,
                    'count' => count($tenantAdvances)
                ];

                foreach ($tenantAdvances as $advance) {
                    $stats['total_amount'] += $advance->getAmount();
                    $stats['available_amount'] += $advance->getRemainingAmount();
                    $stats['used_amount'] += ($advance->getAmount() - $advance->getRemainingAmount());
                }

                return $stats;
            }
        } elseif ($user && in_array('ROLE_MANAGER', $user->getRoles())) {
            // Pour les gestionnaires, calculer les stats sur les acomptes qu'ils gèrent
            $owner = $user->getOwner();
            if ($owner) {
                $managerAdvances = $advancePaymentRepository->findByManager($owner->getId());

                $stats = [
                    'total_amount' => 0,
                    'available_amount' => 0,
                    'used_amount' => 0,
                    'count' => count($managerAdvances)
                ];

                foreach ($managerAdvances as $advance) {
                    $stats['total_amount'] += $advance->getAmount();
                    $stats['available_amount'] += $advance->getRemainingAmount();
                    $stats['used_amount'] += ($advance->getAmount() - $advance->getRemainingAmount());
                }

                return $stats;
            }
        } elseif ($user && (in_array('ROLE_ADMIN', $user->getRoles()) || in_array('ROLE_SUPER_ADMIN', $user->getRoles()))) {
            // Pour les admins, calculer les stats selon l'organisation/société
            $organization = $user->getOrganization();
            $company = $user->getCompany();

            if ($company) {
                // Admin avec société spécifique
                $companyAdvances = $advancePaymentRepository->findByCompany($company);

                $stats = [
                    'total_amount' => 0,
                    'available_amount' => 0,
                    'used_amount' => 0,
                    'count' => count($companyAdvances)
                ];

                foreach ($companyAdvances as $advance) {
                    $stats['total_amount'] += $advance->getAmount();
                    $stats['available_amount'] += $advance->getRemainingAmount();
                    $stats['used_amount'] += ($advance->getAmount() - $advance->getRemainingAmount());
                }

                return $stats;
            } elseif ($organization) {
                // Admin avec organisation
                $orgAdvances = $advancePaymentRepository->findByOrganization($organization);

                $stats = [
                    'total_amount' => 0,
                    'available_amount' => 0,
                    'used_amount' => 0,
                    'count' => count($orgAdvances)
                ];

                foreach ($orgAdvances as $advance) {
                    $stats['total_amount'] += $advance->getAmount();
                    $stats['available_amount'] += $advance->getRemainingAmount();
                    $stats['used_amount'] += ($advance->getAmount() - $advance->getRemainingAmount());
                }

                return $stats;
            }
        }

        // Pour les super admins sans organisation/société, retourner les stats globales
        return $advancePaymentRepository->getStatistics();
    }
}

