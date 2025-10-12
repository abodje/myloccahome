<?php

namespace App\Service;

use App\Entity\Lease;
use App\Entity\Payment;
use Dompdf\Dompdf;
use Dompdf\Options;
use Twig\Environment;

class PdfService
{
    public function __construct(
        private Environment $twig,
        private SettingsService $settingsService,
        private CurrencyService $currencyService,
        private ContractConfigService $contractConfigService
    ) {
    }

    /**
     * Génère un PDF à partir d'un template HTML
     */
    private function generatePdf(string $html, string $filename, bool $download = true): string
    {
        $options = new Options();
        $options->set('defaultFont', 'DejaVu Sans');
        $options->set('isRemoteEnabled', true);
        $options->set('isHtml5ParserEnabled', true);

        $dompdf = new Dompdf($options);
        $dompdf->loadHtml($html);
        $dompdf->setPaper('A4', 'portrait');
        $dompdf->render();

        if ($download) {
            $dompdf->stream($filename, ['Attachment' => true]);
        }

        return $dompdf->output();
    }

    /**
     * Génère un contrat de bail en PDF
     */
    public function generateLeaseContract(Lease $lease, bool $download = true): string
    {
        // Récupérer la configuration du contrat
        $contractConfig = $this->contractConfigService->getContractConfig();
        
        $html = $this->twig->render('pdf/lease_contract.html.twig', array_merge([
            'lease' => $lease,
            'property' => $lease->getProperty(),
            'tenant' => $lease->getTenant(),
            'owner' => $lease->getProperty()->getOwner(),
            'company' => $this->settingsService->getAppSettings(),
            'currency' => $this->currencyService->getActiveCurrency(),
            'generated_at' => new \DateTime(),
        ], $contractConfig));

        $filename = sprintf(
            'Contrat_Bail_%s_%s.pdf',
            $lease->getProperty()->getId(),
            $lease->getStartDate()->format('Y-m-d')
        );

        return $this->generatePdf($html, $filename, $download);
    }

    /**
     * Génère un reçu de paiement en PDF
     */
    public function generatePaymentReceipt(Payment $payment, bool $download = true): string
    {
        $html = $this->twig->render('pdf/payment_receipt.html.twig', [
            'payment' => $payment,
            'lease' => $payment->getLease(),
            'property' => $payment->getLease()->getProperty(),
            'tenant' => $payment->getLease()->getTenant(),
            'company' => $this->settingsService->getAppSettings(),
            'currency' => $this->currencyService->getActiveCurrency(),
            'generated_at' => new \DateTime(),
        ]);

        $filename = sprintf(
            'Recu_Paiement_%s_%s.pdf',
            str_pad($payment->getId(), 6, '0', STR_PAD_LEFT),
            $payment->getPaidDate() ? $payment->getPaidDate()->format('Y-m-d') : date('Y-m-d')
        );

        return $this->generatePdf($html, $filename, $download);
    }

    /**
     * Génère un échéancier de paiement en PDF
     */
    public function generatePaymentSchedule(Lease $lease, int $months = 12, bool $download = true): string
    {
        // Générer les échéances futures
        $schedule = [];
        $startDate = new \DateTime();
        $startDate->setDate($startDate->format('Y'), $startDate->format('m'), $lease->getRentDueDay() ?? 1);

        for ($i = 0; $i < $months; $i++) {
            $dueDate = clone $startDate;
            $dueDate->modify("+{$i} months");

            $schedule[] = [
                'month' => $dueDate->format('F Y'),
                'due_date' => $dueDate,
                'rent' => $lease->getMonthlyRent(),
                'charges' => $lease->getCharges() ?? 0,
                'total' => (float)$lease->getMonthlyRent() + (float)($lease->getCharges() ?? 0),
            ];
        }

        $html = $this->twig->render('pdf/payment_schedule.html.twig', [
            'lease' => $lease,
            'property' => $lease->getProperty(),
            'tenant' => $lease->getTenant(),
            'schedule' => $schedule,
            'months' => $months,
            'company' => $this->settingsService->getAppSettings(),
            'currency' => $this->currencyService->getActiveCurrency(),
            'generated_at' => new \DateTime(),
        ]);

        $filename = sprintf(
            'Echeancier_%s_%s.pdf',
            $lease->getProperty()->getId(),
            date('Y-m-d')
        );

        return $this->generatePdf($html, $filename, $download);
    }

    /**
     * Génère une quittance de loyer en PDF (mensuelle)
     */
    public function generateRentQuittance(array $payments, Lease $lease, \DateTime $month, bool $download = true): string
    {
        $totalAmount = 0;
        foreach ($payments as $payment) {
            $totalAmount += (float)$payment->getAmount();
        }

        $html = $this->twig->render('pdf/rent_quittance.html.twig', [
            'payments' => $payments,
            'lease' => $lease,
            'property' => $lease->getProperty(),
            'tenant' => $lease->getTenant(),
            'month' => $month,
            'total_amount' => $totalAmount,
            'company' => $this->settingsService->getAppSettings(),
            'currency' => $this->currencyService->getActiveCurrency(),
            'generated_at' => new \DateTime(),
        ]);

        $filename = sprintf(
            'Quittance_Loyer_%s_%s.pdf',
            $lease->getTenant()->getLastName(),
            $month->format('Y-m')
        );

        return $this->generatePdf($html, $filename, $download);
    }

    /**
     * Génère un état des lieux en PDF
     */
    public function generateInventory(\App\Entity\Inventory $inventory, bool $download = true): string
    {
        $html = $this->twig->render('pdf/inventory.html.twig', [
            'inventory' => $inventory,
            'property' => $inventory->getProperty(),
            'lease' => $inventory->getLease(),
            'tenant' => $inventory->getLease() ? $inventory->getLease()->getTenant() : null,
            'items' => $inventory->getItems(),
            'company' => $this->settingsService->getAppSettings(),
            'generated_at' => new \DateTime(),
        ]);

        $filename = sprintf(
            'Etat_des_lieux_%s_%s.pdf',
            $inventory->getType(),
            date('Y-m-d')
        );

        return $this->generatePdf($html, $filename, $download);
    }

    /**
     * Génère un rapport de synthèse pour un propriétaire
     */
    public function generateOwnerReport(\App\Entity\Owner $owner, \DateTime $startDate, \DateTime $endDate, bool $download = true): string
    {
        // Récupérer les données nécessaires
        $properties = $owner->getProperties();
        $totalRevenue = 0;
        $totalExpenses = 0;

        $html = $this->twig->render('pdf/owner_report.html.twig', [
            'owner' => $owner,
            'properties' => $properties,
            'start_date' => $startDate,
            'end_date' => $endDate,
            'total_revenue' => $totalRevenue,
            'total_expenses' => $totalExpenses,
            'company' => $this->settingsService->getAppSettings(),
            'currency' => $this->currencyService->getActiveCurrency(),
            'generated_at' => new \DateTime(),
        ]);

        $filename = sprintf(
            'Rapport_Proprietaire_%s_%s.pdf',
            $owner->getId(),
            date('Y-m-d')
        );

        return $this->generatePdf($html, $filename, $download);
    }
}

