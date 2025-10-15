<?php

namespace App\Service;

use App\Entity\User;
use App\Entity\Organization;
use App\Entity\Company;
use App\Entity\Property;
use App\Entity\Tenant;
use App\Entity\Lease;
use App\Entity\Payment;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;

class DemoEnvironmentService
{
    private EntityManagerInterface $entityManager;
    private Filesystem $filesystem;
    private ParameterBagInterface $params;
    private string $demoBaseUrl;
    private string $demoDataDir;

    public function __construct(
        EntityManagerInterface $entityManager,
        Filesystem $filesystem,
        ParameterBagInterface $params
    ) {
        $this->entityManager = $entityManager;
        $this->filesystem = $filesystem;
        $this->params = $params;
        $this->demoBaseUrl = $_ENV['DEMO_BASE_URL'] ?? 'demo.mylocca.local';
        $this->demoDataDir = $this->params->get('kernel.project_dir') . '/demo_data';

        // Créer le dossier de données de démo s'il n'existe pas
        if (!$this->filesystem->exists($this->demoDataDir)) {
            $this->filesystem->mkdir($this->demoDataDir);
        }
    }

    /**
     * Crée un environnement de démo complet pour un utilisateur
     */
    public function createDemoEnvironment(User $user): array
    {
        $subdomain = $this->generateSubdomain($user);
        $demoUrl = "https://{$subdomain}.{$this->demoBaseUrl}";

        try {
            // 1. Créer l'organisation de démo
            $organization = $this->createDemoOrganization($user, $subdomain);

            // 2. Créer la société de démo
            $company = $this->createDemoCompany($organization, $subdomain);

            // 3. Assigner l'utilisateur à l'organisation et société
            $this->assignUserToOrganization($user, $organization, $company);

            // 4. Créer des données de démo
            $demoData = $this->createDemoData($organization, $company);

            // 5. Configurer l'environnement
            $this->configureDemoEnvironment($subdomain, $demoUrl);

            return [
                'success' => true,
                'subdomain' => $subdomain,
                'demo_url' => $demoUrl,
                'organization' => $organization,
                'company' => $company,
                'demo_data' => $demoData,
                'message' => "Environnement de démo créé avec succès ! Accédez à votre démo : {$demoUrl}"
            ];

        } catch (\Exception $e) {
            return [
                'success' => false,
                'error' => $e->getMessage(),
                'message' => 'Erreur lors de la création de l\'environnement de démo'
            ];
        }
    }

    /**
     * Génère un sous-domaine unique basé sur l'utilisateur
     */
    private function generateSubdomain(User $user): string
    {
        // Utiliser l'email ou générer un ID unique
        $base = strtolower(preg_replace('/[^a-zA-Z0-9]/', '', $user->getEmail()));
        $base = substr($base, 0, 10); // Limiter à 10 caractères

        // Ajouter un suffixe pour éviter les conflits
        $suffix = substr(md5($user->getEmail() . time()), 0, 4);

        return "{$base}-{$suffix}";
    }

    /**
     * Crée une organisation de démo
     */
    private function createDemoOrganization(User $user, string $subdomain): Organization
    {
        $organization = new Organization();
        $organization->setName("Organisation Démo - {$user->getFirstName()} {$user->getLastName()}");
        $organization->setDescription("Environnement de démo pour tester MYLOCCA");
        $organization->setSubdomain($subdomain);
        $organization->setIsDemo(true);
        $organization->setCreatedAt(new \DateTime());
        $organization->setStatus('active');

        $this->entityManager->persist($organization);
        $this->entityManager->flush();

        return $organization;
    }

    /**
     * Crée une société de démo
     */
    private function createDemoCompany(Organization $organization, string $subdomain): Company
    {
        $company = new Company();
        $company->setName("Société Démo - {$subdomain}");
        $company->setDescription("Société de démo pour l'organisation {$organization->getName()}");
        $company->setOrganization($organization);
        $company->setIsDemo(true);
        $company->setCreatedAt(new \DateTime());
        $company->setStatus('active');

        $this->entityManager->persist($company);
        $this->entityManager->flush();

        return $company;
    }

    /**
     * Assigne l'utilisateur à l'organisation et société
     */
    private function assignUserToOrganization(User $user, Organization $organization, Company $company): void
    {
        $user->setOrganization($organization);
        $user->setCompany($company);
        $user->setRoles(['ROLE_ADMIN']); // Admin de son environnement de démo

        $this->entityManager->persist($user);
        $this->entityManager->flush();
    }

