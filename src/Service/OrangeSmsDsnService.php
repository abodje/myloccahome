<?php

namespace App\Service;

/**
 * Service pour générer dynamiquement la DSN Orange SMS
 * à partir des paramètres stockés en base de données
 */
class OrangeSmsDsnService
{
    public function __construct(
        private SettingsService $settingsService
    ) {
    }

    /**
     * Génère la DSN Orange SMS à partir des paramètres de configuration
     */
    public function generateDsn(): string
    {
        $clientId = $this->settingsService->get('orange_sms_client_id', '');
        $clientSecret = $this->settingsService->get('orange_sms_client_secret', '');
        $senderName = $this->settingsService->get('orange_sms_sender_name', 'LOKAPRO');

        // Si les paramètres ne sont pas configurés, retourner une DSN vide
        if (empty($clientId) || empty($clientSecret)) {
            return '';
        }

        // Générer la DSN au format: orange-sms://CLIENT_ID:CLIENT_SECRET@default?from=FROM&sender_name=SENDER_NAME
        $dsn = sprintf(
            'orange-sms://%s:%s@default?from=+2250000&sender_name=%s',
            urlencode($clientId),
            urlencode($clientSecret),
            urlencode($senderName)
        );

        return $dsn;
    }

    /**
     * Vérifie si la configuration Orange SMS est complète
     */
    public function isConfigured(): bool
    {
        $clientId = $this->settingsService->get('orange_sms_client_id', '');
        $clientSecret = $this->settingsService->get('orange_sms_client_secret', '');

        return !empty($clientId) && !empty($clientSecret);
    }

    /**
     * Retourne les paramètres Orange SMS pour debug
     */
    public function getDebugInfo(): array
    {
        return [
            'client_id' => $this->settingsService->get('orange_sms_client_id', ''),
            'client_secret' => $this->settingsService->get('orange_sms_client_secret', ''),
            'sender_name' => $this->settingsService->get('orange_sms_sender_name', 'LOKAPRO'),
            'is_configured' => $this->isConfigured(),
            'dsn' => $this->generateDsn(),
        ];
    }
}
