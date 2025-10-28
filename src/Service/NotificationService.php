<?php

namespace App\Service;

use Symfony\Component\Notifier\TexterInterface;
use Symfony\Component\Notifier\Message\SmsMessage;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mime\Email;
use Psr\Log\LoggerInterface;
use Doctrine\ORM\EntityManagerInterface;
use App\Entity\Payment;
use App\Entity\Document;

/**
 * Service de notification pour l'envoi de SMS via Orange
 */
class NotificationService
{
    public function __construct(
        private TexterInterface $texter,
        private SettingsService $settingsService,
        private LoggerInterface $logger,
        private MailerInterface $mailer,
        private EntityManagerInterface $entityManager,
        private SmtpConfigurationService $smtpConfigurationService,
        private string $documentsDirectory
    ) {
    }

    /**
     * Envoie un SMS de notification
     */
    public function sendSmsNotification(string $phoneNumber, string $message, string $senderName = null): bool
    {
        try {
            // Vérifier si Orange SMS est configuré et activé
            if (!$this->isOrangeSmsConfigured()) {
                $this->logger->warning('Orange SMS non configuré, SMS non envoyé', [
                    'phone' => $phoneNumber,
                    'message' => $message
                ]);
                return false;
            }

            // Nettoyer et formater le numéro de téléphone
            $formattedPhone = $this->formatPhoneNumber($phoneNumber);

            // Utiliser le sender name configuré si non spécifié
            if (!$senderName) {
                $senderName = $this->settingsService->get('orange_sms_sender_name', 'LOKAPRO');
            }

            $sms = new SmsMessage(
                $formattedPhone,
                $message,
                '' // Le numéro d'envoi sera défini par le transport Orange SMS
            );

            // Envoyer le SMS via le transport Orange SMS
            $sentMessage = $this->texter->send($sms, 'orange-sms');

            // Vérifier si l'envoi a réussi
            if (!$sentMessage) {
                throw new \Exception('L\'envoi du SMS a échoué - aucune réponse du service');
            }

            $messageId = $sentMessage->getMessageId() ?? 'unknown';

            $this->logger->info('SMS envoyé avec succès', [
                'phone' => $formattedPhone,
                'message_id' => $messageId,
                'sender_name' => $senderName
            ]);

            return true;

        } catch (\Exception $e) {
            $this->logger->error('Erreur lors de l\'envoi du SMS', [
                'phone' => $phoneNumber,
                'message' => $message,
                'error' => $e->getMessage()
            ]);

            return false;
        }
    }

    /**
     * Envoie un SMS de rappel de paiement
     */
    public function sendPaymentReminder(string $phoneNumber, string $tenantName, float $amount, string $dueDate): bool
    {
        $message = sprintf(
            'Bonjour %s, rappel : votre loyer de %.0f FCFA est attendu pour le %s. LOKAPRO',
            $tenantName,
            $amount,
            $dueDate
        );

        return $this->sendSmsNotification($phoneNumber, $message);
    }

    /**
     * Envoie un SMS de confirmation de paiement
     */
    public function sendPaymentConfirmation(string $phoneNumber, string $tenantName, float $amount): bool
    {
        $message = sprintf(
            'Merci %s ! Paiement de %.0f FCFA reçu et confirmé. LOKAPRO',
            $tenantName,
            $amount
        );

        return $this->sendSmsNotification($phoneNumber, $message);
    }

    /**
     * Envoie un SMS de maintenance assignée
     */
    public function sendMaintenanceAssignment(string $phoneNumber, string $maintenanceType, string $propertyAddress): bool
    {
        $message = sprintf(
            'Maintenance %s assignée pour %s. Nous vous contacterons bientôt. LOKAPRO',
            $maintenanceType,
            $propertyAddress
        );

        return $this->sendSmsNotification($phoneNumber, $message);
    }

    /**
     * Vérifie si Orange SMS est configuré et activé
     */
    private function isOrangeSmsConfigured(): bool
    {
        $clientId = $this->settingsService->get('orange_sms_client_id', '');
        $clientSecret = $this->settingsService->get('orange_sms_client_secret', '');
        $enabled = $this->settingsService->get('orange_sms_enabled', false);

        return !empty($clientId) && !empty($clientSecret) && $enabled;
    }

