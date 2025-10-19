<?php

namespace App\Service;

use App\Entity\Environment;
use App\Entity\Organization;
use App\Repository\EnvironmentRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\String\Slugger\SluggerInterface;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;
use Psr\Log\LoggerInterface;

class EnvironmentManagementService
{
    private EntityManagerInterface $entityManager;
    private EnvironmentRepository $environmentRepository;
    private SluggerInterface $slugger;
    private Filesystem $filesystem;
    private ParameterBagInterface $params;
    private LoggerInterface $logger;
    private SettingsService $settingsService;
    private \Symfony\Component\HttpFoundation\RequestStack $requestStack;
    private ProductionConfigService $productionConfig;
    private string $environmentsDir;

    public function __construct(
        EntityManagerInterface $entityManager,
        EnvironmentRepository $environmentRepository,
        SluggerInterface $slugger,
        Filesystem $filesystem,
        ParameterBagInterface $params,
        LoggerInterface $logger,
        SettingsService $settingsService,
        \Symfony\Component\HttpFoundation\RequestStack $requestStack,
        ProductionConfigService $productionConfig
    ) {
        $this->entityManager = $entityManager;
        $this->environmentRepository = $environmentRepository;
        $this->slugger = $slugger;
        $this->filesystem = $filesystem;
        $this->params = $params;
        $this->logger = $logger;
        $this->settingsService = $settingsService;
        $this->requestStack = $requestStack;
        $this->productionConfig = $productionConfig;
        $this->environmentsDir = $this->params->get('kernel.project_dir') . '/environments';

        // Créer le dossier des environnements s'il n'existe pas
        if (!$this->filesystem->exists($this->environmentsDir)) {
            $this->filesystem->mkdir($this->environmentsDir);
        }
    }

    /**
     * Crée un nouvel environnement
     */
    public function createEnvironment(
        Organization $organization,
        string $name,
        string $type = 'PRODUCTION',
        ?string $description = null,
        ?string $customDomain = null,
        array $configuration = []
    ): array {
        try {
            $this->logger->info('Création d\'un nouvel environnement', [
                'organization' => $organization->getName(),
                'name' => $name,
                'type' => $type
            ]);

            // Générer un sous-domaine unique
            $subdomain = $this->generateUniqueSubdomain($name, $organization);

            // Créer l'entité Environment
            $environment = new Environment();
            $environment->setName($name);
            $environment->setSubdomain($subdomain);
            $environment->setType($type);
            $environment->setOrganization($organization);
            $environment->setDescription($description);
            $environment->setDomain($customDomain);
            $environment->setConfiguration($configuration);
            $environment->setStatus('CREATING');

            $this->entityManager->persist($environment);
            $this->entityManager->flush();

            // Créer la configuration de l'environnement
            $this->createEnvironmentConfiguration($environment);

            // Configurer le DNS et Apache (simulation)
            $this->configureEnvironmentInfrastructure($environment);

            // Mettre à jour le statut
            $environment->setStatus('ACTIVE');
            $environment->setLastDeployedAt(new \DateTime());
            $environment->setUpdatedAt(new \DateTime());
            $this->entityManager->flush();

            $baseDomain = $this->getBaseDomain();
            $environmentUrl = $environment->getUrlWithBaseDomain($baseDomain);

            $this->logger->info('Environnement créé avec succès', [
                'environment_id' => $environment->getId(),
                'subdomain' => $subdomain,
                'url' => $environmentUrl,
                'base_domain' => $baseDomain
            ]);

            return [
                'success' => true,
                'environment' => $environment,
                'url' => $environmentUrl,
                'base_domain' => $baseDomain,
                'message' => 'Environnement créé avec succès'
            ];

        } catch (\Exception $e) {
            $this->logger->error('Erreur lors de la création de l\'environnement', [
                'error' => $e->getMessage(),
                'organization' => $organization->getName(),
                'name' => $name
            ]);

            return [
                'success' => false,
                'error' => $e->getMessage(),
                'message' => 'Erreur lors de la création de l\'environnement'
            ];
        }
    }

