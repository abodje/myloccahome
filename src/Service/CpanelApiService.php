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

            // L'API cPanel UAPI2 utilise 'delsubdomain' mais UAPI utilise un autre format
            // Essayons avec le nom complet du sous-domaine
            $fullDomain = "{$subdomain}.{$domain}";

            $response = $this->client->request('GET', $this->buildUrl('SubDomain', 'delete'), [
                'query' => [
                    'domain' => $fullDomain,
                    'discard' => 1, // Supprimer aussi le répertoire
                ],
            ]);

            $data = $response->toArray();

            if (isset($data['status']) && $data['status'] == 1) {
                $this->logger->info("✅ Sous-domaine supprimé");
                return ['success' => true];
            }

            // Si erreur, essayer avec l'ancienne API UAPI2
            $this->logger->warning("Tentative avec API alternative...");

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
            // cPanel attend le nom AVEC le préfixe utilisateur
            $fullDatabaseName = "{$this->cpanelUsername}_{$databaseName}";

            $this->logger->info("💾 Création de la base de données: {$fullDatabaseName}");

            $response = $this->client->request('GET', $this->buildUrl('Mysql', 'create_database'), [
                'query' => [
                    'name' => $fullDatabaseName,
                ],
            ]);

            $data = $response->toArray();

            if (isset($data['status']) && $data['status'] == 1) {
                $this->logger->info("✅ Base de données créée: {$fullDatabaseName}");
                return ['success' => true, 'database' => $fullDatabaseName];
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
            // cPanel attend le nom AVEC le préfixe utilisateur
            $fullUsername = "{$this->cpanelUsername}_{$username}";

            $this->logger->info("👤 Création de l'utilisateur MySQL: {$fullUsername}");

            $response = $this->client->request('GET', $this->buildUrl('Mysql', 'create_user'), [
                'query' => [
                    'name' => $fullUsername,
                    'password' => $password,
                ],
            ]);

            $data = $response->toArray();

            if (isset($data['status']) && $data['status'] == 1) {
                $this->logger->info("✅ Utilisateur MySQL créé: {$fullUsername}");
                return ['success' => true, 'user' => $fullUsername];
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
     * Crée un fichier .htaccess de redirection dans le sous-domaine
     */
    private function createRedirectionFile(string $rootDir, string $subdomain, string $domain): array
    {
        try {
            $this->logger->info("📄 Création du fichier de redirection dans: {$rootDir}");

            // Contenu du fichier index.php qui redirige vers l'app principale
            $indexContent = <<<'PHP'
<?php
// Redirection automatique vers l'application principale
// en passant le sous-domaine comme paramètre

$subdomain = '<?= SUBDOMAIN ?>';
$mainAppUrl = 'https://lokapro.tech/demo/' . $subdomain;

// Rediriger vers l'application principale
header('Location: ' . $mainAppUrl);
exit;
PHP;

            // Remplacer le placeholder
            $indexContent = str_replace('<?= SUBDOMAIN ?>', $subdomain, $indexContent);

            // Créer le fichier via l'API Fileman
            $response = $this->client->request('GET', $this->buildUrl('Fileman', 'save_file_content'), [
                'query' => [
                    'dir' => $rootDir,
                    'file' => 'index.php',
                    'content' => base64_encode($indexContent),
                    'encoding' => 'base64',
                ],
            ]);

            $data = $response->toArray();

            if (isset($data['status']) && $data['status'] == 1) {
                $this->logger->info("✅ Fichier de redirection créé");
                return ['success' => true];
            }

            $this->logger->warning("⚠️ Impossible de créer le fichier de redirection automatiquement");
            return ['success' => false, 'error' => $data['errors'][0] ?? 'Unknown error'];

        } catch (\Exception $e) {
            $this->logger->error("❌ Erreur création fichier redirection: " . $e->getMessage());
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }

    /**
     * Crée un environnement de démo complet (sans sous-domaine cPanel)
     * Utilise uniquement le routing Symfony via /demo/{code}
     */
    public function createDemoEnvironment(string $demoId): array
    {
        $results = [
            'success' => false,
            'subdomain' => null,
            'database' => null,
            'db_user' => null,
            'db_password' => null,
            'errors' => [],
            'message' => ''
        ];

        // Nettoyer le demoId pour éviter les caractères invalides
        $cleanDemoId = preg_replace('/[^a-z0-9]/i', '', $demoId);

        // Pour les noms MySQL, on doit respecter les limites strictes de cPanel:
        // - Database name: max 64 chars (lokaprot_ = 9 chars, reste 55)
        // - User name: max 16 chars (lokaprot_ = 9 chars, reste 7!)
        //
        // Utilisons un hash court basé sur le demoId pour garantir l'unicité
        $hash = substr(md5($demoId), 0, 6); // 6 caractères hexadécimaux

        // Générer les noms
        $subdomain = "demo-{$cleanDemoId}";

        // Noms courts pour MySQL
        $dbName = "demo_{$hash}";  // lokaprot_demo_abc123 = 21 chars
        $dbUser = "d_{$hash}";      // lokaprot_d_abc123 = 16 chars (limite exacte!)
        $dbPassword = bin2hex(random_bytes(12)); // 24 caractères

        $this->logger->info("🚀 Création environnement démo (BDD uniquement)", [
            'demoId' => $demoId,
            'database' => "{$this->cpanelUsername}_{$dbName}",
            'user' => "{$this->cpanelUsername}_{$dbUser}"
        ]);

        // PAS de création de sous-domaine cPanel (utilise routing Symfony)
        $results['subdomain'] = $subdomain; // Juste le code, pas un vrai sous-domaine

        // 1. Créer la base de données
        $dbResult = $this->createDatabase($dbName);
        if (!$dbResult['success']) {
            $results['errors'][] = "Base de données: " . $dbResult['error'];
            $results['message'] = "Échec création base de données: " . $dbResult['error'];
            return $results;
        }
        $results['database'] = $dbResult['database'];

        // 2. Créer l'utilisateur
        $userResult = $this->createDatabaseUser($dbUser, $dbPassword);
        if (!$userResult['success']) {
            $results['errors'][] = "Utilisateur BDD: " . $userResult['error'];
            $results['message'] = "Échec création utilisateur: " . $userResult['error'];
            $this->deleteDatabase($dbName); // Rollback
            return $results;
        }
        $results['db_user'] = $userResult['user'];
        $results['db_password'] = $dbPassword;

        // 3. Attribuer les privilèges
        $privResult = $this->setDatabasePrivileges($dbUser, $dbName);
        if (!$privResult['success']) {
            $results['errors'][] = "Privilèges: " . $privResult['error'];
            $results['message'] = "Échec attribution privilèges: " . $privResult['error'];
            $this->deleteDatabaseUser($dbUser); // Rollback
            $this->deleteDatabase($dbName);
            return $results;
        }

        $results['success'] = true;
        $results['message'] = "Environnement créé avec succès (accès via URL principale)";
        $this->logger->info("🎉 Environnement de démo créé avec succès", $results);

        return $results;
    }

    /**
     * Supprime un environnement de démo complet
     */
    public function deleteDemoEnvironment(string $demoId): array
    {
        // Nettoyer le demoId comme dans createDemoEnvironment
        $cleanDemoId = preg_replace('/[^a-z0-9]/i', '', $demoId);
        $cleanDemoId = substr($cleanDemoId, 0, 10);

        $subdomain = "demo-{$cleanDemoId}";
        $domain = "lokapro.tech";
        $dbName = "demo_{$cleanDemoId}";
        $dbUser = "d_{$cleanDemoId}";

        $errors = [];
        $success = 0;

        // Supprimer l'utilisateur MySQL
        $userResult = $this->deleteDatabaseUser($dbUser);
        if (!$userResult['success']) {
            $errors[] = "Utilisateur: " . $userResult['error'];
            $this->logger->warning("⚠️ Échec suppression utilisateur (peut ne pas exister)");
        } else {
            $success++;
        }

        // Supprimer la base de données
        $dbResult = $this->deleteDatabase($dbName);
        if (!$dbResult['success']) {
            $errors[] = "Base de données: " . $dbResult['error'];
            $this->logger->warning("⚠️ Échec suppression BDD (peut ne pas exister)");
        } else {
            $success++;
        }

        // Supprimer le sous-domaine
        $subdomainResult = $this->deleteSubdomain($subdomain, $domain);
        if (!$subdomainResult['success']) {
            $errors[] = "Sous-domaine: " . $subdomainResult['error'];
            $this->logger->warning("⚠️ Échec suppression sous-domaine");
        } else {
            $success++;
        }

        $isSuccess = $success > 0; // Au moins une suppression réussie

        return [
            'success' => $isSuccess,
            'errors' => $errors,
            'message' => $isSuccess
                ? "Environnement supprimé ({$success}/3 ressources)"
                : "Échec complet de la suppression"
        ];
    }
}
