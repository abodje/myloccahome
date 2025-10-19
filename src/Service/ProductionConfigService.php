<?php

namespace App\Service;

use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;

class ProductionConfigService
{
    private ParameterBagInterface $params;
    private SettingsService $settingsService;

    public function __construct(
        ParameterBagInterface $params,
        SettingsService $settingsService
    ) {
        $this->params = $params;
        $this->settingsService = $settingsService;
    }

    /**
     * Obtient la configuration optimisée pour la production
     */
    public function getProductionConfig(): array
    {
        $domain = $this->getProductionDomain();
        $scheme = $this->isHttpsEnabled() ? 'https' : 'http';
        $baseUrl = $scheme . '://' . $domain;

        return [
            'domain' => $domain,
            'environment' => $this->getEnvironment(),
            'ssl_enabled' => $this->isHttpsEnabled(),
            'base_url' => $baseUrl,
            'cdn_url' => $baseUrl,
            'api_url' => $baseUrl . '/api',
        ];
    }

    /**
     * Récupère le domaine de production dynamiquement
     */
    private function getProductionDomain(): string
    {
        // Essayer de récupérer depuis les paramètres système
        $savedDomain = $this->settingsService->get('app_production_domain');

        if (!empty($savedDomain)) {
            return $savedDomain;
        }

        // Détecter automatiquement le domaine depuis $_SERVER
        if (isset($_SERVER['HTTP_HOST'])) {
            $host = $_SERVER['HTTP_HOST'];

            // Nettoyer le port si présent
            $host = explode(':', $host)[0];

            // Si ce n'est pas localhost ou une IP, c'est probablement la production
            if (strpos($host, 'localhost') === false &&
                strpos($host, '127.0.0.1') === false &&
                !filter_var($host, FILTER_VALIDATE_IP)) {

                // Sauvegarder automatiquement le domaine détecté
                $this->settingsService->set('app_production_domain', $host);
                return $host;
            }
        }

        // Fallback si aucune détection ne fonctionne
        return 'app.lokapro.tech';
    }

    /**
     * Vérifie si HTTPS est activé
     */
    private function isHttpsEnabled(): bool
    {
        // Vérifier via $_SERVER
        if (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on') {
            return true;
        }

        // Vérifier via le header X-Forwarded-Proto (pour les proxies)
        if (isset($_SERVER['HTTP_X_FORWARDED_PROTO']) && $_SERVER['HTTP_X_FORWARDED_PROTO'] === 'https') {
            return true;
        }

        // Vérifier via le header X-Forwarded-Ssl (pour certains proxies)
        if (isset($_SERVER['HTTP_X_FORWARDED_SSL']) && $_SERVER['HTTP_X_FORWARDED_SSL'] === 'on') {
            return true;
        }

        // Vérifier via REQUEST_SCHEME
        if (isset($_SERVER['REQUEST_SCHEME']) && $_SERVER['REQUEST_SCHEME'] === 'https') {
            return true;
        }

        // Vérifier via SERVER_PORT (port 443 = HTTPS)
        if (isset($_SERVER['SERVER_PORT']) && $_SERVER['SERVER_PORT'] == 443) {
            return true;
        }

        return false;
    }

    /**
     * Détermine l'environnement actuel
     */
    public function getEnvironment(): string
    {
        $env = $this->params->get('kernel.environment');

        // Si on est sur un domaine de production (pas localhost/IP), on est en production
        $domain = $this->getProductionDomain();
        if (isset($_SERVER['HTTP_HOST']) && strpos($_SERVER['HTTP_HOST'], $domain) !== false) {
            return 'prod';
        }

        return $env;
    }

    /**
     * Obtient l'URL de base pour les environnements
     */
    public function getEnvironmentBaseUrl(): string
    {
        $config = $this->getProductionConfig();

        if ($this->getEnvironment() === 'prod') {
            return $config['base_url'];
        }

        // Pour le développement local - détecter automatiquement
        if (isset($_SERVER['HTTP_HOST'])) {
            $scheme = $this->isHttpsEnabled() ? 'https' : 'http';
            return $scheme . '://' . $_SERVER['HTTP_HOST'];
        }

        return 'http://127.0.0.1:8000';
    }

    /**
     * Génère l'URL complète pour un environnement spécifique
     */
    public function generateEnvironmentUrl(string $envCode): string
    {
        $baseUrl = $this->getEnvironmentBaseUrl();
        return $baseUrl . '/env/' . $envCode;
    }

    /**
     * Génère l'URL complète pour une démo
     */
    public function generateDemoUrl(string $demoCode): string
    {
        $baseUrl = $this->getEnvironmentBaseUrl();
        return $baseUrl . '/demo/' . $demoCode;
    }

    /**
     * Vérifie si on est en production
     */
    public function isProduction(): bool
    {
        return $this->getEnvironment() === 'prod';
    }

    /**
     * Obtient la configuration des domaines pour les environnements
     */
    public function getEnvironmentDomainConfig(): array
    {
        $config = $this->getProductionConfig();

        return [
            'base_domain' => $config['domain'],
            'environment_prefix' => 'env',
            'demo_prefix' => 'demo',
            'ssl_enabled' => $config['ssl_enabled'],
        ];
    }

    /**
     * Obtient des informations de debug sur la production
     */
    public function getProductionDebugInfo(): array
    {
        $config = $this->getProductionConfig();

        return [
            'detected_domain' => $this->getProductionDomain(),
            'saved_domain' => $this->settingsService->get('app_production_domain'),
            'current_url' => $config['base_url'],
            'ssl_enabled' => $config['ssl_enabled'],
            'environment' => $this->getEnvironment(),
            'sources' => [
                'http_host' => $_SERVER['HTTP_HOST'] ?? null,
                'https' => $_SERVER['HTTPS'] ?? null,
                'request_scheme' => $_SERVER['REQUEST_SCHEME'] ?? null,
                'server_port' => $_SERVER['SERVER_PORT'] ?? null,
                'x_forwarded_proto' => $_SERVER['HTTP_X_FORWARDED_PROTO'] ?? null,
                'x_forwarded_ssl' => $_SERVER['HTTP_X_FORWARDED_SSL'] ?? null,
            ]
        ];
    }

    /**
     * Définit manuellement le domaine de production
     */
    public function setProductionDomain(string $domain): void
    {
        $this->settingsService->set('app_production_domain', $domain);
    }
}