    /**
     * Récupère le domaine de base dynamiquement
     */
    public function getBaseDomain(): string
    {
        // Si on est en production, utiliser le domaine détecté dynamiquement
        if ($this->productionConfig->isProduction()) {
            return $this->productionConfig->getProductionConfig()['domain'];
        }

        // Essayer de récupérer depuis les paramètres système
        $baseDomain = $this->settingsService->get('app_base_domain');

        if (empty($baseDomain)) {
            // Utiliser le domaine par défaut basé sur l'URL actuelle
            $baseDomain = $this->getDefaultBaseDomain();

            // Sauvegarder automatiquement le domaine détecté
            $this->settingsService->set('app_base_domain', $baseDomain);

            $this->logger->info('Domaine de base détecté automatiquement', [
                'domain' => $baseDomain,
                'source' => 'auto_detection',
                'environment' => $this->productionConfig->getEnvironment()
            ]);
        }

        return $baseDomain;
    }

    /**
     * Récupère le domaine par défaut basé sur l'URL actuelle
     */
    private function getDefaultBaseDomain(): string
    {
        // Méthode 1: Récupérer depuis $_SERVER['HTTP_HOST']
        if (isset($_SERVER['HTTP_HOST'])) {
            $host = $_SERVER['HTTP_HOST'];

            // Nettoyer le port si présent
            $host = explode(':', $host)[0];

            // Si c'est localhost ou une IP, utiliser le domaine local dynamique
            if (strpos($host, 'localhost') !== false ||
                strpos($host, '127.0.0.1') !== false ||
                filter_var($host, FILTER_VALIDATE_IP)) {
                return $this->getLocalDomain();
            }

            // Retourner le domaine actuel
            return $host;
        }

        // Méthode 2: Récupérer depuis $_SERVER['SERVER_NAME']
        if (isset($_SERVER['SERVER_NAME'])) {
            $serverName = $_SERVER['SERVER_NAME'];

            // Nettoyer le port si présent
            $serverName = explode(':', $serverName)[0];

            // Si c'est localhost ou une IP, utiliser le domaine par défaut
            if (strpos($serverName, 'localhost') !== false ||
                strpos($serverName, '127.0.0.1') !== false ||
                filter_var($serverName, FILTER_VALIDATE_IP)) {
                return 'mylocca.local';
            }

            return $serverName;
        }

        // Méthode 3: Récupérer depuis les paramètres Symfony
        $request = $this->requestStack->getCurrentRequest();
        if ($request) {
            $host = $request->getHost();

            // Si c'est localhost ou une IP, utiliser le domaine local dynamique
            if (strpos($host, 'localhost') !== false ||
                strpos($host, '127.0.0.1') !== false ||
                filter_var($host, FILTER_VALIDATE_IP)) {
                return $this->getLocalDomain();
            }

            return $host;
        }

        // Fallback si aucune méthode ne fonctionne
        return 'app.lokapro.tech';
    }

    /**
     * Récupère le domaine local dynamiquement
     */
    private function getLocalDomain(): string
    {
        // Essayer de récupérer depuis les paramètres système
        $localDomain = $this->settingsService->get('app_local_domain');

        if (empty($localDomain)) {
            // Générer un domaine local basé sur le nom du projet
            $projectDir = basename($this->params->get('kernel.project_dir'));
            $localDomain = strtolower($projectDir) . '.local';

            // Sauvegarder automatiquement
            $this->settingsService->set('app_local_domain', $localDomain);

            $this->logger->info('Domaine local généré automatiquement', [
                'domain' => $localDomain,
                'project_dir' => $projectDir
            ]);
        }

        return $localDomain;
    }

