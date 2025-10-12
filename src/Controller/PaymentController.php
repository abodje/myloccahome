<?php

namespace App\Controller;

use App\Entity\Payment;
use App\Form\PaymentType;
use App\Repository\PaymentRepository;
use App\Repository\LeaseRepository;
use App\Repository\AdvancePaymentRepository;
use App\Service\PdfService;
use App\Service\ContractGenerationService;
use App\Service\PaymentSettingsService;
use App\Service\AdvancePaymentService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/mes-paiements')]
class PaymentController extends AbstractController
{
    #[Route('/', name: 'app_payment_index', methods: ['GET'])]
    public function index(
        PaymentRepository $paymentRepository,
        Request $request,
        AdvancePaymentRepository $advancePaymentRepository
    ): Response {
        /** @var \App\Entity\User|null $user */
        $user = $this->getUser();
        $status = $request->query->get('status');
        $type = $request->query->get('type');
        $year = $request->query->get('year', date('Y'));
        $month = $request->query->get('month');

        // Filtrer les paiements selon le rôle de l'utilisateur
        if ($user && in_array('ROLE_TENANT', $user->getRoles())) {
            // Si l'utilisateur est un locataire, ne montrer que ses paiements
            $tenant = $user->getTenant();
            if ($tenant) {
                $payments = $paymentRepository->findByTenantWithFilters($tenant->getId(), $status, $type, $year, $month);
            } else {
                $payments = [];
            }
        } elseif ($user && in_array('ROLE_MANAGER', $user->getRoles())) {
            // Si l'utilisateur est un gestionnaire, montrer les paiements des locataires qu'il gère
            $owner = $user->getOwner();
            if ($owner) {
                $payments = $paymentRepository->findByManagerWithFilters($owner->getId(), $status, $type, $year, $month);
            } else {
                $payments = $paymentRepository->findWithFilters($status, $type, $year, $month);
            }
        } else {
            // Pour les admins, montrer tous les paiements
            $payments = $paymentRepository->findWithFilters($status, $type, $year, $month);
        }

        // Statistiques filtrées selon le rôle
        $stats = $this->calculateFilteredStats($paymentRepository, $user);

        // Statistiques des acomptes filtrées
        $advanceStats = $this->calculateFilteredAdvanceStats($advancePaymentRepository, $user);

        return $this->render('payment/index.html.twig', [
            'payments' => $payments,
            'stats' => $stats,
            'advance_stats' => $advanceStats,
            'current_status' => $status,
            'current_type' => $type,
            'current_year' => $year,
            'current_month' => $month,
        ]);
    }

    #[Route('/nouveau', name: 'app_payment_new', methods: ['GET', 'POST'])]
    public function new(Request $request, EntityManagerInterface $entityManager): Response
    {
        $payment = new Payment();
        $form = $this->createForm(PaymentType::class, $payment);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->persist($payment);
            $entityManager->flush();

            $this->addFlash('success', 'Le paiement a été créé avec succès.');

            return $this->redirectToRoute('app_payment_show', ['id' => $payment->getId()]);
        }

