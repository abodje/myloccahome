<?php

namespace App\Service;

use App\Repository\SettingsRepository;

class SettingsService
{
    public function __construct(
        private SettingsRepository $settingsRepository
    ) {
    }

    /**
     * Récupère la valeur d'un paramètre
     */
    public function get(string $key, $defaultValue = null)
    {
        return $this->settingsRepository->getValue($key, $defaultValue);
    }

    /**
     * Définit la valeur d'un paramètre
     */
    public function set(string $key, $value): void
    {
        $this->settingsRepository->setValue($key, $value);
    }

    /**
     * Récupère les paramètres de l'application
     */
    public function getAppSettings(): array
    {
        return [
            'app_name' => $this->get('app_name', 'MYLOCCA'),
            'company_name' => $this->get('company_name', 'MYLOCCA Gestion'),
            'company_address' => $this->get('company_address', '123 Avenue de la République, 69000 Lyon'),
            'company_phone' => $this->get('company_phone', '04 72 00 00 00'),
            'company_email' => $this->get('company_email', 'contact@mylocca.com'),
        ];
    }

    /**
     * Récupère les paramètres de paiement
     */
    public function getPaymentSettings(): array
    {
        return [
            'default_rent_due_day' => $this->get('default_rent_due_day', 1),
            'late_fee_rate' => $this->get('late_fee_rate', 5.0),
            'auto_generate_rent' => $this->get('auto_generate_rent', true),
        ];
    }

    /**
     * Récupère les paramètres email
     */
    public function getEmailSettings(): array
    {
        return [
            'smtp_host' => $this->get('smtp_host', 'localhost'),
            'smtp_port' => $this->get('smtp_port', 587),
            'email_from' => $this->get('email_from', 'noreply@mylocca.com'),
        ];
    }

    /**
     * Récupère les paramètres de maintenance
     */
    public function getMaintenanceSettings(): array
    {
        return [
            'auto_assign_maintenance' => $this->get('auto_assign_maintenance', false),
            'urgent_notification' => $this->get('urgent_notification', true),
        ];
    }

    /**
     * Récupère les paramètres CinetPay
     */
    public function getCinetPaySettings(): array
    {
        return [
            'cinetpay_apikey' => $this->get('cinetpay_apikey', '383009496685bd7d235ad53.69596427'),
            'cinetpay_site_id' => $this->get('cinetpay_site_id', '105899583'),
            'cinetpay_secret_key' => $this->get('cinetpay_secret_key', '202783455685bd868b44665.45198979'),
            'cinetpay_environment' => $this->get('cinetpay_environment', 'test'),
            'cinetpay_currency' => $this->get('cinetpay_currency', 'XOF'),
            'cinetpay_return_url' => $this->get('cinetpay_return_url', ''),
            'cinetpay_enabled' => $this->get('cinetpay_enabled', true),
            'cinetpay_channels' => $this->get('cinetpay_channels', 'ALL'),
        ];
    }

    /**
     * Vérifie si une fonctionnalité est activée
     */
    public function isFeatureEnabled(string $feature): bool
    {
        return (bool) $this->get($feature, false);
    }

    /**
     * Active ou désactive une fonctionnalité
     */
    public function toggleFeature(string $feature, bool $enabled): void
    {
        $this->set($feature, $enabled);
    }

    /**
     * Récupère tous les paramètres sous forme de tableau associatif
     */
    public function getAllSettings(): array
    {
        $settings = $this->settingsRepository->findAll();
        $result = [];

        foreach ($settings as $setting) {
            $result[$setting->getSettingKey()] = $setting->getParsedValue();
        }

        return $result;
    }

    /**
     * Importe des paramètres depuis un tableau
     */
    public function importSettings(array $settings): void
    {
        foreach ($settings as $key => $value) {
            $this->set($key, $value);
        }
    }

    /**
     * Exporte tous les paramètres
     */
    public function exportSettings(): array
    {
        return $this->getAllSettings();
    }

    /**
     * Restaure les paramètres par défaut
     */
    public function restoreDefaults(): void
    {
        $defaultSettings = [
            // Application
            'app_name' => 'MYLOCCA',
            'company_name' => 'MYLOCCA Gestion',
            'company_address' => '123 Avenue de la République, 69000 Lyon',
            'company_phone' => '04 72 00 00 00',
            'company_email' => 'contact@mylocca.com',
            'app_logo' => '',
            'app_description' => 'Logiciel de gestion locative professionnel',
            'maintenance_mode' => false,
            'registration_enabled' => true,

            // Paiements
            'default_rent_due_day' => 1,
            'late_fee_rate' => 5.0,
            'auto_generate_rent' => true,
            'payment_reminder_days' => 7,
            'allow_partial_payments' => false,
            'minimum_payment_amount' => 10,
            'allow_advance_payments' => true,
            'minimum_advance_amount' => 50,

            // CinetPay
            'cinetpay_apikey' => '383009496685bd7d235ad53.69596427',
            'cinetpay_site_id' => '105899583',
            'cinetpay_secret_key' => '202783455685bd868b44665.45198979',
            'cinetpay_environment' => 'test',
            'cinetpay_currency' => 'XOF',
            'cinetpay_return_url' => '',
            'cinetpay_enabled' => true,
            'cinetpay_channels' => 'ALL',

            // Email
            'smtp_host' => 'localhost',
            'smtp_port' => 587,
            'smtp_username' => '',
            'smtp_password' => '',
            'smtp_encryption' => 'tls',
            'email_from' => 'noreply@mylocca.com',
            'email_from_name' => 'MYLOCCA',
            'email_notifications' => true,

            // Maintenance
            'auto_assign_maintenance' => false,
            'urgent_notification' => true,

            // Localisation
            'default_currency' => 'EUR',
            'date_format' => 'd/m/Y',
            'time_format' => 'H:i',
            'timezone' => 'Europe/Paris',
            'locale' => 'fr_FR',
            'decimal_separator' => ',',
            'thousands_separator' => ' ',
        ];

        $this->importSettings($defaultSettings);
    }
}
