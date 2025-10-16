<?php

namespace App\Twig;

use App\Service\CurrencyService;
use Twig\Extension\AbstractExtension;
use Twig\TwigFilter;
use Twig\TwigFunction;

class CurrencyExtension extends AbstractExtension
{
    public function __construct(
        private CurrencyService $currencyService
    ) {
    }

    public function getFilters(): array
    {
        return [
            new TwigFilter('currency', [$this, 'formatCurrency'], ['is_safe' => ['html']]),
            new TwigFilter('currency_symbol', [$this, 'getCurrencySymbol']),
        ];
    }

    public function getFunctions(): array
    {
        return [
            new TwigFunction('default_currency', [$this, 'getDefaultCurrency']),
            new TwigFunction('format_amount', [$this, 'formatAmount'], ['is_safe' => ['html']]),
            new TwigFunction('active_currencies', [$this, 'getActiveCurrencies']),
        ];
    }

    /**
     * Formate un montant avec la devise active
     */
    public function formatCurrency(?float $amount, bool $showSymbol = true): string
    {
        if ($amount === null) {
            return '-';
        }

        $currency = $this->currencyService->getActiveCurrency();
        return $this->currencyService->formatAmount($amount, $currency, $showSymbol);
    }

    /**
     * Récupère le symbole de la devise active
     */
    public function getCurrencySymbol(): string
    {
        $currency = $this->currencyService->getActiveCurrency();
        return $currency->getSymbol();
    }

    /**
     * Récupère la devise par défaut
     */
    public function getDefaultCurrency()
    {
        return $this->currencyService->getDefaultCurrency();
    }

    /**
     * Formate un montant avec options
     */
    public function formatAmount(?float $amount, ?string $currencyCode = null, bool $showSymbol = true): string
    {
        if ($amount === null) {
            return '-';
        }

        $currency = null;
        if ($currencyCode) {
            $currency = $this->currencyService->getCurrencyByCode($currencyCode);
        }

        return $this->currencyService->formatAmount($amount, $currency, $showSymbol);
    }

    /**
     * Récupère toutes les devises actives
     */
    public function getActiveCurrencies(): array
    {
        return $this->currencyService->getActiveCurrencies();
    }
}