    /**
     * Formate un numéro de téléphone pour Orange SMS
     */
    private function formatPhoneNumber(string $phoneNumber): string
    {
        // Nettoyer le numéro
        $cleanPhone = preg_replace('/[^0-9]/', '', $phoneNumber);

        // Si le numéro commence par 0, le remplacer par 225
        if (substr($cleanPhone, 0, 1) === '0') {
            $cleanPhone = '225' . substr($cleanPhone, 1);
        }

        // Si le numéro ne commence pas par 225, l'ajouter
        if (substr($cleanPhone, 0, 3) !== '225') {
            $cleanPhone = '225' . $cleanPhone;
        }

        return '+' . $cleanPhone;
    }

    /**
     * Récupère l'adresse email de l'expéditeur depuis les paramètres email
     */
    private function getFromEmail(): string
    {
        // Utiliser l'adresse email configurée dans les paramètres email
        return $this->settingsService->get('email_from_address', 'info@app.lokapro.tech');
    }

    /**
     * Récupère le nom de l'expéditeur depuis les paramètres email
     */
    private function getFromName(): string
    {
        return $this->settingsService->get('email_sender_name', 'LOKAPRO');
    }

    /**
     * Envoie les quittances de loyer aux locataires
     */
    public function sendRentReceipts(?\DateTime $forMonth = null): array
    {
        if (!$forMonth) {
            $forMonth = new \DateTime('first day of last month');
        }

        $startDate = new \DateTime($forMonth->format('Y-m-01 00:00:00'));
        $endDate = new \DateTime($forMonth->format('Y-m-t 23:59:59'));

        // Trouver tous les paiements payés pour le mois spécifié
        $payments = $this->entityManager->getRepository(Payment::class)
            ->createQueryBuilder('p')
            ->join('p.lease', 'l')
            ->join('l.tenant', 't')
            ->where('p.status = :status')
            ->andWhere('p.paidDate BETWEEN :startDate AND :endDate')
            ->setParameter('status', 'Payé')
            ->setParameter('startDate', $startDate)
            ->setParameter('endDate', $endDate)
            ->getQuery()
            ->getResult();

        $sent = 0;
        $failed = 0;
        $errors = [];

        foreach ($payments as $payment) {
            try {
                // Vérifier que l'EntityManager est toujours ouvert
                if (!$this->entityManager->isOpen()) {
                    $errors[] = "Paiement #{$payment->getId()}: EntityManager fermé";
                    $failed++;
                    break;
                }

                $tenant = $payment->getLease()->getTenant();
                if (!$tenant || !$tenant->getEmail()) {
                    $errors[] = "Paiement #{$payment->getId()}: Locataire sans email valide";
                    $failed++;
                    continue;
                }

                // Chercher la quittance générée pour ce paiement
                // Utiliser la même logique que RentReceiptService pour générer le nom de fichier
                $tenant = $payment->getLease()->getTenant();
                $fileName = sprintf(
                    'quittance_%s_%s.pdf',
                    $tenant->getLastName(),
                    $payment->getDueDate()->format('Y_m')
                );

                $receipt = $this->entityManager->getRepository(Document::class)
                    ->findOneBy([
                        'type' => 'Quittance de loyer',
                        'fileName' => $fileName
                    ]);

                if (!$receipt) {
                    $errors[] = "Paiement #{$payment->getId()}: Aucune quittance trouvée";
                    $failed++;
                    continue;
                }

                // Envoyer l'email avec la quittance en pièce jointe
                $this->sendReceiptEmail($tenant->getEmail(), $tenant->getFirstName(), $receipt, $payment);
                $sent++;

                // Vérifier l'état de l'EntityManager après chaque envoi
                if (!$this->entityManager->isOpen()) {
                    $errors[] = "Paiement #{$payment->getId()}: EntityManager fermé après envoi";
                    $failed++;
                    break;
                }

            } catch (\Exception $e) {
                $errors[] = "Paiement #{$payment->getId()}: " . $e->getMessage();
                $failed++;
                $this->logger->error('Erreur envoi quittance', [
                    'payment_id' => $payment->getId(),
                    'error' => $e->getMessage(),
                    'entity_manager_open' => $this->entityManager->isOpen()
                ]);

                // Si l'EntityManager est fermé, on ne peut pas continuer
                if (!$this->entityManager->isOpen()) {
                    $errors[] = "EntityManager fermé - arrêt du traitement";
                    break;
                }
            }
        }

        return [
            'sent' => $sent,
            'failed' => $failed,
            'errors' => $errors
        ];
    }

