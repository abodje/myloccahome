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
use Symfony\Component\String\Slugger\SluggerInterface;
use Symfony\Component\HttpFoundation\RequestStack;
use Psr\Log\LoggerInterface;

class DemoEnvironmentService
{
    private EntityManagerInterface $entityManager;
    private Filesystem $filesystem;
    private ParameterBagInterface $params;
    private SluggerInterface $slugger;
    private RequestStack $requestStack;
    private LoggerInterface $logger;
    private string $demoBaseUrl;
    private string $demoDataDir;

    public function __construct(
        EntityManagerInterface $entityManager,
        Filesystem $filesystem,
        ParameterBagInterface $params,
        SluggerInterface $slugger,
        RequestStack $requestStack,
        LoggerInterface $logger
    ) {
        $this->entityManager = $entityManager;
        $this->filesystem = $filesystem;
        $this->params = $params;
        $this->slugger = $slugger;
        $this->requestStack = $requestStack;
        $this->logger = $logger;
        $this->demoBaseUrl = $this->getCurrentDomain();
        $this->demoDataDir = $this->params->get('kernel.project_dir') . '/demo_data';

        // Créer le dossier de données de démo s'il n'existe pas
        if (!$this->filesystem->exists($this->demoDataDir)) {
            $this->filesystem->mkdir($this->demoDataDir);
        }
    }

    /**
     * Obtient le domaine actuel de manière dynamique
     */
    private function getCurrentDomain(): string
    {
        // Essayer d'obtenir le domaine depuis la requête actuelle
        $request = $this->requestStack->getCurrentRequest();

        if ($request) {
            $host = $request->getHost();

            // Si on est sur un sous-domaine de démo (ex: abc.demo.mylocca.local),
            // retourner le domaine de base (demo.mylocca.local)
            if (preg_match('/^[^.]+\.(demo\..+)$/', $host, $matches)) {
                return $matches[1];
            }

            // Si on est sur le domaine principal, retourner demo.{domain}
            if (preg_match('/^(mylocca\..+)$/', $host, $matches)) {
                return 'demo.' . $matches[1];
            }

            // Pour hébergement partagé : détecter le domaine de production
            if (preg_match('/^([^.]+\.(com|fr|org|net|be|ch|eu|tech|io))$/', $host, $matches)) {
                return 'demo.' . $matches[1];
            }

            // Sinon retourner demo.{host}
            return 'demo.' . $host;
        }

        // Fallback sur la variable d'environnement ou valeur par défaut
        return $_ENV['DEMO_BASE_URL'] ?? 'demo.mylocca.local';
    }

    /**
     * Crée un environnement de démo complet pour un utilisateur
     */
    public function createDemoEnvironment(User $user): array
    {
        // Vérifier si l'EntityManager est fermé et le rouvrir si nécessaire
        if (!$this->entityManager->isOpen()) {
            // Pour recréer un EntityManager fermé, on doit utiliser le service factory
            // ou simplement continuer avec un nouveau EntityManager
            // Dans ce cas, on va simplement continuer car Doctrine gère automatiquement
            // la reconnexion si nécessaire
        }

        // Démarrer une transaction pour assurer la cohérence
        $this->entityManager->beginTransaction();

        try {
            // Vérifier d'abord si l'utilisateur a déjà une organisation démo
            $existingOrg = $this->entityManager->getRepository(\App\Entity\Organization::class)
                ->createQueryBuilder('o')
                ->where('o.isDemo = :demo')
                ->andWhere('o.name LIKE :userName')
                ->setParameter('demo', true)
                ->setParameter('userName', '%' . $user->getFullName() . '%')
                ->getQuery()
                ->getOneOrNullResult();

            if ($existingOrg) {
                $this->entityManager->rollback();
                return [
                    'success' => false,
                    'error' => 'Vous avez déjà une organisation démo existante',
                    'message' => 'Vous avez déjà une organisation démo. Supprimez-la d\'abord pour en créer une nouvelle.'
                ];
            }

            $subdomain = $this->generateSubdomain($user);
            $demoUrl = "https://{$subdomain}.{$this->demoBaseUrl}";

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

            // Valider la transaction
            $this->entityManager->commit();

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
            // Annuler la transaction en cas d'erreur
            if ($this->entityManager->getConnection()->isTransactionActive()) {
                $this->entityManager->rollback();
            }

            // Log l'erreur complète
            error_log('Erreur DemoEnvironmentService: ' . $e->getMessage());
            error_log('Stack trace: ' . $e->getTraceAsString());

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
        $organization->setSlug($this->slugger->slug($organization->getName())->lower()); // Ajouter le slug
        $organization->setDescription("Environnement de démo pour tester LOKAPRO");
        $organization->setSubdomain($subdomain);
        $organization->setIsDemo(true);
        $organization->setCreatedAt(new \DateTime());
        $organization->setStatus('TRIAL'); // Utiliser le statut correct
        $organization->setIsActive(true);

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
        $company->setStatus('ACTIVE'); // Utiliser le statut correct
        $company->setIsHeadquarter(true); // C'est le siège social

        $this->entityManager->persist($company);
        $this->entityManager->flush();

        return $company;
    }

    /**
     * Assigne l'utilisateur à l'organisation et société
     */
    private function assignUserToOrganization(User $user, Organization $organization, Company $company): void
    {
        // Récupérer l'utilisateur depuis la base de données pour éviter les conflits
        $userId = $user->getId();
        $user = $this->entityManager->getRepository(User::class)->find($userId);

        if (!$user) {
            throw new \Exception('Utilisateur introuvable lors de l\'assignation à l\'organisation');
        }

        $user->setOrganization($organization);
        $user->setCompany($company);
        $user->setRoles(['ROLE_ADMIN']); // Admin de son environnement de démo

        $this->entityManager->persist($user);
        $this->entityManager->flush();
    }

    /**
     * Crée des données de démo réalistes
     */
    public function createDemoData(Organization $organization, Company $company): array
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

        // 5. Créer des documents de démo
        $documents = $this->createDemoDocuments($payments, $organization, $company);
        $demoData['documents'] = count($documents);

        // 6. Créer des demandes de maintenance de démo
        $maintenanceRequests = $this->createDemoMaintenanceRequests($properties, $tenants, $organization, $company);
        $demoData['maintenance_requests'] = count($maintenanceRequests);

        return $demoData;
    }

