<?php

namespace App\Service;

use Exception;

/**
 * Service d'intégration avec CinetPay
 * Agrégateur de paiement Mobile Money et Carte Bancaire
 * Supports: Orange Money, MTN Money, Moov Money, Wave, Visa/Mastercard
 */
class CinetPayService
{
    private string $apikey;
    private string $site_id;
    private string $endpoint = 'https://api-checkout.cinetpay.com/v2/payment';

    private ?string $transaction_id = null;
    private ?float $amount = null;
    private string $currency = 'XOF';
    private ?string $description = null;
    private ?string $notify_url = null;
    private ?string $return_url = null;
    private string $channels = 'ALL';
    private string $lang = 'fr';
    private ?array $metadata = null;
    private array $invoice_data = [];
    private array $customer = [];

    public function __construct(
        private SettingsService $settingsService
    ) {
        // Récupérer les credentials depuis les paramètres
        $this->apikey = $this->settingsService->get('cinetpay_apikey', '383009496685bd7d235ad53.69596427');
        $this->site_id = $this->settingsService->get('cinetpay_site_id', '105899583');
    }

    public function setTransactionId(string $id): self
    {
        $this->transaction_id = $id;
        return $this;
    }

    public function setAmount(float $amount): self
    {
        $this->amount = $amount;
        return $this;
    }

    public function setCurrency(string $currency): self
    {
        $this->currency = $currency;
        return $this;
    }

    public function setDescription(string $desc): self
    {
        $this->description = $desc;
        return $this;
    }

    public function setNotifyUrl(string $url): self
    {
        $this->notify_url = $url;
        return $this;
    }

    public function setReturnUrl(string $url): self
    {
        $this->return_url = $url;
        return $this;
    }

    public function setChannels(string $channels): self
    {
        $this->channels = $channels;
        return $this;
    }

    public function setLang(string $lang): self
    {
        $this->lang = $lang;
        return $this;
    }

    public function setMetadata(array $metadata): self
    {
        $this->metadata = $metadata;
        return $this;
    }

    public function setInvoiceData(array $data): self
    {
        $this->invoice_data = $data;
        return $this;
    }

    public function setCustomer(array $customer): self
    {
        $this->customer = $customer;
        return $this;
    }

    /**
     * Initialise le paiement et retourne l'URL de redirection
     *
     * @return string URL de paiement CinetPay
     * @throws Exception
     */
    public function initPayment(): string
    {
        $data = [
            'apikey' => $this->apikey,
            'site_id' => $this->site_id,
            'transaction_id' => $this->transaction_id,
            'amount' => $this->amount,
            'currency' => $this->currency,
            'description' => $this->description,
            'notify_url' => $this->notify_url,
            'return_url' => $this->return_url,
            'channels' => $this->channels,
            'lang' => $this->lang,
        ];

        if ($this->metadata) {
            $data['metadata'] = json_encode($this->metadata);
        }

        if ($this->invoice_data) {
            $data['invoice_data'] = $this->invoice_data;
        }

        if (!empty($this->customer)) {
            $data = array_merge($data, $this->customer);
        }

        $response = $this->postJson($this->endpoint, $data);

        if (isset($response['data']['payment_url']) && filter_var($response['data']['payment_url'], FILTER_VALIDATE_URL)) {
            return $response['data']['payment_url'];
        }

        throw new Exception('Erreur CinetPay: ' . json_encode($response));
    }

    /**
     * Vérifie le statut d'une transaction
     *
     * @param string $transactionId ID de la transaction
     * @return array Réponse de CinetPay
     * @throws Exception
     */
    public function checkTransactionStatus(string $transactionId): array
    {
        $url = 'https://api-checkout.cinetpay.com/v2/payment/check';
        $data = [
            'apikey' => $this->apikey,
            'site_id' => $this->site_id,
            'transaction_id' => $transactionId,
        ];

        $response = $this->postJson($url, $data);

        if (!isset($response['code'])) {
            throw new Exception('Réponse inattendue de CinetPay: ' . json_encode($response));
        }

        return $response;
    }

    /**
     * Envoie une requête POST JSON
     */
    private function postJson(string $url, array $data): array
    {
        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, true);
        curl_setopt($ch, CURLOPT_TIMEOUT, 30);

        $result = curl_exec($ch);

        if ($result === false) {
            $error = curl_error($ch);
            curl_close($ch);
            throw new Exception('Erreur CURL: ' . $error);
        }

        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        $decoded = json_decode($result, true);

        if (!is_array($decoded)) {
            throw new Exception('Réponse non valide (HTTP ' . $httpCode . '): ' . $result);
        }

        return $decoded;
    }

    /**
     * Vérifie si CinetPay est configuré
     */
    public function isConfigured(): bool
    {
        return !empty($this->apikey) && !empty($this->site_id);
    }

    /**
     * Récupère les informations de configuration
     */
    public function getConfig(): array
    {
        return [
            'apikey' => $this->apikey,
            'site_id' => $this->site_id,
            'currency' => $this->currency,
            'configured' => $this->isConfigured(),
        ];
    }
}