    /**
     * Définit le domaine de base dans les paramètres système
     */
    public function setBaseDomain(string $domain): void
    {
        $this->settingsService->set('app_base_domain', $domain);
    }

    /**
     * Définit le domaine local dans les paramètres système
     */
    public function setLocalDomain(string $domain): void
    {
        $this->settingsService->set('app_local_domain', $domain);
    }

    /**
     * Obtient des informations de debug sur le domaine
     */
    public function getDomainDebugInfo(): array
    {
        $info = [
            'current_domain' => $this->getBaseDomain(),
            'saved_domain' => $this->settingsService->get('app_base_domain'),
            'detected_domain' => $this->getDefaultBaseDomain(),
            'local_domain' => $this->getLocalDomain(),
            'saved_local_domain' => $this->settingsService->get('app_local_domain'),
            'project_dir' => basename($this->params->get('kernel.project_dir')),
            'sources' => []
        ];

        // Méthode 1: HTTP_HOST
        if (isset($_SERVER['HTTP_HOST'])) {
            $info['sources']['http_host'] = $_SERVER['HTTP_HOST'];
        }

        // Méthode 2: SERVER_NAME
        if (isset($_SERVER['SERVER_NAME'])) {
            $info['sources']['server_name'] = $_SERVER['SERVER_NAME'];
        }

        // Méthode 3: Request Stack
        $request = $this->requestStack->getCurrentRequest();
        if ($request) {
            $info['sources']['request_host'] = $request->getHost();
            $info['sources']['request_scheme'] = $request->getScheme();
        }

        return $info;
    }

    /**
     * Génère un identifiant unique pour l'environnement (pour cPanel partagé)
     */
    private function generateUniqueSubdomain(string $name, Organization $organization): string
    {
        // Pour cPanel partagé, on utilise des codes alphanumériques courts
        // au lieu de vrais sous-domaines

        // Créer un code basé sur l'organisation et le nom
        $orgCode = strtoupper(substr($this->slugger->slug($organization->getName())->toString(), 0, 3));
        $nameCode = strtoupper(substr($this->slugger->slug($name)->toString(), 0, 3));

        // Générer un code unique
        $baseCode = $orgCode . $nameCode;

        // Ajouter un timestamp pour l'unicité
        $timestamp = date('md');

        // Créer le code final
        $code = $baseCode . $timestamp;

        // Limiter la longueur
        if (strlen($code) > 12) {
            $code = substr($code, 0, 12);
        }

        // Vérifier l'unicité et ajouter un suffixe si nécessaire
        $originalCode = $code;
        $counter = 1;

        while (!$this->environmentRepository->isSubdomainAvailable($code)) {
            $code = $originalCode . $counter;
            $counter++;

            // Limiter à 15 caractères maximum
            if (strlen($code) > 15) {
                $code = substr($originalCode, 0, 10) . $counter;
            }
        }

        return $code;
    }

    /**
     * Crée la configuration de l'environnement
     */
    private function createEnvironmentConfiguration(Environment $environment): void
    {
        $envDir = $this->environmentsDir . '/' . $environment->getSubdomain();

        if (!$this->filesystem->exists($envDir)) {
            $this->filesystem->mkdir($envDir);
        }

        // Créer le fichier .env pour l'environnement
        $envContent = $this->generateEnvironmentFile($environment);
        $this->filesystem->dumpFile($envDir . '/.env', $envContent);

        // Créer le fichier de configuration Apache
        $apacheConfig = $this->generateApacheConfig($environment);
        $this->filesystem->dumpFile($envDir . '/apache.conf', $apacheConfig);

        // Créer le fichier de configuration Nginx (optionnel)
        $nginxConfig = $this->generateNginxConfig($environment);
        $this->filesystem->dumpFile($envDir . '/nginx.conf', $nginxConfig);

        $this->logger->info('Configuration de l\'environnement créée', [
            'environment_id' => $environment->getId(),
            'subdomain' => $environment->getSubdomain()
        ]);
    }