    /**
     * Envoie un email de rappel de paiement
     */
    public function sendPaymentReminders(): array
    {
        // Trouver les paiements en retard
        $overduePayments = $this->entityManager->getRepository(Payment::class)
            ->createQueryBuilder('p')
            ->join('p.lease', 'l')
            ->join('l.tenant', 't')
            ->where('p.status = :status')
            ->andWhere('p.dueDate < :today')
            ->setParameter('status', 'En attente')
            ->setParameter('today', new \DateTime())
            ->getQuery()
            ->getResult();

        $sent = 0;
        $failed = 0;
        $errors = [];

        foreach ($overduePayments as $payment) {
            try {
                $tenant = $payment->getLease()->getTenant();
                if (!$tenant || !$tenant->getEmail()) {
                    continue;
                }

                $this->sendPaymentReminderEmail($tenant->getEmail(), $tenant->getFirstName(), $payment);
                $sent++;

            } catch (\Exception $e) {
                $errors[] = "Paiement #{$payment->getId()}: " . $e->getMessage();
                $failed++;
            }
        }

        return [
            'sent' => $sent,
            'failed' => $failed,
            'errors' => $errors
        ];
    }

    /**
     * Envoie des alertes d'expiration de contrat
     */
    public function sendLeaseExpirationAlerts(): array
    {
        // Trouver les contrats qui expirent bientôt
        $expiringLeases = $this->entityManager->getRepository(\App\Entity\Lease::class)
            ->createQueryBuilder('l')
            ->join('l.tenant', 't')
            ->where('l.endDate BETWEEN :startDate AND :endDate')
            ->andWhere('l.status = :status')
            ->setParameter('startDate', new \DateTime())
            ->setParameter('endDate', (new \DateTime())->modify('+60 days'))
            ->setParameter('status', 'Actif')
            ->getQuery()
            ->getResult();

        $sent = 0;
        $failed = 0;
        $errors = [];

        foreach ($expiringLeases as $lease) {
            try {
                $tenant = $lease->getTenant();
                if (!$tenant || !$tenant->getEmail()) {
                    continue;
                }

                $this->sendLeaseExpirationEmail($tenant->getEmail(), $tenant->getFirstName(), $lease);
                $sent++;

            } catch (\Exception $e) {
                $errors[] = "Contrat #{$lease->getId()}: " . $e->getMessage();
                $failed++;
            }
        }

        return [
            'sent' => $sent,
            'failed' => $failed,
            'errors' => $errors
        ];
    }

    /**
     * Génère les paiements de loyer pour le mois prochain
     */
    public function generateNextMonthRents(): array
    {
        $nextMonth = new \DateTime('first day of next month');
        $generated = 0;
        $skipped = 0;
        $errors = [];

        // Trouver tous les contrats actifs
        $activeLeases = $this->entityManager->getRepository(\App\Entity\Lease::class)
            ->findBy(['status' => 'Actif']);

        foreach ($activeLeases as $lease) {
            try {
                // Calculer la date d'échéance pour le mois prochain
                $dueDate = clone $nextMonth;
                $dueDate->setDate(
                    $nextMonth->format('Y'),
                    $nextMonth->format('n'),
                    $lease->getRentDueDay() ?? 1
                );

                // Vérifier que la date n'excède pas la fin du bail
                if ($lease->getEndDate() && $dueDate > $lease->getEndDate()) {
                    $skipped++;
                    continue;
                }

                // Vérifier si le loyer existe déjà
                $existingPayment = $this->entityManager->getRepository(\App\Entity\Payment::class)
                    ->findOneBy([
                        'lease' => $lease,
                        'dueDate' => $dueDate,
                        'type' => 'Loyer'
                    ]);

                if (!$existingPayment) {
                    $payment = new \App\Entity\Payment();
                    $payment->setLease($lease);
                    $payment->setDueDate($dueDate);
                    $payment->setAmount($lease->getMonthlyRent());
                    $payment->setType('Loyer');
                    $payment->setStatus('En attente');
                    $payment->setOrganization($lease->getOrganization());
                    $payment->setCompany($lease->getCompany());

                    $this->entityManager->persist($payment);

                    // Créer automatiquement une écriture comptable pour le loyer généré
                    $this->createAccountingEntryForGeneratedRent($payment);

                    $generated++;
                } else {
                    $skipped++;
                }

            } catch (\Exception $e) {
                $errors[] = "Contrat #{$lease->getId()}: " . $e->getMessage();
                $skipped++;
            }
        }

        // Sauvegarder tous les nouveaux paiements
        if ($generated > 0) {
            $this->entityManager->flush();
        }

        return [
            'generated' => $generated,
            'skipped' => $skipped,
            'errors' => $errors
        ];
    }