    /**
     * Crée des données de démo réalistes
     */
    private function createDemoData(Organization $organization, Company $company): array
    {
        $demoData = [];

        // 1. Créer des propriétés de démo
        $properties = $this->createDemoProperties($organization, $company);
        $demoData['properties'] = count($properties);

        // 2. Créer des locataires de démo
        $tenants = $this->createDemoTenants($organization, $company);
        $demoData['tenants'] = count($tenants);

        // 3. Créer des baux de démo
        $leases = $this->createDemoLeases($properties, $tenants, $organization, $company);
        $demoData['leases'] = count($leases);

        // 4. Créer des paiements de démo
        $payments = $this->createDemoPayments($leases, $organization, $company);
        $demoData['payments'] = count($payments);

        return $demoData;
    }

    /**
     * Crée des propriétés de démo
     */
    private function createDemoProperties(Organization $organization, Company $company): array
    {
        $properties = [];
        $addresses = [
            '123 Rue de la Paix, Paris 75001',
            '456 Avenue des Champs, Paris 75008',
            '789 Boulevard Saint-Germain, Paris 75006',
            '321 Rue de Rivoli, Paris 75001',
            '654 Place de la République, Paris 75011'
        ];

        $rents = [1200, 1500, 1800, 2000, 2200];
        $surfaces = [45, 60, 75, 90, 110];

        for ($i = 0; $i < 5; $i++) {
            $property = new Property();
            $property->setAddress($addresses[$i]);
            $property->setRentAmount($rents[$i]);
            $property->setSurface($surfaces[$i]);
            $property->setType('Appartement');
            $property->setDescription("Appartement de démo - {$surfaces[$i]}m²");
            $property->setOrganization($organization);
            $property->setCompany($company);
            $property->setIsDemo(true);
            $property->setCreatedAt(new \DateTime());
            $property->setStatus('available');

            $this->entityManager->persist($property);
            $properties[] = $property;
        }

        $this->entityManager->flush();
        return $properties;
    }

    /**
     * Crée des locataires de démo
     */
    private function createDemoTenants(Organization $organization, Company $company): array
    {
        $tenants = [];
        $tenantData = [
            ['Jean', 'Dupont', 'jean.dupont@demo.com', '0123456789'],
            ['Marie', 'Martin', 'marie.martin@demo.com', '0123456788'],
            ['Pierre', 'Durand', 'pierre.durand@demo.com', '0123456787'],
            ['Sophie', 'Bernard', 'sophie.bernard@demo.com', '0123456786'],
            ['Luc', 'Moreau', 'luc.moreau@demo.com', '0123456785']
        ];

        foreach ($tenantData as $data) {
            $tenant = new Tenant();
            $tenant->setFirstName($data[0]);
            $tenant->setLastName($data[1]);
            $tenant->setEmail($data[2]);
            $tenant->setPhone($data[3]);
            $tenant->setOrganization($organization);
            $tenant->setCompany($company);
            $tenant->setIsDemo(true);
            $tenant->setCreatedAt(new \DateTime());
            $tenant->setStatus('active');

            $this->entityManager->persist($tenant);
            $tenants[] = $tenant;
        }

        $this->entityManager->flush();
        return $tenants;
    }

    /**
     * Crée des baux de démo
     */
    private function createDemoLeases(array $properties, array $tenants, Organization $organization, Company $company): array
    {
        $leases = [];

        for ($i = 0; $i < min(4, count($properties)); $i++) {
            $lease = new Lease();
            $lease->setProperty($properties[$i]);
            $lease->setTenant($tenants[$i]);
            $lease->setMonthlyRent($properties[$i]->getRentAmount());
            $lease->setSecurityDeposit($properties[$i]->getRentAmount() * 2);
            $lease->setStartDate(new \DateTime());
            $lease->setEndDate((new \DateTime())->modify('+12 months'));
            $lease->setStatus('active');
            $lease->setOrganization($organization);
            $lease->setCompany($company);
            $lease->setIsDemo(true);
            $lease->setCreatedAt(new \DateTime());

            $this->entityManager->persist($lease);
            $leases[] = $lease;
        }

        $this->entityManager->flush();
        return $leases;
    }