    /**
     * Crée des propriétés de démo
     */
    private function createDemoProperties(Organization $organization, Company $company): array
    {
        $properties = [];
        $propertyData = [
            [
                'address' => 'Cocody, Riviera 2, Abidjan',
                'city' => 'Abidjan',
                'postalCode' => '00225',
                'rent' => 450000,
                'surface' => 85,
                'rooms' => 3,
                'type' => 'Villa',
                'description' => 'Belle villa 3 chambres avec jardin et parking dans le quartier résidentiel de Riviera 2',
                'floor' => 0,
                'buildingType' => 'Villa individuelle',
                'heating' => 'Climatisation',
                'parking' => true,
                'elevator' => false,
                'balcony' => false,
                'terrace' => true
            ],
            [
                'address' => 'Marcory, Zone 4, Abidjan',
                'city' => 'Abidjan',
                'postalCode' => '00225',
                'rent' => 280000,
                'surface' => 65,
                'rooms' => 2,
                'type' => 'Appartement',
                'description' => 'Appartement moderne 2 chambres avec balcon dans la zone commerciale de Marcory',
                'floor' => 3,
                'buildingType' => 'Immeuble moderne',
                'heating' => 'Ventilateur',
                'parking' => true,
                'elevator' => true,
                'balcony' => true,
                'terrace' => false
            ],
            [
                'address' => 'Plateau, Centre-ville, Abidjan',
                'city' => 'Abidjan',
                'postalCode' => '00225',
                'rent' => 350000,
                'surface' => 45,
                'rooms' => 1,
                'type' => 'Studio',
                'description' => 'Studio entièrement meublé au cœur du Plateau, proche des bureaux et commerces',
                'floor' => 8,
                'buildingType' => 'Immeuble de bureau',
                'heating' => 'Climatisation',
                'parking' => false,
                'elevator' => true,
                'balcony' => false,
                'terrace' => false
            ],
            [
                'address' => 'Yopougon, Sicogi, Abidjan',
                'city' => 'Abidjan',
                'postalCode' => '00225',
                'rent' => 320000,
                'surface' => 75,
                'rooms' => 3,
                'type' => 'Appartement',
                'description' => 'Spacieux appartement 3 chambres avec terrasse dans le quartier populaire de Sicogi',
                'floor' => 2,
                'buildingType' => 'Immeuble collectif',
                'heating' => 'Ventilateur',
                'parking' => true,
                'elevator' => false,
                'balcony' => true,
                'terrace' => true
            ],
            [
                'address' => 'Koumassi, Remblais, Abidjan',
                'city' => 'Abidjan',
                'postalCode' => '00225',
                'rent' => 220000,
                'surface' => 55,
                'rooms' => 2,
                'type' => 'Appartement',
                'description' => 'Appartement 2 chambres avec cour intérieure dans le quartier de Koumassi',
                'floor' => 1,
                'buildingType' => 'Immeuble traditionnel',
                'heating' => 'Ventilateur',
                'parking' => false,
                'elevator' => false,
                'balcony' => false,
                'terrace' => false
            ]
        ];

        foreach ($propertyData as $data) {
            $property = new Property();
            $property->setAddress($data['address']);
            $property->setCity($data['city']);
            $property->setPostalCode($data['postalCode']);
            $property->setMonthlyRent($data['rent']);
            $property->setSurface($data['surface']);
            $property->setRooms($data['rooms']);
            $property->setPropertyType($data['type']);
            $property->setDescription($data['description']);
            $property->setFloor($data['floor']);
            $property->setOrganization($organization);
            $property->setCompany($company);
            $property->setIsDemo(true);
            $property->setCreatedAt(new \DateTime());
            $property->setStatus('Libre'); // Utiliser le statut correct

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
            [
                'firstName' => 'Kouamé',
                'lastName' => 'Traoré',
                'phone' => '0701234567',
                'email' => 'kouame.traore',
                'birthDate' => '1985-03-15',
                'profession' => 'Ingénieur informatique',
                'address' => 'Cocody, Riviera 2, Abidjan',
                'city' => 'Abidjan',
                'postalCode' => '00225',
                'emergencyContact' => 'Aminata Traoré - 0701234568',
                'income' => 1800000,
                'employer' => 'Orange Côte d\'Ivoire'
            ],
            [
                'firstName' => 'Fatou',
                'lastName' => 'Kouassi',
                'phone' => '0701234568',
                'email' => 'fatou.kouassi',
                'birthDate' => '1990-07-22',
                'profession' => 'Architecte',
                'address' => 'Marcory, Zone 4, Abidjan',
                'city' => 'Abidjan',
                'postalCode' => '00225',
                'emergencyContact' => 'Moussa Kouassi - 0701234569',
                'income' => 2200000,
                'employer' => 'Cabinet d\'Architecture Abidjan'
            ],
            [
                'firstName' => 'Drissa',
                'lastName' => 'Koné',
                'phone' => '0701234569',
                'email' => 'drissa.kone',
                'birthDate' => '1988-11-08',
                'profession' => 'Médecin',
                'address' => 'Plateau, Centre-ville, Abidjan',
                'city' => 'Abidjan',
                'postalCode' => '00225',
                'emergencyContact' => 'Mariam Koné - 0701234570',
                'income' => 2800000,
                'employer' => 'CHU de Cocody'
            ],
            [
                'firstName' => 'Aïcha',
                'lastName' => 'Diabaté',
                'phone' => '0701234570',
                'email' => 'aicha.diabate',
                'birthDate' => '1992-05-14',
                'profession' => 'Avocate',
                'address' => 'Yopougon, Sicogi, Abidjan',
                'city' => 'Abidjan',
                'postalCode' => '00225',
                'emergencyContact' => 'Sékou Diabaté - 0701234571',
                'income' => 2500000,
                'employer' => 'Cabinet Diabaté & Associés'
            ],
            [
                'firstName' => 'Mohamed',
                'lastName' => 'Sangaré',
                'phone' => '0701234571',
                'email' => 'mohamed.sangare',
                'birthDate' => '1987-09-30',
                'profession' => 'Chef de projet',
                'address' => 'Koumassi, Remblais, Abidjan',
                'city' => 'Abidjan',
                'postalCode' => '00225',
                'emergencyContact' => 'Rokia Sangaré - 0701234572',
                'income' => 2000000,
                'employer' => 'MTN Côte d\'Ivoire'
            ]
        ];

        // Générer un suffixe unique basé sur l'organisation
        $orgSuffix = substr(md5($organization->getId() . time()), 0, 6);

        foreach ($tenantData as $data) {
            $tenant = new Tenant();
            $tenant->setFirstName($data['firstName']);
            $tenant->setLastName($data['lastName']);
            $tenant->setPhone($data['phone']);
            // Générer un email unique pour chaque tenant
            $email = strtolower($data['email'] . '.' . $orgSuffix . '@demo.com');
            $tenant->setEmail($email);

            $tenant->setOrganization($organization);
            $tenant->setCompany($company);

            $tenant->setIsDemo(true);
            $tenant->setCreatedAt(new \DateTime());
            $tenant->setStatus('Actif');

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
        $leaseData = [
            [
                'startDate' => '-6 months',
                'endDate' => '+6 months',
                'securityDeposit' => 900000,
                'agencyFees' => 450000,
                'guarantor' => 'Aminata Traoré',
                'guarantorPhone' => '0701234568',
                'contractType' => 'Bail meublé',
                'noticePeriod' => 3,
                'renewalConditions' => 'Tacite reconduction'
            ],
            [
                'startDate' => '-4 months',
                'endDate' => '+8 months',
                'securityDeposit' => 560000,
                'agencyFees' => 280000,
                'guarantor' => 'Moussa Kouassi',
                'guarantorPhone' => '0701234569',
                'contractType' => 'Bail vide',
                'noticePeriod' => 3,
                'renewalConditions' => 'Tacite reconduction'
            ],
            [
                'startDate' => '-2 months',
                'endDate' => '+10 months',
                'securityDeposit' => 700000,
                'agencyFees' => 350000,
                'guarantor' => 'Mariam Koné',
                'guarantorPhone' => '0701234570',
                'contractType' => 'Bail meublé',
                'noticePeriod' => 3,
                'renewalConditions' => 'Tacite reconduction'
            ],
            [
                'startDate' => '-1 month',
                'endDate' => '+11 months',
                'securityDeposit' => 640000,
                'agencyFees' => 320000,
                'guarantor' => 'Sékou Diabaté',
                'guarantorPhone' => '0701234571',
                'contractType' => 'Bail vide',
                'noticePeriod' => 3,
                'renewalConditions' => 'Tacite reconduction'
            ]
        ];

        for ($i = 0; $i < min(4, count($properties)); $i++) {
            $lease = new Lease();
            $lease->setProperty($properties[$i]);
            $lease->setTenant($tenants[$i]);
            $lease->setMonthlyRent($properties[$i]->getMonthlyRent());
            $lease->setSecurityDeposit($leaseData[$i]['securityDeposit']);
            $lease->setCharges('25000'); // Charges mensuelles de 25 000 FCFA
            $lease->setDeposit($leaseData[$i]['securityDeposit']); // Dépôt de garantie
            $lease->setTerms('Bail de démo avec conditions standards. Paiement mensuel par virement ou mobile money.');
            $lease->setRentDueDay(5); // Loyer dû le 5 de chaque mois
            $lease->setStartDate((new \DateTime())->modify($leaseData[$i]['startDate']));
            $lease->setEndDate((new \DateTime())->modify($leaseData[$i]['endDate']));
            $lease->setStatus('Actif');
            $lease->setOrganization($organization);
            $lease->setCompany($company);
            $lease->setIsDemo(true);
            $lease->setCreatedAt(new \DateTime());
            $lease->setUpdatedAt(new \DateTime());

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
        $paymentTypes = ['Loyer', 'Charges', 'Caution', 'Acompte'];
        $paymentMethods = ['Orange Money', 'MTN Money', 'Virement bancaire', 'Espèces', 'Chèque'];
        $paymentStatuses = ['Payé', 'En attente', 'En retard'];

        foreach ($leases as $leaseIndex => $lease) {
            // Créer 4 paiements de démo (mois précédents + 1 futur)
            for ($i = 1; $i <= 4; $i++) {
                $payment = new Payment();
                $payment->setLease($lease);
                $payment->setAmount($lease->getMonthlyRent() + ($i === 2 ? 25000 : 0)); // Charges pour le 2ème paiement
                $payment->setDueDate((new \DateTime())->modify("-{$i} months")->modify('+1 day'));

                // Définir la date de paiement selon le statut
                $status = $paymentStatuses[($i + $leaseIndex) % count($paymentStatuses)];
                if ($status === 'Payé') {
                    $payment->setPaidDate((new \DateTime())->modify("-{$i} months")->modify('+2 days'));
                } elseif ($status === 'En attente') {
                    $payment->setPaidDate(null);
                } else {
                    $payment->setPaidDate(null); // En retard
                }

                $payment->setStatus($status);
                $payment->setType($paymentTypes[($i + $leaseIndex) % count($paymentTypes)]);
                $payment->setPaymentMethod($paymentMethods[($i + $leaseIndex) % count($paymentMethods)]);
                $payment->setReference('DEMO-' . ($leaseIndex + 1) . '-' . str_pad($i, 3, '0', STR_PAD_LEFT));
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
     * Crée des documents de démo
     */
    private function createDemoDocuments(array $payments, Organization $organization, Company $company): array
    {
        $documents = [];
        $documentTypes = ['Quittance de loyer', 'Contrat de bail', 'État des lieux', 'Avis d\'échéance', 'Reçu de caution'];

        foreach ($payments as $paymentIndex => $payment) {
            if ($payment->getStatus() === 'Payé') {
                $document = new \App\Entity\Document();
                $document->setType($documentTypes[$paymentIndex % count($documentTypes)]);
                $document->setFileName('demo_' . $document->getType() . '_' . $payment->getId() . '.pdf');
                $document->setFileSize(rand(50000, 500000)); // Taille aléatoire entre 50KB et 500KB
                $document->setTenant($payment->getLease()->getTenant());
                $document->setProperty($payment->getLease()->getProperty());
                $document->setLease($payment->getLease()); // Assigner explicitement le lease
                $document->setOrganization($organization);
                $document->setCompany($company);
                $document->setCreatedAt(new \DateTime());

                $this->entityManager->persist($document);
                $documents[] = $document;
            }
        }

        $this->entityManager->flush();
        return $documents;
    }

    /**
     * Crée des demandes de maintenance de démo
     */
    private function createDemoMaintenanceRequests(array $properties, array $tenants, Organization $organization, Company $company): array
    {
        $requests = [];
        $requestTypes = ['Plomberie', 'Électricité', 'Climatisation', 'Sécurité', 'Entretien général', 'Réparation toit', 'Réparation portail'];
        $priorities = ['Faible', 'Moyenne', 'Élevée', 'Urgente'];
        $statuses = ['Nouvelle', 'En cours', 'Terminée', 'Annulée'];

        for ($i = 0; $i < 3; $i++) {
            $request = new \App\Entity\MaintenanceRequest();
            $request->setProperty($properties[$i]);
            $request->setTenant($tenants[$i]);
            $request->setCreatedAt(new \DateTime());

            $this->entityManager->persist($request);
            $requests[] = $request;
        }

        $this->entityManager->flush();
        return $requests;
    }

    /**
     * Configure l'environnement de démo (DNS, Apache, etc.)
     */
    private function configureDemoEnvironment(string $subdomain, string $demoUrl): void
    {
        // Détecter le type d'hébergement
        if ($this->isSharedHosting()) {
            $this->configureSharedHostingEnvironment($subdomain, $demoUrl);
        } else {
            $this->configureLocalEnvironment($subdomain, $demoUrl);
        }
    }

    /**
     * Détecte si on est en hébergement partagé
     */
    private function isSharedHosting(): bool
    {
        // Vérifier si on est sur un domaine de production (pas .local)
        $request = $this->requestStack->getCurrentRequest();
        if ($request) {
            $host = $request->getHost();
            // Si le domaine se termine par .com, .fr, .org, etc. (pas .local)
            return preg_match('/\.(com|fr|org|net|be|ch|eu)$/', $host);
        }

        // Vérifier la variable d'environnement
        return $_ENV['APP_ENV'] === 'prod' || !str_contains($_ENV['DEMO_BASE_URL'] ?? '', '.local');
    }

    /**
     * Configure l'environnement de démo pour développement local
     */
    private function configureLocalEnvironment(string $subdomain, string $demoUrl): void
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
     * Configure l'environnement de démo pour hébergement partagé (cPanel)
     */
    private function configureSharedHostingEnvironment(string $subdomain, string $demoUrl): void
    {
        // 1. Créer un fichier .htaccess pour la détection du sous-domaine
        $this->createSharedHostingHtaccess($subdomain);

        // 2. Créer un fichier de configuration pour l'environnement
        $this->createSharedHostingConfig($subdomain, $demoUrl);

        // 3. Log de création
        $this->logDemoEnvironmentCreation($subdomain, $demoUrl);
    }

    /**
     * Crée la configuration .htaccess pour hébergement partagé
     */
    private function createSharedHostingHtaccess(string $subdomain): void
    {
        $htaccessContent = <<<EOF
# Configuration pour sous-domaines de démo - Hébergement partagé
RewriteEngine On

# Détection du sous-domaine de démo
RewriteCond %{HTTP_HOST} ^([^.]+)\.demo\.{$this->getDomainFromBaseUrl()}$ [NC]
RewriteRule ^(.*)$ - [E=DEMO_SUBDOMAIN:%1]

# Redirection vers Symfony
RewriteCond %{REQUEST_FILENAME} !-f
RewriteCond %{REQUEST_FILENAME} !-d
RewriteRule ^(.*)$ index.php [QSA,L]

# Headers de sécurité
Header always set X-Frame-Options "SAMEORIGIN"
Header always set X-Content-Type-Options "nosniff"
Header always set X-XSS-Protection "1; mode=block"
Header always set X-Demo-Environment "true"
EOF;

        $htaccessFile = $this->params->get('kernel.project_dir') . '/public/.htaccess';

        // Ajouter la configuration à la fin du fichier .htaccess existant
        if (file_exists($htaccessFile)) {
            $existingContent = file_get_contents($htaccessFile);
            if (strpos($existingContent, '# Configuration pour sous-domaines de démo') === false) {
                file_put_contents($htaccessFile, $existingContent . "\n\n" . $htaccessContent);
            }
        } else {
            file_put_contents($htaccessFile, $htaccessContent);
        }
    }

    /**
     * Crée la configuration pour l'environnement de démo en hébergement partagé
     */
    private function createSharedHostingConfig(string $subdomain, string $demoUrl): void
    {
        $configContent = [
            'subdomain' => $subdomain,
            'demo_url' => $demoUrl,
            'created_at' => (new \DateTime())->format('Y-m-d H:i:s'),
            'environment' => 'shared_hosting',
            'hosting_type' => 'cpanel'
        ];

        $configFile = $this->demoDataDir . "/{$subdomain}_config.json";
        file_put_contents($configFile, json_encode($configContent, JSON_PRETTY_PRINT));
    }

    /**
     * Extrait le domaine de base depuis demoBaseUrl
     */
    private function getDomainFromBaseUrl(): string
    {
        return str_replace('demo.', '', $this->demoBaseUrl);
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
                $content .= "\n# LOKAPRO Demo Environment\n{$dnsEntry}\n";
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
    public function deleteDemoEnvironment(string $subdomain): array
    {
        try {
            $organization = $this->entityManager->getRepository(Organization::class)
                ->findOneBy(['subdomain' => $subdomain, 'isDemo' => true]);

            if (!$organization) {
                return [
                    'success' => false,
                    'error' => 'Démo non trouvée',
                    'message' => 'L\'environnement de démo n\'existe pas'
                ];
            }

            // 1. Supprimer les données de la base
            $this->deleteDemoData($subdomain);

            // 2. Supprimer les fichiers de configuration
            $this->deleteConfigurationFiles($subdomain);

            // 3. Supprimer l'organisation
            $this->entityManager->remove($organization);
            $this->entityManager->flush();

            // 4. Log de suppression
            $this->logDemoEnvironmentDeletion($subdomain);

            return [
                'success' => true,
                'message' => 'Environnement de démo supprimé avec succès'
            ];
        } catch (\Exception $e) {
            error_log('Erreur suppression démo: ' . $e->getMessage());
            return [
                'success' => false,
                'error' => $e->getMessage(),
                'message' => 'Erreur lors de la suppression de la démo'
            ];
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
            ->findBy(['isDemo' => true], ['createdAt' => 'DESC']);

        $environments = [];
        foreach ($organizations as $org) {
            $subdomain = $org->getSubdomain();

            // Générer une URL de démo seulement si le subdomain existe
            $demoUrl = null;
            if ($subdomain) {
                $demoUrl = $this->generateDemoUrl($subdomain);
            }

            $environments[] = [
                'id' => $org->getId(),
                'subdomain' => $subdomain,
                'name' => $org->getName(),
                'email' => $org->getEmail(),
                'created_at' => $org->getCreatedAt(),
                'updated_at' => $org->getUpdatedAt(),
                'demo_url' => $demoUrl,
                'status' => $org->getStatus(),
                'is_active' => $org->isActive(),
                'trial_ends_at' => $org->getTrialEndsAt(),
                'properties_count' => $org->getProperties()->count(),
                'tenants_count' => $org->getTenants()->count(),
                'companies_count' => $org->getCompanies()->count(),
                'is_expired' => $this->isDemoExpired($org)
            ];
        }

        return $environments;
    }

    /**
     * Vérifie si une démo est expirée
     */
    private function isDemoExpired(Organization $organization): bool
    {
        if (!$organization->getTrialEndsAt()) {
            return false;
        }

        return $organization->getTrialEndsAt() < new \DateTime();
    }


    /**
     * Nettoie les données d'une démo
     */
    private function cleanupDemoData(Organization $organization): void
    {
        // Supprimer les propriétés
        foreach ($organization->getProperties() as $property) {
            $this->entityManager->remove($property);
        }

        // Supprimer les locataires
        foreach ($organization->getTenants() as $tenant) {
            $this->entityManager->remove($tenant);
        }

        // Supprimer les baux
        foreach ($organization->getLeases() as $lease) {
            $this->entityManager->remove($lease);
        }

        // Supprimer les paiements
        foreach ($organization->getPayments() as $payment) {
            $this->entityManager->remove($payment);
        }

        // Supprimer les sociétés
        foreach ($organization->getCompanies() as $company) {
            $this->entityManager->remove($company);
        }

        // Supprimer les propriétaires
        foreach ($organization->getOwners() as $owner) {
            $this->entityManager->remove($owner);
        }
    }

    /**
     * Nettoie les fichiers de configuration d'une démo
     */
    private function cleanupDemoFiles(string $subdomain): void
    {
        $configFile = $this->demoDataDir . "/{$subdomain}_config.json";
        if (file_exists($configFile)) {
            unlink($configFile);
        }
    }

    /**
     * Prolonge une démo
     */
    public function extendDemoEnvironment(string $subdomain, int $days = 7): array
    {
        try {
            $organization = $this->entityManager->getRepository(Organization::class)
                ->findOneBy(['subdomain' => $subdomain, 'isDemo' => true]);

            if (!$organization) {
                return [
                    'success' => false,
                    'error' => 'Démo non trouvée',
                    'message' => 'L\'environnement de démo n\'existe pas'
                ];
            }

            $newTrialEnd = new \DateTime();
            $newTrialEnd->add(new \DateInterval("P{$days}D"));
            $organization->setTrialEndsAt($newTrialEnd);

            $this->entityManager->flush();

            return [
                'success' => true,
                'message' => "Démo prolongée de {$days} jours",
                'new_trial_end' => $newTrialEnd->format('Y-m-d H:i:s')
            ];

        } catch (\Exception $e) {
            error_log('Erreur prolongation démo: ' . $e->getMessage());
            return [
                'success' => false,
                'error' => $e->getMessage(),
                'message' => 'Erreur lors de la prolongation de la démo'
            ];
        }
    }

    /**
     * Nettoie automatiquement les démos expirées
     */
    public function cleanupExpiredDemos(): array
    {
        $expiredOrgs = $this->entityManager->getRepository(Organization::class)
            ->createQueryBuilder('o')
            ->where('o.isDemo = :demo')
            ->andWhere('o.trialEndsAt < :now')
            ->setParameter('demo', true)
            ->setParameter('now', new \DateTime())
            ->getQuery()
            ->getResult();

        $cleaned = [];
        foreach ($expiredOrgs as $org) {
            $result = $this->deleteDemoEnvironment($org->getSubdomain());
            if ($result['success']) {
                $cleaned[] = $org->getSubdomain();
            }
        }

        return [
            'success' => true,
            'cleaned_count' => count($cleaned),
            'cleaned_demos' => $cleaned,
            'message' => count($cleaned) . ' démo(s) expirée(s) nettoyée(s)'
        ];
    }

    /**
     * Obtient les statistiques des démos
     */
    public function getDemoStatistics(): array
    {
        $totalDemos = $this->entityManager->getRepository(Organization::class)
            ->count(['isDemo' => true]);

        $activeDemos = $this->entityManager->getRepository(Organization::class)
            ->createQueryBuilder('o')
            ->select('COUNT(o.id)')
            ->where('o.isDemo = :demo')
            ->andWhere('o.isActive = :active')
            ->setParameter('demo', true)
            ->setParameter('active', true)
            ->getQuery()
            ->getSingleScalarResult();

        $expiredDemos = $this->entityManager->getRepository(Organization::class)
            ->createQueryBuilder('o')
            ->select('COUNT(o.id)')
            ->where('o.isDemo = :demo')
            ->andWhere('o.trialEndsAt < :now')
            ->setParameter('demo', true)
            ->setParameter('now', new \DateTime())
            ->getQuery()
            ->getSingleScalarResult();

        $expiringSoon = $this->entityManager->getRepository(Organization::class)
            ->createQueryBuilder('o')
            ->select('COUNT(o.id)')
            ->where('o.isDemo = :demo')
            ->andWhere('o.trialEndsAt BETWEEN :now AND :soon')
            ->setParameter('demo', true)
            ->setParameter('now', new \DateTime())
            ->setParameter('soon', (new \DateTime())->add(new \DateInterval('P7D')))
            ->getQuery()
            ->getSingleScalarResult();

        return [
            'total_demos' => $totalDemos,
            'active_demos' => $activeDemos,
            'expired_demos' => $expiredDemos,
            'expiring_soon' => $expiringSoon,
            'cleanup_needed' => $expiredDemos > 0
        ];
    }

    /**
     * Crée un environnement de démo avec URL paramétrique
     */
    public function createDemoEnvironmentWithUrl(User $user): array
    {
        // Vérifier si l'EntityManager est fermé et le rouvrir si nécessaire
        if (!$this->entityManager->isOpen()) {
            // Pour recréer un EntityManager fermé, on doit utiliser le service factory
            // ou simplement continuer avec un nouveau EntityManager
            // Dans ce cas, on va simplement continuer car Doctrine gère automatiquement
            // la reconnexion si nécessaire
        }

        // Démarrer une transaction pour assurer la cohérence
        $this->entityManager->beginTransaction();

        try {
            // Générer un code unique pour la démo
            $demoCode = $this->generateDemoCode();

            // Vérifier si l'utilisateur a déjà une démo active
            $existingDemo = $this->getUserActiveDemo($user);
            if ($existingDemo) {
                $this->entityManager->rollback();
                return [
                    'success' => false,
                    'message' => 'Vous avez déjà une démo active. Supprimez-la d\'abord pour en créer une nouvelle.',
                    'demo_code' => null
                ];
            }

            // Créer l'organisation de démo
            $organization = $this->createDemoOrganization($user, $demoCode);

            // Créer la société de démo
            $company = $this->createDemoCompany($organization, $demoCode);

            // Créer les données de démo
            $this->createDemoData($organization, $company);

            // Enregistrer les informations de la démo
            $this->saveDemoInfo($demoCode, $user, $organization, $company);

            // Générer l'URL de la démo
            $demoUrl = $this->generateDemoUrl($demoCode);

            // Valider la transaction
            $this->entityManager->commit();

            return [
                'success' => true,
                'message' => sprintf('Démo créée avec succès ! Votre URL de démo : %s', $demoUrl),
                'demo_code' => $demoCode,
                'demo_url' => $demoUrl,
                'organization' => $organization,
                'company' => $company
            ];

        } catch (\Exception $e) {
            // Annuler la transaction en cas d'erreur
            if ($this->entityManager->getConnection()->isTransactionActive()) {
                $this->entityManager->rollback();
            }

            $this->logger->error('Erreur lors de la création de la démo avec URL', [
                'user' => $user->getEmail(),
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return [
                'success' => false,
                'message' => 'Erreur lors de la création de la démo : ' . $e->getMessage(),
                'demo_code' => null
            ];
        }
    }

    /**
     * Génère un code unique pour la démo
     */
    private function generateDemoCode(): string
    {
        do {
            $code = strtoupper(substr(md5(uniqid(rand(), true)), 0, 8));
        } while ($this->demoCodeExists($code));

        return $code;
    }

    /**
     * Vérifie si un code de démo existe déjà
     */
    private function demoCodeExists(string $code): bool
    {
        $demoInfoFile = $this->demoDataDir . '/demo_' . $code . '.json';
        return file_exists($demoInfoFile);
    }

    /**
     * Récupère une démo par son code
     */
    public function getDemoByCode(string $demoCode): ?array
    {
        // D'abord, essayer de récupérer depuis le fichier JSON
        $demoInfoFile = $this->demoDataDir . '/demo_' . $demoCode . '.json';

        if (file_exists($demoInfoFile)) {
            $demoData = json_decode(file_get_contents($demoInfoFile), true);

            if ($demoData) {
                // Récupérer l'organisation et l'utilisateur depuis la base de données
                $organization = $this->entityManager->getRepository(Organization::class)->find($demoData['organization_id']);
                $user = $this->entityManager->getRepository(User::class)->find($demoData['user_id']);

                if ($organization && $user) {
                    return array_merge($demoData, [
                        'organization' => $organization,
                        'user' => $user,
                        'demo_code' => $demoCode
                    ]);
                }
            }
        }

        // Si pas de fichier JSON ou données corrompues, essayer de récupérer directement depuis la DB
        $organization = $this->entityManager->getRepository(Organization::class)
            ->findOneBy(['subdomain' => $demoCode, 'isDemo' => true]);

        if (!$organization) {
            return null;
        }

        $user = $this->entityManager->getRepository(User::class)
            ->findOneBy(['organization' => $organization]);

        if (!$user) {
            return null;
        }

        return [
            'demo_code' => $demoCode,
            'organization' => $organization,
            'user' => $user,
            'organization_id' => $organization->getId(),
            'user_id' => $user->getId(),
            'created_at' => $organization->getCreatedAt() ? $organization->getCreatedAt()->format('Y-m-d H:i:s') : null,
            'expires_at' => $organization->getTrialEndsAt() ? $organization->getTrialEndsAt()->format('Y-m-d H:i:s') : null
        ];
    }

    /**
     * Vérifie si une démo est active
     */
    public function isDemoActive(array $demo): bool
    {
        if (!isset($demo['organization'])) {
            return false;
        }

        $organization = $demo['organization'];

        if (!$organization->getIsDemo()) {
            return false;
        }

        $now = new \DateTime();
        return $organization->getTrialEndsAt() && $organization->getTrialEndsAt() > $now;
    }

    /**
     * Génère l'URL de la démo
     */
    private function generateDemoUrl(string $demoCode): string
    {
        $request = $this->requestStack->getCurrentRequest();
        $scheme = $request ? $request->getScheme() : 'https';
        $host = $request ? $request->getHost() : 'localhost';
        $port = $request && $request->getPort() !== 80 && $request->getPort() !== 443 ? ':' . $request->getPort() : '';

        return sprintf('%s://%s%s/demo/%s', $scheme, $host, $port, $demoCode);
    }

    /**
     * Sauvegarde les informations de la démo
     */
    private function saveDemoInfo(string $demoCode, User $user, Organization $organization, Company $company): void
    {
        $demoInfo = [
            'demo_code' => $demoCode,
            'user_id' => $user->getId(),
            'organization_id' => $organization->getId(),
            'company_id' => $company->getId(),
            'created_at' => (new \DateTime())->format('Y-m-d H:i:s'),
            'expires_at' => $organization->getTrialEndsAt() ? $organization->getTrialEndsAt()->format('Y-m-d H:i:s') : null,
            'demo_url' => $this->generateDemoUrl($demoCode)
        ];

        $demoInfoFile = $this->demoDataDir . '/demo_' . $demoCode . '.json';
        file_put_contents($demoInfoFile, json_encode($demoInfo, JSON_PRETTY_PRINT));
    }

    /**
     * Récupère les démos d'un utilisateur
     */
    public function getUserDemos(User $user): array
    {
        $demos = [];
        $files = glob($this->demoDataDir . '/demo_*.json');

        foreach ($files as $file) {
            $demoData = json_decode(file_get_contents($file), true);

            if ($demoData && $demoData['user_id'] === $user->getId()) {
                $demo = $this->getDemoByCode($demoData['demo_code']);
                if ($demo) {
                    $demos[] = $demo;
                }
            }
        }

        return $demos;
    }

    /**
     * Récupère la démo active d'un utilisateur
     */
    public function getUserActiveDemo(User $user): ?array
    {
        $demos = $this->getUserDemos($user);

        foreach ($demos as $demo) {
            if ($this->isDemoActive($demo)) {
                return $demo;
            }
        }

        return null;
    }

    /**
     * Connecte l'utilisateur à l'environnement de démo
     */
    public function loginToDemoEnvironment(User $user, string $demoCode): void
    {
        // Cette méthode sera implémentée selon le système d'authentification
        // Pour l'instant, on peut juste stocker le code de démo en session
        $request = $this->requestStack->getCurrentRequest();
        if ($request) {
            $request->getSession()->set('demo_code', $demoCode);
            $request->getSession()->set('demo_user_id', $user->getId());
        }
    }

    /**
     * Supprime une démo
     */
    public function deleteDemo(string $demoCode): array
    {
        try {
            $demo = $this->getDemoByCode($demoCode);

            if (!$demo) {
                return [
                    'success' => false,
                    'message' => 'Démo introuvable'
                ];
            }

            // Supprimer l'organisation et toutes les données associées
            if (isset($demo['organization'])) {
                $this->entityManager->remove($demo['organization']);
                $this->entityManager->flush();
            }

            // Supprimer le fichier d'information de la démo
            $demoInfoFile = $this->demoDataDir . '/demo_' . $demoCode . '.json';
            if (file_exists($demoInfoFile)) {
                unlink($demoInfoFile);
            }

            return [
                'success' => true,
                'message' => 'Démo supprimée avec succès'
            ];

        } catch (\Exception $e) {
            return [
                'success' => false,
                'message' => 'Erreur lors de la suppression : ' . $e->getMessage()
            ];
        }
    }

    /**
     * Prolonge une démo
     */
    public function extendDemo(string $demoCode, int $days = 7): array
    {
        try {
            $demo = $this->getDemoByCode($demoCode);

            if (!$demo) {
                return [
                    'success' => false,
                    'message' => 'Démo introuvable'
                ];
            }

            $organization = $demo['organization'];
            $currentEndDate = $organization->getTrialEndsAt() ?: new \DateTime();
            $newEndDate = (clone $currentEndDate)->add(new \DateInterval('P' . $days . 'D'));

            $organization->setTrialEndsAt($newEndDate);
            $this->entityManager->flush();

            // Mettre à jour le fichier d'information
            $demoInfoFile = $this->demoDataDir . '/demo_' . $demoCode . '.json';
            if (file_exists($demoInfoFile)) {
                $demoInfo = json_decode(file_get_contents($demoInfoFile), true);
                $demoInfo['expires_at'] = $newEndDate->format('Y-m-d H:i:s');
                file_put_contents($demoInfoFile, json_encode($demoInfo, JSON_PRETTY_PRINT));
            }

            return [
                'success' => true,
                'message' => sprintf('Démo prolongée de %d jours. Nouvelle date offerte : %s', $days, $newEndDate->format('d/m/Y'))
            ];

        } catch (\Exception $e) {
            return [
                'success' => false,
                'message' => 'Erreur lors de la prolongation : ' . $e->getMessage()
            ];
        }
    }
}
