<?php

namespace App\Service;

use App\Entity\Lease;
use App\Entity\Payment;
use App\Entity\Tenant;
use App\Repository\LeaseRepository;
use App\Repository\PaymentRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mime\Email;
use Twig\Environment;

class NotificationService
{
    public function __construct(
        private MailerInterface $mailer,
        private Environment $twig,
        private SettingsService $settingsService,
        private EntityManagerInterface $entityManager,
        private ?EmailCustomizationService $emailCustomizationService = null
    ) {
    }

    /**
     * Envoie un email en utilisant un template personnalisé si disponible
     */
    private function sendEmailWithCustomTemplate(
        string $templateCode,
        string $toEmail,
        array $variables,
        string $fallbackTwigTemplate,
        array $fallbackData,
        string $fallbackSubject
    ): void {
        // Essayer d'utiliser le template personnalisé
        if ($this->emailCustomizationService) {
            try {
                $sent = $this->emailCustomizationService->sendCustomEmail($templateCode, $toEmail, $variables);
                if ($sent) {
                    return; // Email envoyé avec succès via template personnalisé
                }
            } catch (\Exception $e) {
                // Continuer avec le template par défaut si erreur
            }
        }

        // Utiliser le template Twig par défaut
        $emailContent = $this->twig->render($fallbackTwigTemplate, $fallbackData);

        $email = (new Email())
            ->from($this->settingsService->get('email_from', 'noreply@mylocca.com'))
            ->to($toEmail)
            ->subject($fallbackSubject)
            ->html($emailContent);

        $this->mailer->send($email);
    }

    /**
     * Envoie les quittances de loyer pour tous les locataires actifs
     */
    public function sendRentReceipts(\DateTime $forMonth = null): array
    {
        if (!$forMonth) {
            $forMonth = new \DateTime('first day of this month');
        }

        $leaseRepository = $this->entityManager->getRepository(Lease::class);
        $paymentRepository = $this->entityManager->getRepository(Payment::class);

        $activeLeases = $leaseRepository->findByStatus('Actif');
        $results = ['sent' => 0, 'failed' => 0, 'errors' => []];

        foreach ($activeLeases as $lease) {
            try {
                // Chercher les paiements payés pour ce mois
                $startDate = clone $forMonth;
                $endDate = (clone $forMonth)->modify('last day of this month');

                $paidPayments = $paymentRepository->createQueryBuilder('p')
                    ->where('p.lease = :lease')
                    ->andWhere('p.status = :status')
                    ->andWhere('p.paidDate BETWEEN :start AND :end')
                    ->setParameter('lease', $lease)
                    ->setParameter('status', 'Payé')
                    ->setParameter('start', $startDate)
                    ->setParameter('end', $endDate)
                    ->getQuery()
                    ->getResult();

                if (!empty($paidPayments)) {
                    $this->sendRentReceiptToTenant($lease, $paidPayments, $forMonth);
                    $results['sent']++;
                }
            } catch (\Exception $e) {
                $results['failed']++;
                $results['errors'][] = "Erreur pour {$lease->getTenant()->getFullName()}: " . $e->getMessage();
            }
        }

        return $results;
    }

    /**
     * Envoie une quittance de loyer à un locataire spécifique
     */
    public function sendRentReceiptToTenant(Lease $lease, array $payments, \DateTime $forMonth): void
    {
        $tenant = $lease->getTenant();
        $property = $lease->getProperty();

        // Calculer le total des paiements
        $totalAmount = 0;
        foreach ($payments as $payment) {
            $totalAmount += (float)$payment->getAmount();
        }

        // Générer le contenu de l'email
        $emailContent = $this->twig->render('emails/rent_receipt.html.twig', [
            'tenant' => $tenant,
            'lease' => $lease,
            'property' => $property,
            'payments' => $payments,
            'total_amount' => $totalAmount,
            'month' => $forMonth,
            'company' => $this->settingsService->getAppSettings(),
        ]);

        // Créer et envoyer l'email
        $email = (new Email())
            ->from($this->settingsService->get('email_from', 'noreply@mylocca.com'))
            ->to($tenant->getEmail())
            ->subject("Quittance de loyer - " . $forMonth->format('F Y'))
            ->html($emailContent);

        $this->mailer->send($email);
    }

    /**
     * Envoie des rappels de paiement pour les loyers en retard
     */
    public function sendPaymentReminders(): array
    {
        $paymentRepository = $this->entityManager->getRepository(Payment::class);
        $overduePayments = $paymentRepository->findOverdue();

        $results = ['sent' => 0, 'failed' => 0, 'errors' => []];

        foreach ($overduePayments as $payment) {
            try {
                $this->sendPaymentReminderToTenant($payment);
                $results['sent']++;
            } catch (\Exception $e) {
                $results['failed']++;
                $results['errors'][] = "Erreur pour {$payment->getTenant()->getFullName()}: " . $e->getMessage();
            }
        }

        return $results;
    }

