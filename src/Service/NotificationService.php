<?php

namespace App\Service;

use Symfony\Component\Notifier\TexterInterface;
use Symfony\Component\Notifier\Message\SmsMessage;
use Psr\Log\LoggerInterface;

/**
 * Service de notification pour l'envoi de SMS via Orange
 */
class NotificationService
{
    public function __construct(
        private TexterInterface $texter,
        private SettingsService $settingsService,
        private LoggerInterface $logger
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
                $senderName = $this->settingsService->get('orange_sms_sender_name', 'MYLOCCA');
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
            'Bonjour %s, rappel : votre loyer de %.0f FCFA est attendu pour le %s. MYLOCCA',
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
            'Merci %s ! Paiement de %.0f FCFA reçu et confirmé. MYLOCCA',
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
            'Maintenance %s assignée pour %s. Nous vous contacterons bientôt. MYLOCCA',
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
}
