<?php

namespace App\Service;

/**
 * Service de gestion des paramètres de paiement
 * Centralise l'accès aux paramètres configurés dans l'interface admin
 */
class PaymentSettingsService
{
    public function __construct(
        private SettingsService $settingsService
    ) {
    }

    /**
     * Récupère le jour d'échéance par défaut pour les loyers
     */
    public function getDefaultRentDueDay(): int
    {
        return (int) $this->settingsService->get('default_rent_due_day', 1);
    }

    /**
     * Récupère le taux de pénalité de retard (en pourcentage)
     */
    public function getLateFeeRate(): float
    {
        return (float) $this->settingsService->get('late_fee_rate', 5.0);
    }

    /**
     * Calcule le montant des pénalités de retard
     * 
     * @param float $amount Montant du loyer
     * @param int $daysLate Nombre de jours de retard
     * @return float Montant de la pénalité
     */
    public function calculateLateFee(float $amount, int $daysLate): float
    {
        if ($daysLate <= 0) {
            return 0;
        }

        $rate = $this->getLateFeeRate();
        // Formule : (montant × taux%) × (jours / 30)
        return ($amount * $rate / 100) * ($daysLate / 30);
    }

    /**
     * Vérifie si la génération automatique des loyers est activée
     */
    public function isAutoGenerateRentEnabled(): bool
    {
        return (bool) $this->settingsService->get('auto_generate_rent', true);
    }

    /**
     * Récupère le nombre de jours après l'échéance avant d'envoyer un rappel
     */
    public function getPaymentReminderDays(): int
    {
        return (int) $this->settingsService->get('payment_reminder_days', 7);
    }

    /**
     * Vérifie si les paiements partiels sont autorisés
     */
    public function isPartialPaymentAllowed(): bool
    {
        return (bool) $this->settingsService->get('allow_partial_payments', false);
    }

    /**
     * Récupère le montant minimum pour un paiement partiel
     */
    public function getMinimumPaymentAmount(): float
    {
        return (float) $this->settingsService->get('minimum_payment_amount', 10);
    }

    /**
     * Vérifie si les paiements anticipés (acomptes) sont autorisés
     */
    public function isAdvancePaymentAllowed(): bool
    {
        return (bool) $this->settingsService->get('allow_advance_payments', true);
    }

    /**
     * Récupère le montant minimum pour un acompte
     */
    public function getMinimumAdvanceAmount(): float
    {
        return (float) $this->settingsService->get('minimum_advance_amount', 50);
    }

    /**
     * Valide un montant de paiement selon les règles configurées
     * 
     * @param float $amount Montant du paiement
     * @param float $dueAmount Montant dû
     * @return array Liste des erreurs de validation (vide si valide)
     */
    public function validatePaymentAmount(float $amount, float $dueAmount): array
    {
        $errors = [];

        // Vérifier que le montant est positif
        if ($amount <= 0) {
            $errors[] = "Le montant doit être positif";
            return $errors;
        }

        // Vérifier que le montant ne dépasse pas le montant dû
        if ($amount > $dueAmount) {
            $errors[] = sprintf(
                "Le montant ne peut pas dépasser le montant dû (%s)",
                number_format($dueAmount, 2)
            );
        }

        // Si c'est un paiement partiel
        if ($amount < $dueAmount) {
            // Vérifier si les paiements partiels sont autorisés
            if (!$this->isPartialPaymentAllowed()) {
                $errors[] = "Les paiements partiels ne sont pas autorisés. Veuillez payer le montant complet.";
            }

            // Vérifier le montant minimum
            $minimumAmount = $this->getMinimumPaymentAmount();
            if ($amount < $minimumAmount) {
                $errors[] = sprintf(
                    "Le montant minimum pour un paiement partiel est de %s",
                    number_format($minimumAmount, 2)
                );
            }
        }

        return $errors;
    }

    /**
     * Récupère tous les paramètres de paiement sous forme de tableau
     */
    public function getAllSettings(): array
    {
        return [
            'default_rent_due_day' => $this->getDefaultRentDueDay(),
            'late_fee_rate' => $this->getLateFeeRate(),
            'auto_generate_rent' => $this->isAutoGenerateRentEnabled(),
            'payment_reminder_days' => $this->getPaymentReminderDays(),
            'allow_partial_payments' => $this->isPartialPaymentAllowed(),
            'minimum_payment_amount' => $this->getMinimumPaymentAmount(),
            'allow_advance_payments' => $this->isAdvancePaymentAllowed(),
            'minimum_advance_amount' => $this->getMinimumAdvanceAmount(),
        ];
    }

    /**
     * Détermine si un paiement est en retard et calcule le nombre de jours
     * 
     * @param \DateTimeInterface $dueDate Date d'échéance
     * @return array ['is_late' => bool, 'days_late' => int]
     */
    public function getLateFeeInfo(\DateTimeInterface $dueDate): array
    {
        $now = new \DateTime();
        $interval = $now->diff($dueDate);
        $daysLate = $interval->invert ? $interval->days : 0;

        return [
            'is_late' => $daysLate > 0,
            'days_late' => $daysLate,
        ];
    }

    /**
     * Vérifie si un rappel de paiement doit être envoyé
     * 
     * @param \DateTimeInterface $dueDate Date d'échéance
     * @return bool True si un rappel doit être envoyé
     */
    public function shouldSendReminder(\DateTimeInterface $dueDate): bool
    {
        $reminderDays = $this->getPaymentReminderDays();
        $now = new \DateTime();
        $interval = $now->diff($dueDate);
        
        // Le paiement est en retard depuis au moins X jours
        return $interval->invert && $interval->days >= $reminderDays;
    }
}

