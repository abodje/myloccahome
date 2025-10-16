<?php

namespace App\Service;

use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;
use Psr\Log\LoggerInterface;

/**
 * Service de configuration pour l'Intelligence Artificielle
 */
class AIConfigurationService
{
    private ParameterBagInterface $params;
    private LoggerInterface $logger;
    private array $config;

    public function __construct(
        ParameterBagInterface $params,
        LoggerInterface $logger
    ) {
        $this->params = $params;
        $this->logger = $logger;
        $this->loadConfiguration();
    }

    /**
     * Charge la configuration IA depuis les paramètres
     */
    private function loadConfiguration(): void
    {
        $this->config = [
            'openai' => [
                'api_key' => $this->params->get('app.ai.openai.api_key', ''),
                'model' => $this->params->get('app.ai.openai.model', 'gpt-4'),
                'temperature' => (float) $this->params->get('app.ai.openai.temperature', 0.3),
                'max_tokens' => (int) $this->params->get('app.ai.openai.max_tokens', 1000),
                'timeout' => (int) $this->params->get('app.ai.openai.timeout', 30),
            ],
            'google' => [
                'api_key' => $this->params->get('app.ai.google.api_key', ''),
                'vision_api_url' => $this->params->get('app.ai.google.vision_api_url', 'https://vision.googleapis.com/v1/images:annotate'),
                'timeout' => (int) $this->params->get('app.ai.google.timeout', 30),
            ],
            'features' => [
                'rent_prediction_enabled' => (bool) $this->params->get('app.ai.features.rent_prediction_enabled', true),
                'risk_assessment_enabled' => (bool) $this->params->get('app.ai.features.risk_assessment_enabled', true),
                'contract_generation_enabled' => (bool) $this->params->get('app.ai.features.contract_generation_enabled', true),
                'image_analysis_enabled' => (bool) $this->params->get('app.ai.features.image_analysis_enabled', true),
                'recommendations_enabled' => (bool) $this->params->get('app.ai.features.recommendations_enabled', true),
                'customer_response_enabled' => (bool) $this->params->get('app.ai.features.customer_response_enabled', true),
                'market_analysis_enabled' => (bool) $this->params->get('app.ai.features.market_analysis_enabled', true),
            ],
            'limits' => [
                'requests_per_hour' => (int) $this->params->get('app.ai.limits.requests_per_hour', 100),
                'requests_per_day' => (int) $this->params->get('app.ai.limits.requests_per_day', 1000),
                'max_cost_per_day' => (float) $this->params->get('app.ai.limits.max_cost_per_day', 50.0),
            ],
            'cache' => [
                'enabled' => (bool) $this->params->get('app.ai.cache.enabled', true),
                'ttl' => (int) $this->params->get('app.ai.cache.ttl', 3600), // 1 heure
            ],
            'logging' => [
                'enabled' => (bool) $this->params->get('app.ai.logging.enabled', true),
                'log_level' => $this->params->get('app.ai.logging.log_level', 'info'),
                'log_requests' => (bool) $this->params->get('app.ai.logging.log_requests', true),
                'log_responses' => (bool) $this->params->get('app.ai.logging.log_responses', false),
            ]
        ];
    }

    /**
     * Récupère la configuration OpenAI
     */
    public function getOpenAIConfig(): array
    {
        return $this->config['openai'];
    }

    /**
     * Récupère la configuration Google AI
     */
    public function getGoogleAIConfig(): array
    {
        return $this->config['google'];
    }

    /**
     * Récupère la configuration des fonctionnalités
     */
    public function getFeaturesConfig(): array
    {
        return $this->config['features'];
    }

    /**
     * Récupère la configuration des limites
     */
    public function getLimitsConfig(): array
    {
        return $this->config['limits'];
    }

    /**
     * Récupère la configuration du cache
     */
    public function getCacheConfig(): array
    {
        return $this->config['cache'];
    }

    /**
     * Récupère la configuration du logging
     */
    public function getLoggingConfig(): array
    {
        return $this->config['logging'];
    }

    /**
     * Vérifie si une fonctionnalité IA est activée
     */
    public function isFeatureEnabled(string $feature): bool
    {
        return $this->config['features'][$feature . '_enabled'] ?? false;
    }

    /**
     * Récupère la clé API OpenAI
     */
    public function getOpenAIKey(): string
    {
        return $this->config['openai']['api_key'];
    }

    /**
     * Récupère la clé API Google
     */
    public function getGoogleAIKey(): string
    {
        return $this->config['google']['api_key'];
    }

    /**
     * Vérifie si les clés API sont configurées
     */
    public function isConfigured(): bool
    {
        return !empty($this->config['openai']['api_key']) || !empty($this->config['google']['api_key']);
    }

    /**
     * Récupère le modèle IA principal
     */
    public function getMainModel(): string
    {
        return $this->config['openai']['model'];
    }

    /**
     * Récupère la température pour les modèles IA
     */
    public function getTemperature(): float
    {
        return $this->config['openai']['temperature'];
    }