    /**
     * Crée des paiements de démo
     */
    private function createDemoPayments(array $leases, Organization $organization, Company $company): array
    {
        $payments = [];

        foreach ($leases as $lease) {
            // Créer 3 paiements de démo (mois précédents)
            for ($i = 1; $i <= 3; $i++) {
                $payment = new Payment();
                $payment->setLease($lease);
                $payment->setAmount($lease->getMonthlyRent());
                $payment->setDueDate((new \DateTime())->modify("-{$i} months")->modify('+1 day'));
                $payment->setPaidDate((new \DateTime())->modify("-{$i} months")->modify('+2 days'));
                $payment->setStatus('Payé');
                $payment->setType('Loyer');
                $payment->setPaymentMethod('Virement');
                $payment->setReference('DEMO-' . str_pad($i, 3, '0', STR_PAD_LEFT));
                $payment->setOrganization($organization);
                $payment->setCompany($company);
                $payment->setIsDemo(true);
                $payment->setCreatedAt(new \DateTime());

                $this->entityManager->persist($payment);
                $payments[] = $payment;
            }
        }

        $this->entityManager->flush();
        return $payments;
    }

    /**
     * Configure l'environnement de démo (DNS, Apache, etc.)
     */
    private function configureDemoEnvironment(string $subdomain, string $demoUrl): void
    {
        // 1. Créer un fichier de configuration Apache
        $this->createApacheConfig($subdomain);

        // 2. Créer un fichier de configuration DNS (pour développement local)
        $this->createDNSConfig($subdomain);

        // 3. Créer un fichier de configuration pour le reverse proxy
        $this->createReverseProxyConfig($subdomain);

        // 4. Log de création
        $this->logDemoEnvironmentCreation($subdomain, $demoUrl);
    }

    /**
     * Crée la configuration Apache pour le sous-domaine
     */
    private function createApacheConfig(string $subdomain): void
    {
        $configContent = <<<EOF
<VirtualHost *:80>
    ServerName {$subdomain}.{$this->demoBaseUrl}
    DocumentRoot "C:/wamp64/mylocca/public"

    <Directory "C:/wamp64/mylocca/public">
        AllowOverride All
        Require all granted
    </Directory>

    # Configuration spécifique pour l'environnement de démo
    SetEnv APP_ENV demo
    SetEnv DEMO_SUBDOMAIN {$subdomain}

    # Logs spécifiques
    ErrorLog "C:/wamp64/logs/{$subdomain}_error.log"
    CustomLog "C:/wamp64/logs/{$subdomain}_access.log" combined
</VirtualHost>
EOF;

        $configFile = "C:/wamp64/bin/apache/apache2.4.54/conf/extra/{$subdomain}.conf";
        file_put_contents($configFile, $configContent);

        // Ajouter l'include dans httpd.conf
        $this->addIncludeToApacheConfig($subdomain);
    }

    /**
     * Ajoute l'include dans la configuration Apache principale
     */
    private function addIncludeToApacheConfig(string $subdomain): void
    {
        $httpdConf = "C:/wamp64/bin/apache/apache2.4.54/conf/httpd.conf";
        $includeLine = "Include conf/extra/{$subdomain}.conf";

        if (file_exists($httpdConf)) {
            $content = file_get_contents($httpdConf);
            if (strpos($content, $includeLine) === false) {
                $content .= "\n# Configuration sous-domaine de démo\n{$includeLine}\n";
                file_put_contents($httpdConf, $content);
            }
        }
    }

    /**
     * Crée la configuration DNS pour le développement local
     */
    private function createDNSConfig(string $subdomain): void
    {
        $hostsFile = "C:/Windows/System32/drivers/etc/hosts";
        $dnsEntry = "127.0.0.1 {$subdomain}.{$this->demoBaseUrl}";

        if (file_exists($hostsFile)) {
            $content = file_get_contents($hostsFile);
            if (strpos($content, $dnsEntry) === false) {
                $content .= "\n# MYLOCCA Demo Environment\n{$dnsEntry}\n";
                file_put_contents($hostsFile, $content);
            }
        }
    }

    /**
     * Crée la configuration du reverse proxy
     */
    private function createReverseProxyConfig(string $subdomain): void
    {
        $proxyConfig = [
            'subdomain' => $subdomain,
            'target_url' => 'http://127.0.0.1:8000',
            'created_at' => date('Y-m-d H:i:s'),
            'status' => 'active'
        ];

        $configFile = $this->demoDataDir . "/proxy_config_{$subdomain}.json";
        file_put_contents($configFile, json_encode($proxyConfig, JSON_PRETTY_PRINT));
    }

    /**
     * Log de création de l'environnement de démo
     */
    private function logDemoEnvironmentCreation(string $subdomain, string $demoUrl): void
    {
        $logEntry = [
            'timestamp' => date('Y-m-d H:i:s'),
            'subdomain' => $subdomain,
            'demo_url' => $demoUrl,
            'action' => 'demo_environment_created'
        ];

        $logFile = $this->demoDataDir . '/demo_environments.log';
        file_put_contents($logFile, json_encode($logEntry) . "\n", FILE_APPEND);
    }

