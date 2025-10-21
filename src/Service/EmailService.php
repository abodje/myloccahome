<?php

namespace App\Service;

use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mime\Email;
use Twig\Environment;

/**
 * Service centralisé pour l'envoi d'emails
 * Utilise les paramètres configurés dans admin/parametres/email
 */
class EmailService
{
    public function __construct(
        private MailerInterface $mailer,
        private Environment $twig,
        private SettingsService $settingsService
    ) {
    }

    /**
     * Envoie un email en utilisant les paramètres configurés
     */
    public function sendEmail(
        string $to,
        string $subject,
        string $htmlContent,
        ?string $textContent = null,
        ?string $replyTo = null
    ): bool {
        // Vérifier si les notifications email sont activées
        if (!$this->settingsService->get('email_notifications', true)) {
            return false; // Les notifications email sont désactivées
        }

        try {
            $fromEmail = $this->settingsService->get('email_from', 'noreply@app.lokapro.tech');
            $fromName = $this->settingsService->get('email_from_name', 'LOKAPRO');

            $email = (new Email())
                ->from($fromEmail, $fromName)
                ->to($to)
                ->subject($subject)
                ->html($htmlContent);

            if ($textContent) {
                $email->text($textContent);
            }

            if ($replyTo) {
                $email->replyTo($replyTo);
            }

            $this->mailer->send($email);
            return true;
        } catch (\Exception $e) {
            // Log l'erreur si nécessaire
            return false;
        }
    }

    /**
     * Envoie un email en utilisant un template Twig
     */
    public function sendTemplateEmail(
        string $to,
        string $subject,
        string $template,
        array $context = [],
        ?string $replyTo = null
    ): bool {
        try {
            $htmlContent = $this->twig->render($template, $context);
            return $this->sendEmail($to, $subject, $htmlContent, null, $replyTo);
        } catch (\Exception $e) {
            return false;
        }
    }

    /**
     * Envoie un email à plusieurs destinataires
     */
    public function sendBulkEmail(
        array $recipients,
        string $subject,
        string $htmlContent,
        ?string $textContent = null
    ): array {
        $results = ['sent' => 0, 'failed' => 0, 'errors' => []];

        foreach ($recipients as $recipient) {
            $success = $this->sendEmail($recipient, $subject, $htmlContent, $textContent);

            if ($success) {
                $results['sent']++;
            } else {
                $results['failed']++;
                $results['errors'][] = "Échec d'envoi à {$recipient}";
            }
        }

        return $results;
    }

    /**
     * Teste la configuration email
     */
    public function testConfiguration(string $testEmail): bool
    {
        $subject = 'Test de configuration email - ' . $this->settingsService->get('app_name', 'LOKAPRO');

        $htmlContent = $this->twig->render('emails/test.html.twig', [
            'company' => $this->settingsService->getAppSettings(),
            'test_date' => new \DateTime(),
            'from_email' => $this->settingsService->get('email_from', 'noreply@app.lokapro.tech'),
            'from_name' => $this->settingsService->get('email_from_name', 'LOKAPRO'),
        ]);

        return $this->sendEmail($testEmail, $subject, $htmlContent);
    }

    /**
     * Vérifie si les notifications email sont activées
     */
    public function areNotificationsEnabled(): bool
    {
        return $this->settingsService->get('email_notifications', true);
    }

    /**
     * Récupère l'adresse email d'expéditeur configurée
     */
    public function getFromEmail(): string
    {
        return $this->settingsService->get('email_from', 'noreply@app.lokapro.tech');
    }

    /**
     * Récupère le nom de l'expéditeur configuré
     */
    public function getFromName(): string
    {
        return $this->settingsService->get('email_from_name', 'LOKAPRO');
    }

    /**
     * Récupère les paramètres email complets
     */
    public function getEmailSettings(): array
    {
        return [
            'from_email' => $this->getFromEmail(),
            'from_name' => $this->getFromName(),
            'notifications_enabled' => $this->areNotificationsEnabled(),
            'smtp_host' => $this->settingsService->get('smtp_host', ''),
            'smtp_port' => $this->settingsService->get('smtp_port', ''),
            'smtp_username' => $this->settingsService->get('smtp_username', ''),
            'smtp_encryption' => $this->settingsService->get('smtp_encryption', ''),
        ];
    }
}
