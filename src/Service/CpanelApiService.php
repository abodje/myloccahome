<?php

namespace App\Service;

use Psr\Log\LoggerInterface;
use Symfony\Component\HttpClient\HttpClient;
use Symfony\Contracts\HttpClient\HttpClientInterface;

/**
 * Service pour gérer les API cPanel (création sous-domaines, bases de données, etc.)
 */
class CpanelApiService
{
    private HttpClientInterface $client;
    private string $cpanelHost;
    private string $cpanelUsername;
    private string $cpanelToken;
    private int $cpanelPort;

    public function __construct(
        private LoggerInterface $logger,
        string $cpanelHost,
        string $cpanelUsername,
        string $cpanelToken,
        int $cpanelPort = 2083
    ) {
        $this->cpanelHost = $cpanelHost;
        $this->cpanelUsername = $cpanelUsername;
        $this->cpanelToken = $cpanelToken;
        $this->cpanelPort = $cpanelPort;

        $this->client = HttpClient::create([
            'verify_peer' => true,
            'verify_host' => true,
            'headers' => [
                'Authorization' => 'cpanel ' . $cpanelUsername . ':' . $cpanelToken,
            ],
        ]);
    }

    /**
     * Crée un sous-domaine
     */
    public function createSubdomain(string $subdomain, string $domain, string $rootDir): array
    {
        try {
            $this->logger->info("🌐 Création du sous-domaine: {$subdomain}.{$domain}");

            $response = $this->client->request('GET', $this->buildUrl('SubDomain', 'addsubdomain'), [
                'query' => [
                    'domain' => $subdomain,
                    'rootdomain' => $domain,
                    'dir' => $rootDir,
                ],
            ]);

            $data = $response->toArray();

            if (isset($data['status']) && $data['status'] == 1) {
                $this->logger->info("✅ Sous-domaine créé: {$subdomain}.{$domain}");
                return ['success' => true, 'data' => $data];
            }

            $this->logger->error("❌ Échec création sous-domaine", ['response' => $data]);
            return ['success' => false, 'error' => $data['errors'][0] ?? 'Unknown error'];

        } catch (\Exception $e) {
            $this->logger->error("❌ Erreur création sous-domaine: " . $e->getMessage());
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }

    /**
     * Supprime un sous-domaine
     */
    public function deleteSubdomain(string $subdomain, string $domain): array
    {
        try {
            $this->logger->info("🗑️  Suppression du sous-domaine: {$subdomain}.{$domain}");

            $response = $this->client->request('GET', $this->buildUrl('SubDomain', 'delsubdomain'), [
                'query' => [
                    'domain' => "{$subdomain}.{$domain}",
                ],
            ]);

            $data = $response->toArray();

            if (isset($data['status']) && $data['status'] == 1) {
                $this->logger->info("✅ Sous-domaine supprimé");
                return ['success' => true];
            }

            return ['success' => false, 'error' => $data['errors'][0] ?? 'Unknown error'];

        } catch (\Exception $e) {
            $this->logger->error("❌ Erreur suppression sous-domaine: " . $e->getMessage());
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }

    /**
     * Crée une base de données MySQL
     */
    public function createDatabase(string $databaseName): array
    {
        try {
            $this->logger->info("💾 Création de la base de données: {$databaseName}");

            $response = $this->client->request('GET', $this->buildUrl('Mysql', 'create_database'), [
                'query' => [
                    'name' => $databaseName,
                ],
            ]);

            $data = $response->toArray();

            if (isset($data['status']) && $data['status'] == 1) {
                $this->logger->info("✅ Base de données créée: {$this->cpanelUsername}_{$databaseName}");
                return ['success' => true, 'database' => "{$this->cpanelUsername}_{$databaseName}"];
            }

            return ['success' => false, 'error' => $data['errors'][0] ?? 'Unknown error'];

        } catch (\Exception $e) {
            $this->logger->error("❌ Erreur création BDD: " . $e->getMessage());
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }

    /**
     * Crée un utilisateur MySQL
     */
    public function createDatabaseUser(string $username, string $password): array
    {
        try {
            $this->logger->info("👤 Création de l'utilisateur MySQL: {$username}");

            $response = $this->client->request('GET', $this->buildUrl('Mysql', 'create_user'), [
                'query' => [
                    'name' => $username,
                    'password' => $password,
                ],
            ]);

            $data = $response->toArray();

            if (isset($data['status']) && $data['status'] == 1) {
                $this->logger->info("✅ Utilisateur MySQL créé: {$this->cpanelUsername}_{$username}");
                return ['success' => true, 'user' => "{$this->cpanelUsername}_{$username}"];
            }

            return ['success' => false, 'error' => $data['errors'][0] ?? 'Unknown error'];

        } catch (\Exception $e) {
            $this->logger->error("❌ Erreur création utilisateur MySQL: " . $e->getMessage());
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }

    /**
     * Associe un utilisateur à une base de données avec tous les privilèges
     */
    public function setDatabasePrivileges(string $username, string $databaseName): array
    {
        try {
            $this->logger->info("🔐 Attribution des privilèges: {$username} -> {$databaseName}");

            $fullUsername = "{$this->cpanelUsername}_{$username}";
            $fullDatabase = "{$this->cpanelUsername}_{$databaseName}";

            $response = $this->client->request('GET', $this->buildUrl('Mysql', 'set_privileges_on_database'), [
                'query' => [
                    'user' => $fullUsername,
                    'database' => $fullDatabase,
                    'privileges' => 'ALL PRIVILEGES',
                ],
            ]);

            $data = $response->toArray();

            if (isset($data['status']) && $data['status'] == 1) {
                $this->logger->info("✅ Privilèges attribués");
                return ['success' => true];
            }

            return ['success' => false, 'error' => $data['errors'][0] ?? 'Unknown error'];

        } catch (\Exception $e) {
            $this->logger->error("❌ Erreur attribution privilèges: " . $e->getMessage());
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }

    /**
     * Supprime une base de données
     */
    public function deleteDatabase(string $databaseName): array
    {
        try {
            $this->logger->info("🗑️  Suppression de la base de données: {$databaseName}");

            $fullDatabase = "{$this->cpanelUsername}_{$databaseName}";

            $response = $this->client->request('GET', $this->buildUrl('Mysql', 'delete_database'), [
                'query' => [
                    'name' => $fullDatabase,
                ],
            ]);

            $data = $response->toArray();

            if (isset($data['status']) && $data['status'] == 1) {
                $this->logger->info("✅ Base de données supprimée");
                return ['success' => true];
            }

            return ['success' => false, 'error' => $data['errors'][0] ?? 'Unknown error'];

        } catch (\Exception $e) {
            $this->logger->error("❌ Erreur suppression BDD: " . $e->getMessage());
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }

    /**
     * Supprime un utilisateur MySQL
     */
    public function deleteDatabaseUser(string $username): array
    {
        try {
            $this->logger->info("🗑️  Suppression de l'utilisateur MySQL: {$username}");

            $fullUsername = "{$this->cpanelUsername}_{$username}";

            $response = $this->client->request('GET', $this->buildUrl('Mysql', 'delete_user'), [
                'query' => [
                    'name' => $fullUsername,
                ],
            ]);

            $data = $response->toArray();

            if (isset($data['status']) && $data['status'] == 1) {
                $this->logger->info("✅ Utilisateur MySQL supprimé");
                return ['success' => true];
            }

            return ['success' => false, 'error' => $data['errors'][0] ?? 'Unknown error'];

        } catch (\Exception $e) {
            $this->logger->error("❌ Erreur suppression utilisateur MySQL: " . $e->getMessage());
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }

    /**
     * Construit l'URL de l'API cPanel
     */
    private function buildUrl(string $module, string $function): string
    {
        return sprintf(
            'https://%s:%d/execute/%s/%s',
            $this->cpanelHost,
            $this->cpanelPort,
            $module,
            $function
        );
    }

    /**
     * Crée un environnement de démo complet
     */
    public function createDemoEnvironment(string $demoId): array
    {
        $results = [
            'success' => false,
            'subdomain' => null,
            'database' => null,
            'db_user' => null,
            'db_password' => null,
            'errors' => []
        ];

        // Générer les noms
        $subdomain = "demo-{$demoId}";
        $domain = "lokapro.tech"; // À configurer
        $rootDir = "/home/{$this->cpanelUsername}/demos/{$subdomain}";
        $dbName = "demo_{$demoId}";
        $dbUser = "demo_{$demoId}";
        $dbPassword = bin2hex(random_bytes(16)); // Mot de passe aléatoire sécurisé

        // 1. Créer le sous-domaine
        $subdomainResult = $this->createSubdomain($subdomain, $domain, $rootDir);
        if (!$subdomainResult['success']) {
            $results['errors'][] = "Sous-domaine: " . $subdomainResult['error'];
            return $results;
        }
        $results['subdomain'] = "{$subdomain}.{$domain}";

        // 2. Créer la base de données
        $dbResult = $this->createDatabase($dbName);
        if (!$dbResult['success']) {
            $results['errors'][] = "Base de données: " . $dbResult['error'];
            $this->deleteSubdomain($subdomain, $domain); // Rollback
            return $results;
        }
        $results['database'] = $dbResult['database'];

        // 3. Créer l'utilisateur
        $userResult = $this->createDatabaseUser($dbUser, $dbPassword);
        if (!$userResult['success']) {
            $results['errors'][] = "Utilisateur BDD: " . $userResult['error'];
            $this->deleteDatabase($dbName); // Rollback
            $this->deleteSubdomain($subdomain, $domain);
            return $results;
        }
        $results['db_user'] = $userResult['user'];
        $results['db_password'] = $dbPassword;

        // 4. Attribuer les privilèges
        $privResult = $this->setDatabasePrivileges($dbUser, $dbName);
        if (!$privResult['success']) {
            $results['errors'][] = "Privilèges: " . $privResult['error'];
            $this->deleteDatabaseUser($dbUser); // Rollback
            $this->deleteDatabase($dbName);
            $this->deleteSubdomain($subdomain, $domain);
            return $results;
        }

        $results['success'] = true;
        $this->logger->info("🎉 Environnement de démo créé avec succès", $results);

        return $results;
    }

    /**
     * Supprime un environnement de démo complet
     */
    public function deleteDemoEnvironment(string $demoId): array
    {
        $subdomain = "demo-{$demoId}";
        $domain = "lokapro.tech";
        $dbName = "demo_{$demoId}";
        $dbUser = "demo_{$demoId}";

        $errors = [];

        // Supprimer l'utilisateur MySQL
        $userResult = $this->deleteDatabaseUser($dbUser);
        if (!$userResult['success']) {
            $errors[] = "Utilisateur: " . $userResult['error'];
        }

        // Supprimer la base de données
        $dbResult = $this->deleteDatabase($dbName);
        if (!$dbResult['success']) {
            $errors[] = "Base de données: " . $dbResult['error'];
        }

        // Supprimer le sous-domaine
        $subdomainResult = $this->deleteSubdomain($subdomain, $domain);
        if (!$subdomainResult['success']) {
            $errors[] = "Sous-domaine: " . $subdomainResult['error'];
        }

        return [
            'success' => empty($errors),
            'errors' => $errors
        ];
    }
}