    /**
     * Teste la configuration email
     */
    public function testEmailConfiguration(string $testEmail): bool
    {
        try {
            $email = (new Email())
                ->from($this->getFromEmail())
                ->to($testEmail)
                ->subject('Test de configuration email - ' . $this->getFromName())
                ->html('<p>Ceci est un email de test pour vérifier la configuration SMTP de ' . $this->getFromName() . '.</p>');

            // Utiliser le mailer SMTP personnalisé
            $customMailer = $this->smtpConfigurationService->createCustomMailer();
            $customMailer->send($email);

            $this->logger->info('Email de test envoyé avec succès', ['to' => $testEmail]);
            return true;
        } catch (\Exception $e) {
            $this->logger->error('Erreur test email', ['error' => $e->getMessage(), 'to' => $testEmail]);
            return false;
        }
    }

    /**
     * Envoie un email avec quittance en pièce jointe
     */
    private function sendReceiptEmail(string $emailAddress, string $tenantName, Document $receipt, Payment $payment): void
    {
        $email = (new Email())
            ->from($this->getFromEmail())
            ->to($emailAddress)
            ->subject('Votre quittance de loyer - LOKAPRO')
            ->html($this->getReceiptEmailTemplate($tenantName, $payment));

        // Ajouter la quittance en pièce jointe si le fichier existe
        $filePath = $this->getDocumentFilePath($receipt);

        if ($filePath) {
            if (file_exists($filePath)) {
                $email->attachFromPath($filePath, 'quittance-' . $payment->getPaidDate()->format('Y-m') . '.pdf');
                $this->logger->info('📎 Pièce jointe ajoutée à l\'email', [
                    'file_path' => $filePath,
                    'file_size' => filesize($filePath),
                    'email_to' => $emailAddress
                ]);
            } else {
                $this->logger->warning('⚠️ Fichier de quittance introuvable', [
                    'file_path' => $filePath,
                    'document_id' => $receipt->getId(),
                    'document_filename' => $receipt->getFileName(),
                    'email_to' => $emailAddress
                ]);
            }
        } else {
            $this->logger->warning('⚠️ Chemin de fichier de quittance vide', [
                'document_id' => $receipt->getId(),
                'document_filename' => $receipt->getFileName(),
                'email_to' => $emailAddress
            ]);
        }

        // Utiliser le mailer SMTP personnalisé
        $customMailer = $this->smtpConfigurationService->createCustomMailer();
        $customMailer->send($email);
    }

    /**
     * Envoie un email de rappel de paiement
     */
    private function sendPaymentReminderEmail(string $emailAddress, string $tenantName, Payment $payment): void
    {
        $email = (new Email())
            ->from($this->getFromEmail())
            ->to($emailAddress)
            ->subject('Rappel de paiement - LOKAPRO')
            ->html($this->getPaymentReminderEmailTemplate($tenantName, $payment));

        // Utiliser le mailer SMTP personnalisé
        $customMailer = $this->smtpConfigurationService->createCustomMailer();
        $customMailer->send($email);
    }

    /**
     * Envoie un email d'alerte d'expiration de contrat
     */
    private function sendLeaseExpirationEmail(string $emailAddress, string $tenantName, \App\Entity\Lease $lease): void
    {
        $email = (new Email())
            ->from($this->getFromEmail())
            ->to($emailAddress)
            ->subject('Alerte: Expiration de votre contrat - LOKAPRO')
            ->html($this->getLeaseExpirationEmailTemplate($tenantName, $lease));

        // Utiliser le mailer SMTP personnalisé
        $customMailer = $this->smtpConfigurationService->createCustomMailer();
        $customMailer->send($email);
    }