    /**
     * Supprime un environnement de démo
     */
    public function deleteDemoEnvironment(string $subdomain): bool
    {
        try {
            // 1. Supprimer les données de la base
            $this->deleteDemoData($subdomain);

            // 2. Supprimer les fichiers de configuration
            $this->deleteConfigurationFiles($subdomain);

            // 3. Log de suppression
            $this->logDemoEnvironmentDeletion($subdomain);

            return true;
        } catch (\Exception $e) {
            return false;
        }
    }

    /**
     * Supprime les données de démo de la base
     */
    private function deleteDemoData(string $subdomain): void
    {
        // Supprimer les paiements de démo
        $this->entityManager->createQueryBuilder()
            ->delete('App\Entity\Payment', 'p')
            ->where('p.isDemo = true')
            ->andWhere('p.organization IN (SELECT o FROM App\Entity\Organization o WHERE o.subdomain = :subdomain)')
            ->setParameter('subdomain', $subdomain)
            ->getQuery()
            ->execute();

        // Supprimer les baux de démo
        $this->entityManager->createQueryBuilder()
            ->delete('App\Entity\Lease', 'l')
            ->where('l.isDemo = true')
            ->andWhere('l.organization IN (SELECT o FROM App\Entity\Organization o WHERE o.subdomain = :subdomain)')
            ->setParameter('subdomain', $subdomain)
            ->getQuery()
            ->execute();

        // Supprimer les locataires de démo
        $this->entityManager->createQueryBuilder()
            ->delete('App\Entity\Tenant', 't')
            ->where('t.isDemo = true')
            ->andWhere('t.organization IN (SELECT o FROM App\Entity\Organization o WHERE o.subdomain = :subdomain)')
            ->setParameter('subdomain', $subdomain)
            ->getQuery()
            ->execute();

        // Supprimer les propriétés de démo
        $this->entityManager->createQueryBuilder()
            ->delete('App\Entity\Property', 'p')
            ->where('p.isDemo = true')
            ->andWhere('p.organization IN (SELECT o FROM App\Entity\Organization o WHERE o.subdomain = :subdomain)')
            ->setParameter('subdomain', $subdomain)
            ->getQuery()
            ->execute();

        // Supprimer les sociétés de démo
        $this->entityManager->createQueryBuilder()
            ->delete('App\Entity\Company', 'c')
            ->where('c.isDemo = true')
            ->andWhere('c.organization IN (SELECT o FROM App\Entity\Organization o WHERE o.subdomain = :subdomain)')
            ->setParameter('subdomain', $subdomain)
            ->getQuery()
            ->execute();

        // Supprimer l'organisation de démo
        $this->entityManager->createQueryBuilder()
            ->delete('App\Entity\Organization', 'o')
            ->where('o.isDemo = true')
            ->andWhere('o.subdomain = :subdomain')
            ->setParameter('subdomain', $subdomain)
            ->getQuery()
            ->execute();
    }

    /**
     * Supprime les fichiers de configuration
     */
    private function deleteConfigurationFiles(string $subdomain): void
    {
        // Supprimer la configuration Apache
        $apacheConfig = "C:/wamp64/bin/apache/apache2.4.54/conf/extra/{$subdomain}.conf";
        if (file_exists($apacheConfig)) {
            unlink($apacheConfig);
        }

        // Supprimer la configuration proxy
        $proxyConfig = $this->demoDataDir . "/proxy_config_{$subdomain}.json";
        if (file_exists($proxyConfig)) {
            unlink($proxyConfig);
        }
    }

    /**
     * Log de suppression de l'environnement de démo
     */
    private function logDemoEnvironmentDeletion(string $subdomain): void
    {
        $logEntry = [
            'timestamp' => date('Y-m-d H:i:s'),
            'subdomain' => $subdomain,
            'action' => 'demo_environment_deleted'
        ];

        $logFile = $this->demoDataDir . '/demo_environments.log';
        file_put_contents($logFile, json_encode($logEntry) . "\n", FILE_APPEND);
    }

    /**
     * Liste tous les environnements de démo
     */
    public function listDemoEnvironments(): array
    {
        $organizations = $this->entityManager->getRepository(Organization::class)
            ->findBy(['isDemo' => true]);

        $environments = [];
        foreach ($organizations as $org) {
            $environments[] = [
                'subdomain' => $org->getSubdomain(),
                'name' => $org->getName(),
                'created_at' => $org->getCreatedAt(),
                'demo_url' => "https://{$org->getSubdomain()}.{$this->demoBaseUrl}",
                'status' => $org->getStatus()
            ];
        }

        return $environments;
    }
}
