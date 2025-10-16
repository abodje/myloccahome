<?php

namespace App\Service;

use App\Entity\AccountingEntry;
use App\Entity\Payment;
use App\Entity\Expense;
use App\Entity\AdvancePayment;
use App\Repository\AccountingEntryRepository;
use Doctrine\ORM\EntityManagerInterface;

class AccountingService
{
    public function __construct(
        private EntityManagerInterface $entityManager,
        private AccountingEntryRepository $accountingRepository
    ) {
    }

    /**
     * Crée une écriture comptable à partir d'un paiement
     */
    public function createEntryFromPayment(Payment $payment): AccountingEntry
    {
        // Vérifier si une écriture existe déjà pour ce paiement
        $existingEntry = $this->accountingRepository->findOneBy(['payment' => $payment]);
        if ($existingEntry) {
            return $existingEntry;
        }

        $entry = new AccountingEntry();
        $entry->setEntryDate($payment->getPaidDate() ?? $payment->getDueDate());
        $entry->setDescription($this->getPaymentDescription($payment));
        $entry->setAmount($payment->getAmount());
        $entry->setType('CREDIT'); // Les paiements sont des crédits
        $entry->setCategory($this->getPaymentCategory($payment));
        $entry->setReference($payment->getReference());
        $entry->setProperty($payment->getProperty());
        $entry->setOwner($payment->getProperty()?->getOwner());
        $entry->setPayment($payment);

        $this->entityManager->persist($entry);
        $this->entityManager->flush();

        // Recalculer les soldes
        $this->recalculateRunningBalances();

        return $entry;
    }

    /**
     * Crée une écriture comptable à partir d'une dépense
     */
    public function createEntryFromExpense(Expense $expense): AccountingEntry
    {
        // Vérifier si une écriture existe déjà pour cette dépense
        $existingEntry = $this->accountingRepository->findOneBy(['expense' => $expense]);
        if ($existingEntry) {
            return $existingEntry;
        }

        $entry = new AccountingEntry();
        $entry->setEntryDate($expense->getExpenseDate());
        $entry->setDescription($expense->getDescription());
        $entry->setAmount($expense->getAmount());
        $entry->setType('DEBIT'); // Les dépenses sont des débits
        $entry->setCategory($this->getExpenseCategory($expense));
        $entry->setReference($expense->getInvoiceNumber());
        $entry->setProperty($expense->getProperty());
        $entry->setOwner($expense->getProperty()?->getOwner());
        $entry->setExpense($expense);
        $entry->setNotes($expense->getNotes());

        $this->entityManager->persist($entry);
        $this->entityManager->flush();

        // Recalculer les soldes
        $this->recalculateRunningBalances();

        return $entry;
    }

    /**
     * Crée une écriture comptable manuelle
     */
    public function createManualEntry(
        \DateTime $date,
        string $description,
        float $amount,
        string $type,
        string $category,
        ?string $reference = null,
        ?string $notes = null
    ): AccountingEntry {
        $entry = new AccountingEntry();
        $entry->setEntryDate($date);
        $entry->setDescription($description);
        $entry->setAmount((string)$amount);
        $entry->setType($type);
        $entry->setCategory($category);
        $entry->setReference($reference);
        $entry->setNotes($notes);

        $this->entityManager->persist($entry);
        $this->entityManager->flush();

        // Recalculer les soldes
        $this->recalculateRunningBalances();

        return $entry;
    }

    /**
     * Synchronise toutes les écritures comptables avec les paiements et dépenses
     */
    public function synchronizeAllEntries(): void
    {
        // Synchroniser les paiements payés
        $paidPayments = $this->entityManager->getRepository(Payment::class)
            ->findBy(['status' => 'Payé']);

        foreach ($paidPayments as $payment) {
            $this->createEntryFromPayment($payment);
        }

        // Synchroniser les dépenses
        $expenses = $this->entityManager->getRepository(Expense::class)->findAll();

        foreach ($expenses as $expense) {
            $this->createEntryFromExpense($expense);
        }

        // Recalculer les soldes
        $this->recalculateRunningBalances();
    }

    /**
     * Recalcule tous les soldes courants
     */
    public function recalculateRunningBalances(): void
    {
        $this->accountingRepository->recalculateRunningBalances();
    }

    /**
     * Génère la description pour un paiement
     */
    private function getPaymentDescription(Payment $payment): string
    {
        $tenant = $payment->getTenant();
        $property = $payment->getProperty();

        $description = strtoupper($payment->getType());

        if ($payment->getDueDate()) {
            $description .= ' DU ' . $payment->getDueDate()->format('d/m/Y');
        }

        if ($tenant) {
            $description .= ' - ' . strtoupper($tenant->getFullName());
        }

        if ($property) {
            $description .= ' - ' . strtoupper($property->getFullAddress());
        }

        return $description;
    }