    /**
     * Template email pour quittance
     */
    private function getReceiptEmailTemplate(string $tenantName, Payment $payment): string
    {
        // Récupérer le template personnalisé
        $customTemplate = $this->settingsService->get('email_template_receipt');

        if ($customTemplate) {
            // Remplacer les variables dans le template
            $data = [
                'locataire_nom' => $tenantName,
                'locataire_prenom' => explode(' ', $tenantName)[0] ?? $tenantName,
                'loyer_montant' => number_format($payment->getAmount(), 0, ',', ' ') . ' ' . $this->settingsService->get('email_currency', 'FCFA'),
                'mois' => $payment->getPaidDate()->format('F'),
                'annee' => $payment->getPaidDate()->format('Y'),
                'date_echeance' => $payment->getDueDate() ? $payment->getDueDate()->format($this->settingsService->get('email_date_format', 'd/m/Y')) : '',
                'date_aujourdhui' => date($this->settingsService->get('email_date_format', 'd/m/Y')),
                'societe_nom' => $this->getFromName(),
                'societe_contact' => $this->getFromEmail(),
                'email_signature' => $this->settingsService->get('email_signature', 'LOKAPRO - Votre partenaire immobilier'),
            ];

            $template = $customTemplate;
            foreach ($data as $key => $value) {
                $template = str_replace('{{ ' . $key . ' }}', $value, $template);
            }

            return $template;
        }

        // Template par défaut si aucun template personnalisé
        return "
            <h2>Bonjour {$tenantName},</h2>
            <p>Veuillez trouver ci-joint votre quittance de loyer pour le mois de " . $payment->getPaidDate()->format('F Y') . ".</p>
            <p>Montant payé : " . number_format($payment->getAmount(), 0, ',', ' ') . " " . $this->settingsService->get('email_currency', 'FCFA') . "</p>
            <p>Merci pour votre confiance.</p>
            <p>" . $this->getFromName() . "</p>
        ";
    }

    /**
     * Template email pour rappel de paiement
     */
    private function getPaymentReminderEmailTemplate(string $tenantName, Payment $payment): string
    {
        $daysOverdue = $payment->getDueDate()->diff(new \DateTime())->days;

        return "
            <h2>Bonjour {$tenantName},</h2>
            <p>Nous vous rappelons que votre loyer de " . number_format($payment->getAmount(), 0, ',', ' ') . " FCFA était attendu le " . $payment->getDueDate()->format('d/m/Y') . ".</p>
            <p>Le paiement est en retard de {$daysOverdue} jour(s).</p>
            <p>Merci de régulariser votre situation dans les plus brefs délais.</p>
            <p>L'équipe LOKAPRO</p>
        ";
    }

    /**
     * Template email pour alerte d'expiration de contrat
     */
    private function getLeaseExpirationEmailTemplate(string $tenantName, \App\Entity\Lease $lease): string
    {
        $daysUntilExpiration = $lease->getEndDate()->diff(new \DateTime())->days;
        $propertyAddress = $lease->getProperty() ? $lease->getProperty()->getAddress() : 'N/A';

        return "
            <h2>Bonjour {$tenantName},</h2>
            <p>Nous vous informons que votre contrat de location pour <strong>{$propertyAddress}</strong> expire le " . $lease->getEndDate()->format('d/m/Y') . ".</p>
            <p>Il reste {$daysUntilExpiration} jour(s) avant l'expiration de votre contrat.</p>
            <p>Si vous souhaitez renouveler votre contrat ou si vous avez des questions, n'hésitez pas à nous contacter.</p>
            <p>L'équipe LOKAPRO</p>
        ";
    }

    /**
     * Obtient le chemin du fichier de document
     */
    private function getDocumentFilePath(Document $document): ?string
    {
        if (!$document->getFileName()) {
            return null;
        }

        // Utiliser le chemin absolu du répertoire des documents
        return $this->documentsDirectory . '/' . $document->getFileName();
    }

    /**
     * Crée une écriture comptable pour un loyer généré automatiquement
     */
    private function createAccountingEntryForGeneratedRent(Payment $payment): void
    {
        // Vérifier si une écriture comptable existe déjà pour ce paiement
        $existingEntry = $this->entityManager->getRepository(\App\Entity\AccountingEntry::class)
            ->findOneBy(['payment' => $payment]);

        if ($existingEntry) {
            return; // Écriture déjà existante
        }

        // Récupérer la configuration comptable pour les loyers attendus
        $configService = new \App\Service\AccountingConfigService(
            $this->entityManager->getRepository(\App\Entity\AccountingConfiguration::class),
            $this->entityManager
        );

        $config = $configService->getConfigurationForOperation('LOYER_ATTENDU');

        if (!$config) {
            // Configuration par défaut si aucune configuration trouvée
            $config = $this->createDefaultLoyerAttenduConfig();
        }

        // Créer une nouvelle écriture comptable pour le loyer généré
        $entry = new \App\Entity\AccountingEntry();
        $entry->setEntryDate($payment->getDueDate());
        $entry->setDescription($config->getDescription() . ' - ' . $payment->getLease()->getProperty()->getFullAddress());
        $entry->setAmount($payment->getAmount());
        $entry->setType($config->getEntryType());
        $entry->setCategory($config->getCategory());
        $entry->setReference($config->getReference() . $payment->getId());
        $entry->setProperty($payment->getLease()->getProperty());
        $entry->setOwner($payment->getLease()->getProperty()?->getOwner());
        $entry->setPayment($payment);
        $entry->setOrganization($payment->getOrganization());
        $entry->setCompany($payment->getCompany());
        $entry->setNotes('Généré automatiquement selon la configuration comptable');

        $this->entityManager->persist($entry);
    }