        return $this->render('payment/new.html.twig', [
            'payment' => $payment,
            'form' => $form,
        ]);
    }

    #[Route('/{id}', name: 'app_payment_show', methods: ['GET'])]
    public function show(Payment $payment): Response
    {
        return $this->render('payment/show.html.twig', [
            'payment' => $payment,
        ]);
    }

    #[Route('/{id}/modifier', name: 'app_payment_edit', methods: ['GET', 'POST'])]
    public function edit(Request $request, Payment $payment, EntityManagerInterface $entityManager): Response
    {
        $form = $this->createForm(PaymentType::class, $payment);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $payment->setUpdatedAt(new \DateTime());
            $entityManager->flush();

            $this->addFlash('success', 'Le paiement a été modifié avec succès.');

            return $this->redirectToRoute('app_payment_show', ['id' => $payment->getId()]);
        }

        return $this->render('payment/edit.html.twig', [
            'payment' => $payment,
            'form' => $form,
        ]);
    }

    #[Route('/{id}/marquer-paye', name: 'app_payment_mark_paid', methods: ['POST'])]
    public function markPaid(
        Request $request,
        Payment $payment,
        EntityManagerInterface $entityManager,
        ContractGenerationService $contractService,
        PaymentSettingsService $paymentSettings
    ): Response {
        $paidDate = $request->request->get('paid_date') ?? $request->getPayload()->getString('paid_date');
        $paymentMethod = $request->request->get('payment_method') ?? $request->getPayload()->getString('payment_method');
        $reference = $request->request->get('reference') ?? $request->getPayload()->getString('reference');
        $paidAmount = $request->request->get('paid_amount');

        // Si un montant est spécifié, valider qu'il est conforme aux règles
        if ($paidAmount !== null && $paidAmount !== '') {
            $paidAmount = (float) $paidAmount;
            $dueAmount = (float) $payment->getAmount();

            // Valider le montant selon les paramètres configurés
            $errors = $paymentSettings->validatePaymentAmount($paidAmount, $dueAmount);

            if (!empty($errors)) {
                foreach ($errors as $error) {
                    $this->addFlash('error', $error);
                }
                return $this->redirectToRoute('app_payment_show', ['id' => $payment->getId()]);
            }

            // Si paiement partiel, créer un nouveau paiement pour le solde
            if ($paidAmount < $dueAmount) {
                $remainingAmount = $dueAmount - $paidAmount;

                // Modifier le montant du paiement actuel
                $payment->setAmount($paidAmount);

                // Créer un nouveau paiement pour le solde
                $remainingPayment = new Payment();
                $remainingPayment->setLease($payment->getLease());
                $remainingPayment->setType($payment->getType());
                $remainingPayment->setAmount($remainingAmount);
                $remainingPayment->setDueDate($payment->getDueDate());
                $remainingPayment->setStatus('En attente');
                $entityManager->persist($remainingPayment);

                $this->addFlash('info', sprintf(
                    'Paiement partiel enregistré. Un solde de %s reste à payer.',
                    number_format($remainingAmount, 2)
                ));
            }
        }

        $payment->markAsPaid(
            $paidDate ? new \DateTime($paidDate) : new \DateTime(),
            $paymentMethod,
            $reference
        );

        $entityManager->flush();

        $this->addFlash('success', 'Le paiement a été marqué comme payé.');

        // 🎯 Générer automatiquement le contrat de bail si c'est la caution
        if ($payment->getType() === 'Dépôt de garantie' || $payment->getType() === 'Caution') {
            try {
                $contract = $contractService->generateContractAfterDeposit($payment);
                if ($contract) {
                    $this->addFlash('success', '📄 Le contrat de bail a été généré automatiquement et est disponible dans les documents !');
                } else {
                    $this->addFlash('info', 'Le contrat existe déjà ou la caution n\'est pas encore payée.');
                }
            } catch (\Exception $e) {
                $this->addFlash('error', 'Erreur lors de la génération du contrat : ' . $e->getMessage());
            }
        }

        return $this->redirectToRoute('app_payment_show', ['id' => $payment->getId()]);
    }

    #[Route('/generer-loyers/{year}/{month}', name: 'app_payment_generate_rents', methods: ['POST'])]
    public function generateRents(
        int $year,
        int $month,
        LeaseRepository $leaseRepository,
        EntityManagerInterface $entityManager,
        AdvancePaymentService $advanceService
    ): Response {
        $activeLeases = $leaseRepository->findBy(['status' => 'Actif']);
        $generated = 0;
        $autoPaid = 0;

        foreach ($activeLeases as $lease) {
            // Vérifier si le loyer n'existe pas déjà pour ce mois
            $dueDate = new \DateTime("{$year}-{$month}-" . ($lease->getRentDueDay() ?? 1));

            // ⚠️ VÉRIFICATION : Ne pas générer de loyer après la fin du bail
            if ($lease->getEndDate() && $dueDate > $lease->getEndDate()) {
                // La date d'échéance dépasse la fin du bail, on ne génère pas
                continue;
            }

            $existingPayment = $entityManager->getRepository(Payment::class)->findOneBy([
                'lease' => $lease,
                'dueDate' => $dueDate,
                'type' => 'Loyer'
            ]);

            if (!$existingPayment) {
                $payment = new Payment();
                $payment->setLease($lease);
                $payment->setDueDate($dueDate);
                $payment->setAmount($lease->getMonthlyRent());
                $payment->setType('Loyer');
                $payment->setStatus('En attente');

                $entityManager->persist($payment);
                $entityManager->flush(); // Flush pour obtenir l'ID

                // 💰 Appliquer automatiquement les acomptes disponibles
                $amountUsed = $advanceService->applyAdvanceToPayment($payment);
                if ($amountUsed > 0 && $payment->getStatus() === 'Payé') {
                    $autoPaid++;
                }

                $generated++;
            }
        }

        if ($generated > 0) {
            $this->addFlash('success', "{$generated} loyers ont été générés pour {$month}/{$year}.");
            if ($autoPaid > 0) {
                $this->addFlash('success', "💰 {$autoPaid} loyer(s) ont été payés automatiquement avec les acomptes disponibles !");
            }
        } else {
            $this->addFlash('info', "Aucun nouveau loyer à générer pour {$month}/{$year}.");
        }

        return $this->redirectToRoute('app_payment_index');
    }

    #[Route('/{id}/calculer-penalites', name: 'app_payment_calculate_late_fee', methods: ['POST'])]
    public function calculateLateFee(
        Payment $payment,
        EntityManagerInterface $entityManager,
        PaymentSettingsService $paymentSettings
    ): Response {
        // Vérifier que le paiement est en retard
        if ($payment->getStatus() === 'Payé') {
            $this->addFlash('error', 'Ce paiement a déjà été payé. Aucune pénalité ne peut être appliquée.');
            return $this->redirectToRoute('app_payment_show', ['id' => $payment->getId()]);
        }

        $now = new \DateTime();
        $dueDate = $payment->getDueDate();

        if ($dueDate >= $now) {
            $this->addFlash('error', 'Ce paiement n\'est pas encore en retard.');
            return $this->redirectToRoute('app_payment_show', ['id' => $payment->getId()]);
        }

        // Calculer le nombre de jours de retard
        $interval = $now->diff($dueDate);
        $daysLate = $interval->days;

        // Calculer le montant de la pénalité
        $lateFee = $paymentSettings->calculateLateFee($payment->getAmount(), $daysLate);

        // Vérifier si une pénalité existe déjà pour ce paiement
        $existingPenalty = $entityManager->getRepository(Payment::class)->findOneBy([
            'lease' => $payment->getLease(),
            'type' => 'Pénalité de retard',
            'notes' => 'Pénalité pour paiement #' . $payment->getId(),
        ]);

        if ($existingPenalty) {
            $this->addFlash('warning', 'Une pénalité a déjà été calculée pour ce paiement.');
            return $this->redirectToRoute('app_payment_show', ['id' => $payment->getId()]);
        }

        // Créer un nouveau paiement pour les pénalités
        $penaltyPayment = new Payment();
        $penaltyPayment->setLease($payment->getLease());
        $penaltyPayment->setType('Pénalité de retard');
        $penaltyPayment->setAmount($lateFee);
        $penaltyPayment->setDueDate(new \DateTime());
        $penaltyPayment->setStatus('En attente');
        $penaltyPayment->setNotes(sprintf(
            'Pénalité pour paiement #%d (%d jours de retard, taux: %s%%)',
            $payment->getId(),
            $daysLate,
            $paymentSettings->getLateFeeRate()
        ));

        $entityManager->persist($penaltyPayment);
        $entityManager->flush();

        $this->addFlash('success', sprintf(
            'Pénalité de retard calculée et ajoutée : %s (%d jours de retard à %s%%)',
            number_format($lateFee, 2),
            $daysLate,
            $paymentSettings->getLateFeeRate()
        ));

        return $this->redirectToRoute('app_payment_show', ['id' => $payment->getId()]);
    }

    #[Route('/calculer-toutes-penalites', name: 'app_payment_calculate_all_late_fees', methods: ['POST'])]
    public function calculateAllLateFees(
        PaymentRepository $paymentRepository,
        EntityManagerInterface $entityManager,
        PaymentSettingsService $paymentSettings
    ): Response {
        $overduePayments = $paymentRepository->findOverdue();
        $penaltiesCreated = 0;
        $now = new \DateTime();

        foreach ($overduePayments as $payment) {
            // Vérifier qu'une pénalité n'existe pas déjà
            $existingPenalty = $entityManager->getRepository(Payment::class)->findOneBy([
                'lease' => $payment->getLease(),
                'type' => 'Pénalité de retard',
                'notes' => 'Pénalité pour paiement #' . $payment->getId(),
            ]);

            if ($existingPenalty) {
                continue;
            }

            // Calculer le nombre de jours de retard
            $interval = $now->diff($payment->getDueDate());
            $daysLate = $interval->days;

            if ($daysLate <= 0) {
                continue;
            }

            // Calculer et créer la pénalité
            $lateFee = $paymentSettings->calculateLateFee($payment->getAmount(), $daysLate);

            $penaltyPayment = new Payment();
            $penaltyPayment->setLease($payment->getLease());
            $penaltyPayment->setType('Pénalité de retard');
            $penaltyPayment->setAmount($lateFee);
            $penaltyPayment->setDueDate(new \DateTime());
            $penaltyPayment->setStatus('En attente');
            $penaltyPayment->setNotes(sprintf(
                'Pénalité pour paiement #%d (%d jours de retard, taux: %s%%)',
                $payment->getId(),
                $daysLate,
                $paymentSettings->getLateFeeRate()
            ));

            $entityManager->persist($penaltyPayment);
            $penaltiesCreated++;
        }

        if ($penaltiesCreated > 0) {
            $entityManager->flush();
            $this->addFlash('success', sprintf(
                '%d pénalité(s) de retard ont été calculées et ajoutées.',
                $penaltiesCreated
            ));
        } else {
            $this->addFlash('info', 'Aucune nouvelle pénalité à calculer.');
        }

        return $this->redirectToRoute('app_payment_index');
    }

    #[Route('/en-retard', name: 'app_payment_overdue', methods: ['GET'])]
    public function overdue(PaymentRepository $paymentRepository): Response
    {
        $overduePayments = $paymentRepository->findOverdue();

        return $this->render('payment/overdue.html.twig', [
            'overdue_payments' => $overduePayments,
        ]);
    }

    #[Route('/quittances/{year}/{month}', name: 'app_payment_receipts', methods: ['GET'])]
    public function receipts(int $year, int $month, PaymentRepository $paymentRepository): Response
    {
        $startDate = new \DateTime("{$year}-{$month}-01");
        $endDate = clone $startDate;
        $endDate->modify('last day of this month');

        $paidPayments = $paymentRepository->findPaidByPeriod($startDate, $endDate);

        return $this->render('payment/receipts.html.twig', [
            'payments' => $paidPayments,
            'year' => $year,
            'month' => $month,
        ]);
    }

    #[Route('/{id}/supprimer', name: 'app_payment_delete', methods: ['POST'])]
    public function delete(Request $request, Payment $payment, EntityManagerInterface $entityManager): Response
    {
        if ($this->isCsrfTokenValid('delete'.$payment->getId(), $request->getPayload()->getString('_token'))) {
            $entityManager->remove($payment);
            $entityManager->flush();

            $this->addFlash('success', 'Le paiement a été supprimé avec succès.');
        }

        return $this->redirectToRoute('app_payment_index');
    }

    #[Route('/moyens-paiement', name: 'app_payment_methods', methods: ['GET'])]
    public function paymentMethods(): Response
    {
        return $this->render('payment/methods.html.twig');
    }

    #[Route('/configurer-prelevement', name: 'app_payment_setup_direct_debit', methods: ['GET', 'POST'])]
    public function setupDirectDebit(Request $request): Response
    {
        if ($request->isMethod('POST')) {
            // Ici vous pourriez traiter la configuration du prélèvement automatique
            $this->addFlash('success', 'Prélèvement automatique configuré avec succès.');
            return $this->redirectToRoute('app_payment_methods');
        }

        return $this->render('payment/setup_direct_debit.html.twig');
    }

    #[Route('/quittance/{id}', name: 'app_payment_receipt', methods: ['GET'])]
    public function receipt(Payment $payment): Response
    {
        if (!$payment->isPaid()) {
            throw $this->createNotFoundException('Quittance disponible uniquement pour les paiements effectués.');
        }

        return $this->render('payment/receipt.html.twig', [
            'payment' => $payment,
        ]);
    }

    #[Route('/{id}/recu-pdf', name: 'app_payment_receipt_pdf', methods: ['GET'])]
    public function downloadReceipt(Payment $payment, PdfService $pdfService): Response
    {
        if (!$payment->isPaid()) {
            throw $this->createNotFoundException('Reçu disponible uniquement pour les paiements effectués.');
        }

        $pdfService->generatePaymentReceipt($payment, true);
        return new Response(); // Le PDF est déjà envoyé par generatePaymentReceipt
    }

    #[Route('/quittance-mensuelle/{leaseId}/{month}', name: 'app_payment_monthly_quittance_pdf', methods: ['GET'])]
    public function downloadMonthlyQuittance(
        int $leaseId,
        string $month,
        LeaseRepository $leaseRepository,
        PaymentRepository $paymentRepository,
        PdfService $pdfService
    ): Response {
        $lease = $leaseRepository->find($leaseId);
        if (!$lease) {
            throw $this->createNotFoundException('Contrat introuvable.');
        }

        $monthDate = \DateTime::createFromFormat('Y-m', $month);
        if (!$monthDate) {
            throw $this->createNotFoundException('Format de mois invalide.');
        }
        $monthDate->modify('first day of this month');

        // Récupérer les paiements payés pour ce mois
        $startDate = clone $monthDate;
        $endDate = (clone $monthDate)->modify('last day of this month');

        $payments = $paymentRepository->createQueryBuilder('p')
            ->where('p.lease = :lease')
            ->andWhere('p.status = :status')
            ->andWhere('p.paidDate BETWEEN :start AND :end')
            ->setParameter('lease', $lease)
            ->setParameter('status', 'Payé')
            ->setParameter('start', $startDate)
            ->setParameter('end', $endDate)
            ->getQuery()
            ->getResult();

        if (empty($payments)) {
            throw $this->createNotFoundException('Aucun paiement trouvé pour ce mois.');
        }

        $pdfService->generateRentQuittance($payments, $lease, $monthDate, true);
        return new Response(); // Le PDF est déjà envoyé
    }

    /**
     * Calcule les statistiques filtrées selon le rôle de l'utilisateur
     */
    private function calculateFilteredStats(PaymentRepository $paymentRepository, $user): array
    {
        if ($user && in_array('ROLE_TENANT', $user->getRoles())) {
            // Pour les locataires, calculer les stats sur leurs paiements seulement
            $tenant = $user->getTenant();
            if ($tenant) {
                $tenantPayments = $paymentRepository->findByTenantWithFilters($tenant->getId());

                $stats = [
                    'total_pending' => 0,
                    'total_paid' => 0,
                    'total_overdue' => 0,
                    'monthly_income' => 0
                ];

                foreach ($tenantPayments as $payment) {
                    if ($payment->getStatus() === 'En attente') {
                        $stats['total_pending']++;
                    } elseif ($payment->getStatus() === 'Payé') {
                        $stats['total_paid']++;
                        $stats['monthly_income'] += $payment->getAmount();
                    }

                    // Vérifier si le paiement est en retard
                    if ($payment->getStatus() === 'En attente' && $payment->getDueDate() < new \DateTime()) {
                        $stats['total_overdue']++;
                    }
                }

                return $stats;
            }
        } elseif ($user && in_array('ROLE_MANAGER', $user->getRoles())) {
            // Pour les gestionnaires, calculer les stats sur les paiements qu'ils gèrent
            $owner = $user->getOwner();
            if ($owner) {
                $managerPayments = $paymentRepository->findByManagerWithFilters($owner->getId());

                $stats = [
                    'total_pending' => 0,
                    'total_paid' => 0,
                    'total_overdue' => 0,
                    'monthly_income' => 0
                ];

                foreach ($managerPayments as $payment) {
                    if ($payment->getStatus() === 'En attente') {
                        $stats['total_pending']++;
                    } elseif ($payment->getStatus() === 'Payé') {
                        $stats['total_paid']++;
                        $stats['monthly_income'] += $payment->getAmount();
                    }

                    // Vérifier si le paiement est en retard
                    if ($payment->getStatus() === 'En attente' && $payment->getDueDate() < new \DateTime()) {
                        $stats['total_overdue']++;
                    }
                }

                return $stats;
            }
        }

        // Pour les admins, retourner les stats globales
        return [
            'total_pending' => $paymentRepository->count(['status' => 'En attente']),
            'total_paid' => $paymentRepository->count(['status' => 'Payé']),
            'total_overdue' => count($paymentRepository->findOverdue()),
            'monthly_income' => $paymentRepository->getMonthlyIncome(),
        ];
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
                // Récupérer les acomptes via les baux du locataire
                $tenantAdvances = $advancePaymentRepository->createQueryBuilder('ap')
                    ->join('ap.lease', 'l')
                    ->where('l.tenant = :tenant')
                    ->setParameter('tenant', $tenant)
                    ->getQuery()
                    ->getResult();

                $stats = [
                    'total' => count($tenantAdvances),
                    'available_balance' => 0,
                    'used_balance' => 0
                ];

                foreach ($tenantAdvances as $advance) {
                    if ($advance->getStatus() === 'Disponible') {
                        $stats['available_balance'] += $advance->getRemainingBalance();
                    }
                    $stats['used_balance'] += ($advance->getAmount() - $advance->getRemainingBalance());
                }

                return $stats;
            }
        } elseif ($user && in_array('ROLE_MANAGER', $user->getRoles())) {
            // Pour les gestionnaires, calculer les stats sur les acomptes des locataires qu'ils gèrent
            $owner = $user->getOwner();
            if ($owner) {
                $managerAdvances = $advancePaymentRepository->createQueryBuilder('ap')
                    ->join('ap.lease', 'l')
                    ->join('l.property', 'p')
                    ->where('p.owner = :owner')
                    ->setParameter('owner', $owner)
                    ->getQuery()
                    ->getResult();

                $stats = [
                    'total' => count($managerAdvances),
                    'available_balance' => 0,
                    'used_balance' => 0
                ];

                foreach ($managerAdvances as $advance) {
                    if ($advance->getStatus() === 'available') {
                        $stats['available_balance'] += $advance->getRemainingBalance();
                    }
                    $stats['used_balance'] += ($advance->getAmount() - $advance->getRemainingBalance());
                }

                return $stats;
            }
        }

        // Pour les admins, retourner les stats globales
        return $advancePaymentRepository->getStatistics();
    }
}
