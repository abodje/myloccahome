<?php

namespace App\Service;

use App\Entity\Currency;
use App\Repository\CurrencyRepository;
use Doctrine\ORM\EntityManagerInterface;

class CurrencyService
{
    public function __construct(
        private CurrencyRepository $currencyRepository,
        private EntityManagerInterface $entityManager,
        private SettingsService $settingsService
    ) {
    }

    /**
     * Récupère la devise par défaut
     */
    public function getDefaultCurrency(): Currency
    {
        $currency = $this->currencyRepository->findDefault();

        if (!$currency) {
            // Créer EUR comme devise par défaut si aucune n'existe
            $currency = $this->createDefaultCurrency();
        }

        return $currency;
    }

    /**
     * Formate un montant avec la devise par défaut
     */
    public function formatAmount(float $amount, ?Currency $currency = null, bool $showSymbol = true): string
    {
        if (!$currency) {
            $currency = $this->getDefaultCurrency();
        }

        return $currency->formatAmount($amount, $showSymbol);
    }

    /**
     * Convertit un montant d'une devise à une autre
     */
    public function convertAmount(float $amount, Currency $fromCurrency, Currency $toCurrency): float
    {
        return $fromCurrency->convertAmount($amount, $toCurrency);
    }

    /**
     * Met à jour les taux de change depuis une API
     */
    public function updateExchangeRates(): bool
    {
        try {
            // Simulation d'appel API (vous pourriez utiliser une vraie API comme Fixer.io)
            $rates = [
                'EUR' => 1.0,
                'USD' => 1.08,
                'GBP' => 0.87,
                'CHF' => 0.96,
                'CAD' => 1.47,
            ];

            $this->currencyRepository->updateExchangeRates($rates);

            // Enregistrer la date de dernière mise à jour
            $this->settingsService->set('last_exchange_rate_update', date('Y-m-d H:i:s'));

            return true;
        } catch (\Exception $e) {
            return false;
        }
    }

    /**
     * Récupère toutes les devises actives
     */
    public function getActiveCurrencies(): array
    {
        return $this->currencyRepository->findActive();
    }

    /**
     * Définit une devise comme devise par défaut
     */
    public function setDefaultCurrency(Currency $currency): void
    {
        $this->currencyRepository->setAsDefault($currency);
        $this->settingsService->set('default_currency', $currency->getCode());
    }

    /**
     * Crée la devise par défaut (EUR)
     */
    private function createDefaultCurrency(): Currency
    {
        $currency = new Currency();
        $currency->setCode('EUR')
                 ->setName('Euro')
                 ->setSymbol('€')
                 ->setExchangeRate('1.000000')
                 ->setDefault(true)
                 ->setActive(true);

        $this->entityManager->persist($currency);
        $this->entityManager->flush();

        return $currency;
    }

    /**
     * Initialise les devises par défaut
     */
    public function initializeDefaultCurrencies(): void
    {
        $defaultCurrencies = [
            ['code' => 'EUR', 'name' => 'Euro', 'symbol' => '€', 'rate' => 1.0, 'default' => true],
            ['code' => 'USD', 'name' => 'Dollar américain', 'symbol' => '$', 'rate' => 1.08, 'default' => false],
            ['code' => 'GBP', 'name' => 'Livre sterling', 'symbol' => '£', 'rate' => 0.87, 'default' => false],
            ['code' => 'CHF', 'name' => 'Franc suisse', 'symbol' => 'CHF', 'rate' => 0.96, 'default' => false],
            ['code' => 'CAD', 'name' => 'Dollar canadien', 'symbol' => 'CAD', 'rate' => 1.47, 'default' => false],
            ['code' => 'CFA', 'name' => 'XOF', 'symbol' => 'XOF', 'rate' => 1, 'default' => true],
        ];

        foreach ($defaultCurrencies as $currencyData) {
            $existing = $this->currencyRepository->findByCode($currencyData['code']);

            if (!$existing) {
                $currency = new Currency();
                $currency->setCode($currencyData['code'])
                         ->setName($currencyData['name'])
                         ->setSymbol($currencyData['symbol'])
                         ->setExchangeRate((string)$currencyData['rate'])
                         ->setDefault($currencyData['default'])
                         ->setActive(true);

                $this->entityManager->persist($currency);
            }
        }

        $this->entityManager->flush();
    }

    /**
     * Récupère les paramètres de localisation
     */
    public function getLocalizationSettings(): array
    {
        return [
            'default_currency' => $this->getDefaultCurrency()->getCode(),
            'date_format' => $this->settingsService->get('date_format', 'd/m/Y'),
            'time_format' => $this->settingsService->get('time_format', 'H:i'),
            'timezone' => $this->settingsService->get('timezone', 'Europe/Paris'),
            'locale' => $this->settingsService->get('locale', 'fr_FR'),
            'decimal_separator' => $this->settingsService->get('decimal_separator', ','),
            'thousands_separator' => $this->settingsService->get('thousands_separator', ' '),
        ];
    }

    /**
     * Met à jour les paramètres de localisation
     */
    public function updateLocalizationSettings(array $settings): void
    {
        foreach ($settings as $key => $value) {
            $this->settingsService->set($key, $value);
        }
    }

    /**
     * Récupère une devise par son code
     */
    public function getCurrencyByCode(string $code): ?Currency
    {
        return $this->currencyRepository->findByCode($code);
    }

    /**
     * Récupère la devise active actuelle (celle utilisée dans l'application)
     */
    public function getActiveCurrency(): Currency
    {
        // D'abord, essayons de récupérer la devise depuis les paramètres
        $currencyCode = $this->settingsService->get('active_currency');

        if ($currencyCode) {
            $currency = $this->getCurrencyByCode($currencyCode);
            if ($currency && $currency->isActive()) {
                return $currency;
            }
        }

        // Sinon, utiliser la devise par défaut
        return $this->getDefaultCurrency();
    }

    /**
     * Définit la devise active pour l'application
     */
    public function setActiveCurrency(Currency $currency): void
    {
        if (!$currency->isActive()) {
            throw new \InvalidArgumentException('La devise doit être active pour être définie comme devise principale');
        }

        $this->settingsService->set('active_currency', $currency->getCode());
    }
}