    /**
     * Crée une configuration par défaut pour les loyers attendus si aucune n'existe
     */
    private function createDefaultLoyerAttenduConfig(): \App\Entity\AccountingConfiguration
    {
        $config = new \App\Entity\AccountingConfiguration();
        $config->setOperationType('LOYER_ATTENDU')
               ->setAccountNumber('411000')
               ->setAccountLabel('Clients - Loyers attendus')
               ->setEntryType('CREDIT')
               ->setDescription('Loyer généré automatiquement')
               ->setReference('LOYER-GEN-')
               ->setCategory('LOYER')
               ->setNotes('Configuration par défaut')
               ->setIsActive(true);

        $this->entityManager->persist($config);
        $this->entityManager->flush();

        return $config;
    }

    /**
     * Envoie un email de bienvenue à un nouveau locataire
     */
    public function sendWelcomeEmail(string $emailAddress, string $tenantName, \App\Entity\Property $property, ?\App\Entity\Lease $lease = null): bool
    {
        try {
            $email = (new Email())
                ->from($this->getFromEmail())
                ->to($emailAddress)
                ->subject('Bienvenue chez ' . $this->getFromName())
                ->html($this->getWelcomeEmailTemplate($tenantName, $property, $lease));

            // Utiliser le mailer SMTP personnalisé
            $customMailer = $this->smtpConfigurationService->createCustomMailer();
            $customMailer->send($email);

            $this->logger->info('Email de bienvenue envoyé avec succès', ['to' => $emailAddress]);
            return true;
        } catch (\Exception $e) {
            $this->logger->error('Erreur envoi email de bienvenue', ['error' => $e->getMessage(), 'to' => $emailAddress]);
            return false;
        }
    }

    /**
     * Template email de bienvenue
     */
    private function getWelcomeEmailTemplate(string $tenantName, \App\Entity\Property $property, ?\App\Entity\Lease $lease): string
    {
        // Récupérer le template personnalisé
        $customTemplate = $this->settingsService->get('email_template_welcome');

        if ($customTemplate) {
            // Remplacer les variables dans le template
            $data = [
                'locataire_nom' => $tenantName,
                'locataire_prenom' => explode(' ', $tenantName)[0] ?? $tenantName,
                'propriete_adresse' => $property->getFullAddress(),
                'contrat_debut' => $lease ? $lease->getStartDate()->format($this->settingsService->get('email_date_format', 'd/m/Y')) : '',
                'contrat_fin' => $lease ? $lease->getEndDate()->format($this->settingsService->get('email_date_format', 'd/m/Y')) : '',
                'date_aujourdhui' => date($this->settingsService->get('email_date_format', 'd/m/Y')),
                'societe_nom' => $this->getFromName(),
                'societe_contact' => $this->getFromEmail(),
                'email_signature' => $this->settingsService->get('email_signature', 'LOKAPRO - Votre partenaire immobilier'),
            ];

            $template = $customTemplate;
            foreach ($data as $key => $value) {
                $template = str_replace('{{ ' . $key . ' }}', $value, $template);
            }

            return $template;
        }

        // Template par défaut si aucun template personnalisé
        return "
            <h2>Bienvenue chez " . $this->getFromName() . "</h2>
            <p>Bonjour " . explode(' ', $tenantName)[0] . " " . $tenantName . ",</p>
            <p>Nous sommes ravis de vous accueillir dans votre nouveau logement !</p>
            <p><strong>Propriété :</strong> " . $property->getFullAddress() . "</p>
            <p>Votre compte client a été créé avec succès. Vous pouvez maintenant accéder à votre espace personnel.</p>
            <p>Cordialement,</p>
            <p>" . $this->getFromName() . "</p>
        ";
    }
}
