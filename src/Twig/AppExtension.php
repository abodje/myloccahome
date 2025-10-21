<?php

namespace App\Twig;

use App\Service\SettingsService;
use App\Service\CurrencyService;
use Symfony\Bundle\SecurityBundle\Security;
use Twig\Extension\AbstractExtension;
use Twig\TwigFunction;

class AppExtension extends AbstractExtension
{
    public function __construct(
        private SettingsService $settingsService,
        private CurrencyService $currencyService,
        private Security $security
    ) {
    }

    public function getFunctions(): array
    {
        return [
            new TwigFunction('app_settings', [$this, 'getAppSettings']),
            new TwigFunction('app_setting', [$this, 'getAppSetting']),
            new TwigFunction('company_info', [$this, 'getCompanyInfo']),
            new TwigFunction('current_currency', [$this, 'getCurrentCurrency']),
            new TwigFunction('is_admin', [$this, 'isAdmin']),
            new TwigFunction('is_manager', [$this, 'isManager']),
            new TwigFunction('is_tenant', [$this, 'isTenant']),
        ];
    }

    /**
     * Récupère tous les paramètres de l'application
     */
    public function getAppSettings(): array
    {
        return $this->settingsService->getAppSettings();
    }

    /**
     * Récupère un paramètre spécifique
     */
    public function getAppSetting(string $key, $default = null)
    {
        return $this->settingsService->get($key, $default);
    }

    /**
     * Récupère les informations de l'entreprise
     */
    public function getCompanyInfo(): array
    {
        return [
            'name' => $this->settingsService->get('company_name', 'LOKAPRO Gestion'),
            'address' => $this->settingsService->get('company_address', ''),
            'phone' => $this->settingsService->get('company_phone', ''),
            'email' => $this->settingsService->get('company_email', 'contact@app.lokapro.tech'),
            'logo' => $this->settingsService->get('app_logo', ''),
        ];
    }

    /**
     * Récupère la devise active
     */
    public function getCurrentCurrency()
    {
        return $this->currencyService->getActiveCurrency();
    }

    /**
     * Vérifie si l'utilisateur est admin
     */
    public function isAdmin(): bool
    {
        $user = $this->security->getUser();
        return $user && in_array('ROLE_ADMIN', $user->getRoles());
    }

    /**
     * Vérifie si l'utilisateur est gestionnaire
     */
    public function isManager(): bool
    {
        $user = $this->security->getUser();
        return $user && (in_array('ROLE_MANAGER', $user->getRoles()) || $this->isAdmin());
    }

    /**
     * Vérifie si l'utilisateur est locataire
     */
    public function isTenant(): bool
    {
        $user = $this->security->getUser();
        return $user && in_array('ROLE_TENANT', $user->getRoles());
    }
}