    /**
     * Détermine la catégorie comptable pour un paiement
     */
    private function getPaymentCategory(Payment $payment): string
    {
        return match($payment->getType()) {
            'Loyer' => 'LOYER',
            'Charges' => 'CHARGES',
            'Caution' => 'DEPOT_GARANTIE',
            'Frais' => 'FRAIS_GESTION',
            'Pénalité' => 'PENALITE',
            default => 'AUTRE'
        };
    }

    /**
     * Détermine la catégorie comptable pour une dépense
     */
    private function getExpenseCategory(Expense $expense): string
    {
        return match($expense->getCategory()) {
            'Réparations' => 'REPARATIONS',
            'Entretien' => 'ENTRETIEN',
            'Assurance' => 'ASSURANCE',
            'Taxe' => 'TAXE_FONCIERE',
            'Travaux' => 'TRAVAUX',
            default => 'AUTRE'
        };
    }

    /**
     * Génère un rapport comptable pour une période
     */
    public function generateReport(\DateTime $startDate, \DateTime $endDate): array
    {
        $entries = $this->accountingRepository->findByDateRange($startDate, $endDate);

        $totalCredits = 0;
        $totalDebits = 0;
        $categorySummary = [];

        foreach ($entries as $entry) {
            $amount = (float)$entry->getAmount();

            if ($entry->isCredit()) {
                $totalCredits += $amount;
            } else {
                $totalDebits += $amount;
            }

            // Résumé par catégorie
            $category = $entry->getCategory();
            if (!isset($categorySummary[$category])) {
                $categorySummary[$category] = ['credits' => 0, 'debits' => 0];
            }

            if ($entry->isCredit()) {
                $categorySummary[$category]['credits'] += $amount;
            } else {
                $categorySummary[$category]['debits'] += $amount;
            }
        }

        return [
            'period' => [
                'start' => $startDate,
                'end' => $endDate
            ],
            'totals' => [
                'credits' => $totalCredits,
                'debits' => $totalDebits,
                'net' => $totalCredits - $totalDebits
            ],
            'categories' => $categorySummary,
            'entries' => $entries,
            'entry_count' => count($entries)
        ];
    }

    /**
     * Enregistre un paiement anticipé (acompte) en comptabilité
     */
    public function recordAdvancePayment(AdvancePayment $advance): AccountingEntry
    {
        $entry = new AccountingEntry();
        $entry->setType('Crédit');
        $entry->setCategory('Acomptes reçus');
        $entry->setAmount((float) $advance->getAmount());
        $entry->setDescription(sprintf(
            'Acompte reçu - %s - Bail #%d',
            $advance->getTenant()?->getFullName() ?? 'N/A',
            $advance->getLease()?->getId() ?? 0
        ));
        $entry->setEntryDate($advance->getPaidDate());
        $entry->setReference('ACOMPTE-' . $advance->getId());

        $this->entityManager->persist($entry);
        $this->entityManager->flush();

        return $entry;
    }

    /**
     * Enregistre l'utilisation d'un acompte en comptabilité
     */
    public function recordAdvanceUsage(AdvancePayment $advance, float $amountUsed, Payment $payment): AccountingEntry
    {
        $entry = new AccountingEntry();
        $entry->setType('Débit');
        $entry->setCategory('Utilisation acomptes');
        $entry->setAmount($amountUsed);
        $entry->setDescription(sprintf(
            'Utilisation acompte #%d pour paiement #%d - %s',
            $advance->getId(),
            $payment->getId(),
            $payment->getLease()?->getTenant()?->getFullName() ?? 'N/A'
        ));
        $entry->setEntryDate(new \DateTime());
        $entry->setReference('USE-ACOMPTE-' . $advance->getId() . '-' . $payment->getId());
        $entry->setPayment($payment);

        $this->entityManager->persist($entry);
        $this->entityManager->flush();

        return $entry;
    }

    /**
     * Enregistre le remboursement d'un acompte en comptabilité
     */
    public function recordAdvanceRefund(AdvancePayment $advance, float $amount, ?string $reason = null): AccountingEntry
    {
        $entry = new AccountingEntry();
        $entry->setType('Débit');
        $entry->setCategory('Remboursement acomptes');
        $entry->setAmount($amount);
        $entry->setDescription(sprintf(
            'Remboursement acompte #%d - %s%s',
            $advance->getId(),
            $advance->getTenant()?->getFullName() ?? 'N/A',
            $reason ? " - Raison: $reason" : ''
        ));
        $entry->setEntryDate(new \DateTime());
        $entry->setReference('REFUND-ACOMPTE-' . $advance->getId());

        $this->entityManager->persist($entry);
        $this->entityManager->flush();

        return $entry;
    }
}
