<?php

namespace App\Service;

use App\Entity\AdvancePayment;
use App\Entity\Lease;
use App\Entity\Payment;
use App\Repository\AdvancePaymentRepository;
use Doctrine\ORM\EntityManagerInterface;

/**
 * Service de gestion des paiements anticipés (acomptes)
 * Permet aux locataires de constituer un solde qui sera utilisé automatiquement
 */
class AdvancePaymentService
{
    public function __construct(
        private EntityManagerInterface $entityManager,
        private AdvancePaymentRepository $advancePaymentRepository,
        private PaymentSettingsService $paymentSettings,
        private ?AccountingService $accountingService = null
    ) {
    }

    /**
     * Crée un nouvel acompte pour un bail
     */
    public function createAdvancePayment(
        Lease $lease,
        float $amount,
        string $paymentMethod,
        ?string $reference = null,
        ?string $notes = null
    ): AdvancePayment {
        $advance = new AdvancePayment();
        $advance->setLease($lease);
        $advance->setAmount((string) $amount);
        $advance->setRemainingBalance((string) $amount);
        $advance->setPaymentMethod($paymentMethod);
        $advance->setReference($reference);
        $advance->setNotes($notes);

        $this->entityManager->persist($advance);
        $this->entityManager->flush();

        return $advance;
    }

    /**
     * Récupère le solde total disponible pour un bail
     */
    public function getAvailableBalance(Lease $lease): float
    {
        return $this->advancePaymentRepository->getTotalAvailableBalance($lease);
    }

    /**
     * Utilise automatiquement les acomptes disponibles pour payer un loyer
     * Retourne le montant total utilisé
     */
    public function applyAdvanceToPayment(Payment $payment): float
    {
        if (!$this->paymentSettings->isAdvancePaymentAllowed()) {
            return 0;
        }

        $lease = $payment->getLease();
        $remainingAmount = (float) $payment->getAmount();
        $totalUsed = 0;

        // Récupérer les acomptes disponibles (FIFO)
        $availableAdvances = $this->advancePaymentRepository->findAvailableByLease($lease);

        foreach ($availableAdvances as $advance) {
            if ($remainingAmount <= 0) {
                break;
            }

            $advanceBalance = (float) $advance->getRemainingBalance();
            $amountToUse = min($advanceBalance, $remainingAmount);

            // Utiliser l'acompte
            $advance->useBalance($amountToUse);
            $totalUsed += $amountToUse;
            $remainingAmount -= $amountToUse;

            // Ajouter une note sur le paiement
            $currentNotes = $payment->getNotes() ?? '';
            $newNote = sprintf(
                "Acompte #%d utilisé: %s (Solde restant: %s)",
                $advance->getId(),
                number_format($amountToUse, 2) . '€',
                number_format((float) $advance->getRemainingBalance(), 2) . '€'
            );
            $payment->setNotes($currentNotes ? $currentNotes . "\n" . $newNote : $newNote);

            // 📊 Enregistrer l'utilisation en comptabilité
            if ($this->accountingService) {
                try {
                    $this->accountingService->recordAdvanceUsage($advance, $amountToUse, $payment);
                } catch (\Exception $e) {
                    // Log l'erreur mais continue le traitement
                }
            }
        }

        if ($totalUsed > 0) {
            // Si le paiement est entièrement couvert
            if ($remainingAmount <= 0) {
                $payment->markAsPaid(new \DateTime(), 'Acompte automatique', 'AUTO-' . date('YmdHis'));
            }

            $this->entityManager->flush();
        }

        return $totalUsed;
    }

    /**
     * Applique automatiquement les acomptes à tous les paiements en attente d'un bail
     */
    public function applyAdvanceToAllPendingPayments(Lease $lease): array
    {
        $results = [
            'payments_processed' => 0,
            'payments_fully_paid' => 0,
            'total_amount_used' => 0,
        ];

        $pendingPayments = $this->entityManager
            ->getRepository(Payment::class)
            ->findBy(['lease' => $lease, 'status' => 'En attente'], ['dueDate' => 'ASC']);

        foreach ($pendingPayments as $payment) {
            $amountUsed = $this->applyAdvanceToPayment($payment);

            if ($amountUsed > 0) {
                $results['payments_processed']++;
                $results['total_amount_used'] += $amountUsed;

                if ($payment->getStatus() === 'Payé') {
                    $results['payments_fully_paid']++;
                }
            }
        }

        return $results;
    }

    /**
     * Rembourse un acompte (si nécessaire)
     */
    public function refundAdvancePayment(AdvancePayment $advance, ?string $reason = null): void
    {
        $advance->setStatus('Remboursé');
        $advance->setRemainingBalance('0');

        if ($reason) {
            $currentNotes = $advance->getNotes() ?? '';
            $advance->setNotes($currentNotes . "\nRemboursé: " . $reason);
        }

        $this->entityManager->flush();
    }

    /**
     * Vérifie si un bail peut utiliser des acomptes
     */
    public function canUseAdvancePayments(Lease $lease): bool
    {
        return $this->paymentSettings->isAdvancePaymentAllowed()
            && $this->getAvailableBalance($lease) > 0;
    }

    /**
     * Génère un rapport détaillé sur l'utilisation des acomptes pour un bail
     */
    public function getAdvancePaymentReport(Lease $lease): array
    {
        $advances = $this->entityManager
            ->getRepository(AdvancePayment::class)
            ->findBy(['lease' => $lease], ['paidDate' => 'DESC']);

        $totalPaid = 0;
        $totalUsed = 0;
        $totalAvailable = 0;

        foreach ($advances as $advance) {
            $totalPaid += (float) $advance->getAmount();
            $totalUsed += $advance->getUsedAmount();
            $totalAvailable += (float) $advance->getRemainingBalance();
        }

        return [
            'advances' => $advances,
            'total_paid' => $totalPaid,
            'total_used' => $totalUsed,
            'total_available' => $totalAvailable,
            'count' => count($advances),
        ];
    }

    /**
     * Transfère un solde d'acompte vers un autre bail (utile en cas de changement de logement)
     */
    public function transferAdvance(AdvancePayment $advance, Lease $newLease, ?string $reason = null): AdvancePayment
    {
        // Créer un nouvel acompte pour le nouveau bail
        $newAdvance = new AdvancePayment();
        $newAdvance->setLease($newLease);
        $newAdvance->setAmount($advance->getRemainingBalance());
        $newAdvance->setRemainingBalance($advance->getRemainingBalance());
        $newAdvance->setPaymentMethod('Transfert');
        $newAdvance->setReference('TRANSFER-' . $advance->getId());
        $newAdvance->setNotes(sprintf(
            "Transféré depuis acompte #%d%s",
            $advance->getId(),
            $reason ? " - Raison: $reason" : ''
        ));

        // Marquer l'ancien comme utilisé
        $advance->setRemainingBalance('0');
        $advance->setStatus('Utilisé');
        $currentNotes = $advance->getNotes() ?? '';
        $advance->setNotes($currentNotes . sprintf(
            "\nTransféré vers bail #%d le %s",
            $newLease->getId(),
            (new \DateTime())->format('d/m/Y H:i')
        ));

        $this->entityManager->persist($newAdvance);
        $this->entityManager->flush();

        return $newAdvance;
    }
}