    /**
     * Génère le fichier .env pour l'environnement
     */
    private function generateEnvironmentFile(Environment $environment): string
    {
        $config = $environment->getConfiguration();
        $envVars = $environment->getEnvironmentVariables();

        $envContent = "# Configuration pour l'environnement {$environment->getName()}\n";
        $envContent .= "APP_ENV=prod\n";
        $envContent .= "APP_DEBUG=false\n";
        $envContent .= "APP_URL={$environment->getUrl()}\n";
        $envContent .= "APP_SUBDOMAIN={$environment->getSubdomain()}\n";
        $envContent .= "APP_ORGANIZATION_ID={$environment->getOrganization()->getId()}\n";

        // Variables d'environnement personnalisées
        if ($envVars) {
            foreach ($envVars as $key => $value) {
                $envContent .= "{$key}={$value}\n";
            }
        }

        // Configuration personnalisée
        if ($config) {
            foreach ($config as $key => $value) {
                if (is_string($value) || is_numeric($value)) {
                    $envContent .= "APP_{$key}=" . strtoupper($key) . "={$value}\n";
                }
            }
        }

        return $envContent;
    }

    /**
     * Génère la configuration .htaccess pour cPanel partagé
     */
    private function generateApacheConfig(Environment $environment): string
    {
        // Pour cPanel partagé, on génère un fichier .htaccess
        // qui gère le routage basé sur l'URL

        $config = "# Configuration pour l'environnement {$environment->getName()}\n";
        $config .= "# Code: {$environment->getSubdomain()}\n\n";

        $config .= "# Activer le module de réécriture\n";
        $config .= "RewriteEngine On\n\n";

        $config .= "# Gérer les environnements via URL\n";
        $config .= "RewriteCond %{REQUEST_URI} ^/env/{$environment->getSubdomain()}(.*)$\n";
        $config .= "RewriteRule ^env/{$environment->getSubdomain()}(.*)$ $1 [E=APP_ENV_CODE:{$environment->getSubdomain()},E=APP_ORG_ID:{$environment->getOrganization()->getId()}]\n\n";

        $config .= "# Redirection pour les domaines personnalisés\n";
        if ($environment->getDomain()) {
            $config .= "RewriteCond %{HTTP_HOST} ^{$environment->getDomain()}$\n";
            $config .= "RewriteRule ^(.*)$ /env/{$environment->getSubdomain()}$1 [E=APP_ENV_CODE:{$environment->getSubdomain()},E=APP_ORG_ID:{$environment->getOrganization()->getId()}]\n\n";
        }

        $config .= "# Variables d'environnement\n";
        $config .= "SetEnv APP_ENV_CODE {$environment->getSubdomain()}\n";
        $config .= "SetEnv APP_ORG_ID {$environment->getOrganization()->getId()}\n";
        $config .= "SetEnv APP_ENV_TYPE {$environment->getType()}\n";

        if ($environment->getEnvironmentVariables()) {
            foreach ($environment->getEnvironmentVariables() as $key => $value) {
                $config .= "SetEnv {$key} {$value}\n";
            }
        }

        return $config;
    }