    /**
     * Récupère le nombre maximum de tokens
     */
    public function getMaxTokens(): int
    {
        return $this->config['openai']['max_tokens'];
    }

    /**
     * Récupère le timeout pour les requêtes IA
     */
    public function getTimeout(): int
    {
        return $this->config['openai']['timeout'];
    }

    /**
     * Récupère la limite de requêtes par heure
     */
    public function getRequestsPerHourLimit(): int
    {
        return $this->config['limits']['requests_per_hour'];
    }

    /**
     * Récupère la limite de requêtes par jour
     */
    public function getRequestsPerDayLimit(): int
    {
        return $this->config['limits']['requests_per_day'];
    }

    /**
     * Récupère la limite de coût par jour
     */
    public function getMaxCostPerDay(): float
    {
        return $this->config['limits']['max_cost_per_day'];
    }

    /**
     * Vérifie si le cache est activé
     */
    public function isCacheEnabled(): bool
    {
        return $this->config['cache']['enabled'];
    }

    /**
     * Récupère la durée de vie du cache (TTL)
     */
    public function getCacheTTL(): int
    {
        return $this->config['cache']['ttl'];
    }

    /**
     * Vérifie si le logging est activé
     */
    public function isLoggingEnabled(): bool
    {
        return $this->config['logging']['enabled'];
    }

    /**
     * Récupère le niveau de log
     */
    public function getLogLevel(): string
    {
        return $this->config['logging']['log_level'];
    }

    /**
     * Vérifie si les requêtes doivent être loggées
     */
    public function shouldLogRequests(): bool
    {
        return $this->config['logging']['log_requests'];
    }

    /**
     * Vérifie si les réponses doivent être loggées
     */
    public function shouldLogResponses(): bool
    {
        return $this->config['logging']['log_responses'];
    }

    /**
     * Récupère toute la configuration
     */
    public function getAllConfig(): array
    {
        return $this->config;
    }

    /**
     * Met à jour la configuration (pour l'interface d'administration)
     */
    public function updateConfig(array $newConfig): bool
    {
        try {
            // Valider la configuration
            $this->validateConfig($newConfig);

            // Mettre à jour la configuration
            $this->config = array_merge($this->config, $newConfig);

            // Sauvegarder dans les paramètres (si possible)
            $this->saveConfiguration();

            $this->logger->info('Configuration IA mise à jour', ['config' => $newConfig]);

            return true;

        } catch (\Exception $e) {
            $this->logger->error('Erreur mise à jour configuration IA: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * Valide la configuration IA
     */
    private function validateConfig(array $config): void
    {
        // Valider les clés API
        if (isset($config['openai']['api_key']) && !empty($config['openai']['api_key'])) {
            if (!str_starts_with($config['openai']['api_key'], 'sk-')) {
                throw new \InvalidArgumentException('Clé API OpenAI invalide');
            }
        }

        if (isset($config['google']['api_key']) && !empty($config['google']['api_key'])) {
            if (!str_starts_with($config['google']['api_key'], 'AIza')) {
                throw new \InvalidArgumentException('Clé API Google invalide');
            }
        }

        // Valider la température
        if (isset($config['openai']['temperature'])) {
            $temp = (float) $config['openai']['temperature'];
            if ($temp < 0 || $temp > 1) {
                throw new \InvalidArgumentException('La température doit être entre 0 et 1');
            }
        }

        // Valider les limites
        if (isset($config['limits']['requests_per_hour'])) {
            $limit = (int) $config['limits']['requests_per_hour'];
            if ($limit < 1 || $limit > 10000) {
                throw new \InvalidArgumentException('Limite requêtes/heure invalide');
            }
        }

        if (isset($config['limits']['max_cost_per_day'])) {
            $cost = (float) $config['limits']['max_cost_per_day'];
            if ($cost < 0 || $cost > 1000) {
                throw new \InvalidArgumentException('Limite coût/jour invalide');
            }
        }
    }

    /**
     * Sauvegarde la configuration (à implémenter selon vos besoins)
     */
    private function saveConfiguration(): void
    {
        // Ici vous pouvez sauvegarder la configuration dans :
        // - Un fichier de configuration
        // - La base de données
        // - Un service de configuration externe

        // Pour l'instant, on log juste la configuration
        $this->logger->info('Configuration IA sauvegardée', ['config' => $this->config]);
    }

    /**
     * Récupère les statistiques de configuration
     */
    public function getConfigStats(): array
    {
        return [
            'features_enabled' => array_sum($this->config['features']),
            'total_features' => count($this->config['features']),
            'openai_configured' => !empty($this->config['openai']['api_key']),
            'google_configured' => !empty($this->config['google']['api_key']),
            'cache_enabled' => $this->config['cache']['enabled'],
            'logging_enabled' => $this->config['logging']['enabled'],
            'requests_per_hour_limit' => $this->config['limits']['requests_per_hour'],
            'max_cost_per_day' => $this->config['limits']['max_cost_per_day'],
        ];
    }
}