    /**
     * Envoie un rappel de paiement à un locataire
     */
    public function sendPaymentReminderToTenant(Payment $payment): void
    {
        $tenant = $payment->getTenant();
        $lease = $payment->getLease();
        $property = $lease->getProperty();

        $emailContent = $this->twig->render('emails/payment_reminder.html.twig', [
            'tenant' => $tenant,
            'payment' => $payment,
            'lease' => $lease,
            'property' => $property,
            'days_overdue' => $payment->getDaysOverdue(),
            'company' => $this->settingsService->getAppSettings(),
        ]);

        $email = (new Email())
            ->from($this->settingsService->get('email_from', 'noreply@mylocca.com'))
            ->to($tenant->getEmail())
            ->subject("Rappel de paiement - Loyer en retard")
            ->html($emailContent);

        $this->mailer->send($email);
    }

    /**
     * Envoie des alertes pour les contrats qui expirent bientôt
     */
    public function sendLeaseExpirationAlerts(): array
    {
        $leaseRepository = $this->entityManager->getRepository(Lease::class);
        $expiringLeases = $leaseRepository->findExpiringSoon(60); // 60 jours

        $results = ['sent' => 0, 'failed' => 0, 'errors' => []];

        foreach ($expiringLeases as $lease) {
            try {
                $this->sendLeaseExpirationAlert($lease);
                $results['sent']++;
            } catch (\Exception $e) {
                $results['failed']++;
                $results['errors'][] = "Erreur pour {$lease->getTenant()->getFullName()}: " . $e->getMessage();
            }
        }

        return $results;
    }

    /**
     * Envoie une alerte d'expiration de contrat
     */
    public function sendLeaseExpirationAlert(Lease $lease): void
    {
        $tenant = $lease->getTenant();
        $property = $lease->getProperty();

        $daysUntilExpiration = $lease->getEndDate()->diff(new \DateTime())->days;

        $emailContent = $this->twig->render('emails/lease_expiration.html.twig', [
            'tenant' => $tenant,
            'lease' => $lease,
            'property' => $property,
            'days_until_expiration' => $daysUntilExpiration,
            'company' => $this->settingsService->getAppSettings(),
        ]);

        $email = (new Email())
            ->from($this->settingsService->get('email_from', 'noreply@mylocca.com'))
            ->to($tenant->getEmail())
            ->subject("Expiration de contrat - " . $property->getFullAddress())
            ->html($emailContent);

        $this->mailer->send($email);
    }

    /**
     * Génère automatiquement les loyers du mois suivant
     */
    public function generateNextMonthRents(): array
    {
        $leaseRepository = $this->entityManager->getRepository(Lease::class);
        $activeLeases = $leaseRepository->findByStatus('Actif');

        $nextMonth = new \DateTime('first day of next month');
        $generated = 0;

        foreach ($activeLeases as $lease) {
            $dueDate = clone $nextMonth;
            $dueDate->setDate(
                $nextMonth->format('Y'),
                $nextMonth->format('n'),
                $lease->getRentDueDay() ?? 1
            );

            // ⚠️ VÉRIFICATION IMPORTANTE : Ne pas générer de loyer après la fin du bail
            if ($lease->getEndDate() && $dueDate > $lease->getEndDate()) {
                // La date d'échéance dépasse la fin du bail, on ne génère pas
                continue;
            }

            // Vérifier si le loyer n'existe pas déjà
            $existingPayment = $this->entityManager->getRepository(Payment::class)->findOneBy([
                'lease' => $lease,
                'dueDate' => $dueDate,
                'type' => 'Loyer'
            ]);

            if (!$existingPayment) {
                $payment = new Payment();
                $payment->setLease($lease)
                       ->setDueDate($dueDate)
                       ->setAmount($lease->getMonthlyRent())
                       ->setType('Loyer')
                       ->setStatus('En attente');

                $this->entityManager->persist($payment);
                $generated++;
            }
        }

        if ($generated > 0) {
            $this->entityManager->flush();
        }

        return ['generated' => $generated];
    }

    /**
     * Teste l'envoi d'email
     */
    public function testEmailConfiguration(string $testEmail): bool
    {
        try {
            $email = (new Email())
                ->from($this->settingsService->get('email_from', 'noreply@mylocca.com'))
                ->to($testEmail)
                ->subject('Test de configuration email - MYLOCCA')
                ->html($this->twig->render('emails/test.html.twig', [
                    'company' => $this->settingsService->getAppSettings(),
                    'test_date' => new \DateTime(),
                ]));

            $this->mailer->send($email);
            return true;
        } catch (\Exception $e) {
            return false;
        }
    }
}