    /**
     * Génère la configuration Nginx
     */
    private function generateNginxConfig(Environment $environment): string
    {
        $baseDomain = $this->getBaseDomain();
        $domain = $environment->getDomain() ?: $environment->getSubdomain() . '.' . $baseDomain;

        $config = "server {\n";
        $config .= "    listen 80;\n";
        $config .= "    server_name {$domain};\n";
        $config .= "    root /var/www/mylocca/public;\n";
        $config .= "    \n";
        $config .= "    location / {\n";
        $config .= "        try_files \$uri \$uri/ /index.php\$is_args\$args;\n";
        $config .= "    }\n";
        $config .= "    \n";
        $config .= "    location ~ \\.php$ {\n";
        $config .= "        fastcgi_pass unix:/var/run/php/php8.2-fpm.sock;\n";
        $config .= "        fastcgi_index index.php;\n";
        $config .= "        include fastcgi_params;\n";
        $config .= "        fastcgi_param APP_SUBDOMAIN {$environment->getSubdomain()};\n";
        $config .= "        fastcgi_param APP_ORGANIZATION_ID {$environment->getOrganization()->getId()};\n";
        $config .= "    }\n";
        $config .= "}\n";

        if ($environment->isSslEnabled()) {
            $config .= "\nserver {\n";
            $config .= "    listen 443 ssl;\n";
            $config .= "    server_name {$domain};\n";
            $config .= "    root /var/www/mylocca/public;\n";
            $config .= "    \n";
            $config .= "    ssl_certificate /etc/ssl/certs/{$domain}.crt;\n";
            $config .= "    ssl_certificate_key /etc/ssl/private/{$domain}.key;\n";
            $config .= "    \n";
            $config .= "    location / {\n";
            $config .= "        try_files \$uri \$uri/ /index.php\$is_args\$args;\n";
            $config .= "    }\n";
            $config .= "}\n";
        }

        return $config;
    }

    /**
     * Configure l'infrastructure (DNS, Apache, etc.)
     */
    private function configureEnvironmentInfrastructure(Environment $environment): void
    {
        // Ici, vous pouvez intégrer avec des services comme :
        // - CloudFlare API pour DNS
        // - Let's Encrypt pour SSL
        // - Docker pour conteneurisation
        // - Kubernetes pour orchestration

        $this->logger->info('Configuration de l\'infrastructure', [
            'environment_id' => $environment->getId(),
            'subdomain' => $environment->getSubdomain()
        ]);

        // Simulation de la configuration
        // En production, vous appelleriez les APIs des services cloud
    }

    /**
     * Déploie une nouvelle version sur un environnement
     */
    public function deployEnvironment(Environment $environment, string $version): array
    {
        try {
            $this->logger->info('Déploiement d\'un environnement', [
                'environment_id' => $environment->getId(),
                'version' => $version
            ]);

            $environment->setStatus('DEPLOYING');
            $environment->setVersion($version);
            $this->entityManager->flush();

            // Simulation du déploiement
            // En production, vous utiliseriez des outils comme :
            // - Git hooks
            // - CI/CD pipelines
            // - Docker containers
            // - Kubernetes deployments

            $environment->setStatus('ACTIVE');
            $environment->setLastDeployedAt(new \DateTime());
            $environment->setUpdatedAt(new \DateTime());
            $this->entityManager->flush();

            return [
                'success' => true,
                'message' => 'Déploiement réussi',
                'version' => $version
            ];

        } catch (\Exception $e) {
            $environment->setStatus('ERROR');
            $environment->setDeploymentLog($e->getMessage());
            $this->entityManager->flush();

            return [
                'success' => false,
                'error' => $e->getMessage(),
                'message' => 'Erreur lors du déploiement'
            ];
        }
    }

    /**
     * Supprime un environnement
     */
    public function deleteEnvironment(Environment $environment): array
    {
        try {
            $this->logger->info('Suppression d\'un environnement', [
                'environment_id' => $environment->getId(),
                'subdomain' => $environment->getSubdomain()
            ]);

            // Supprimer les fichiers de configuration
            $envDir = $this->environmentsDir . '/' . $environment->getSubdomain();
            if ($this->filesystem->exists($envDir)) {
                $this->filesystem->remove($envDir);
            }

            // Supprimer l'entité
            $this->entityManager->remove($environment);
            $this->entityManager->flush();

            return [
                'success' => true,
                'message' => 'Environnement supprimé avec succès'
            ];

        } catch (\Exception $e) {
            return [
                'success' => false,
                'error' => $e->getMessage(),
                'message' => 'Erreur lors de la suppression'
            ];
        }
    }
}
