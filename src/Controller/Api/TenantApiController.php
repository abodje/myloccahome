<?php

namespace App\Controller\Api;

use App\Entity\Tenant;
use App\Entity\User;
use App\Repository\AccountingEntryRepository;
use App\Repository\LeaseRepository;
use App\Repository\PaymentRepository;
use App\Repository\PropertyRepository;
use App\Repository\TenantRepository;
use App\Service\JwtService;
use App\Service\SettingsService;
use App\Service\CurrencyService;
use App\Service\CinetPayService;
use App\Entity\OnlinePayment;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

/**
 * API REST pour l'application mobile Flutter - Espace Locataire
 */
#[Route('/api/tenant', name: 'api_tenant_')]
class TenantApiController extends AbstractController
{
    public function __construct(
        private EntityManagerInterface $entityManager,
        private TenantRepository $tenantRepository,
        private LeaseRepository $leaseRepository,
        private PaymentRepository $paymentRepository,
        private PropertyRepository $propertyRepository,
        private AccountingEntryRepository $accountingEntryRepository,
        private JwtService $jwtService,
        private SettingsService $settingsService,
        private CurrencyService $currencyService
    ) {
    }

    /**
     * Point d'entrée de l'API - Informations sur l'API
     * GET /api/tenant
     */
    #[Route('/', name: 'index', methods: ['GET'])]
    public function index(): JsonResponse
    {
        return $this->json([
            'success' => true,
            'message' => 'API Tenant MyLocca',
            'version' => '1.0.0',
            'endpoints' => [
                'login' => 'POST /api/tenant/login',
                'dashboard' => 'GET /api/tenant/dashboard',
                'profile' => 'GET /api/tenant/profile',
                'payments' => 'GET /api/tenant/payments',
                'requests' => 'GET /api/tenant/requests',
                'documents' => 'GET /api/tenant/documents',
                'property' => 'GET /api/tenant/property',
                'accounting' => 'GET /api/tenant/accounting'
            ]
        ]);
    }

    /**
     * Authentification du locataire
     * POST /api/tenant/login
     */
    #[Route('/login', name: 'login', methods: ['POST'])]
    public function login(Request $request): JsonResponse
    {
        $data = json_decode($request->getContent(), true);

        $email = $data['email'] ?? null;
        $password = $data['password'] ?? null;

        if (!$email || !$password) {
            return $this->json([
                'success' => false,
                'message' => 'Email et mot de passe requis'
            ], Response::HTTP_BAD_REQUEST);
        }

        // Chercher l'utilisateur
        $user = $this->entityManager->getRepository(User::class)
            ->findOneBy(['email' => $email]);

        if (!$user) {
            return $this->json([
                'success' => false,
                'message' => 'Aucun compte trouvé avec cet email'
            ], Response::HTTP_UNAUTHORIZED);
        }

        // Vérifier l'état actif de l'utilisateur
        if (!$user->isActive()) {
            return $this->json([
                'success' => false,
                'message' => 'Ce compte est désactivé'
            ], Response::HTTP_UNAUTHORIZED);
        }

        // Vérifier le mot de passe
        if (!password_verify($password, $user->getPassword())) {
            return $this->json([
                'success' => false,
                'message' => 'Mot de passe incorrect'
            ], Response::HTTP_UNAUTHORIZED);
        }

        // Vérifier que c'est bien un locataire
        if (!in_array('ROLE_TENANT', $user->getRoles())) {
            return $this->json([
                'success' => false,
                'message' => 'Accès réservé aux locataires. Votre compte n\'a pas les permissions nécessaires.'
            ], Response::HTTP_FORBIDDEN);
        }

        // Récupérer le profil locataire
        $tenant = $this->tenantRepository->findOneBy(['email' => $email]);

        if (!$tenant) {
            return $this->json([
                'success' => false,
                'message' => 'Profil locataire introuvable. Contactez votre administrateur.'
            ], Response::HTTP_NOT_FOUND);
        }

        // Générer un token JWT sécurisé
        $token = $this->jwtService->generateToken([
            'user_id' => $user->getId(),
            'email' => $user->getEmail(),
            'tenant_id' => $tenant->getId(),
            'roles' => $user->getRoles()
        ]);

        return $this->json([
            'success' => true,
            'token' => $token,
            'token_type' => 'Bearer',
            'expires_in' => 86400, // 24 heures
            'user' => [
                'id' => $user->getId(),
                'email' => $user->getEmail(),
                'firstName' => $user->getFirstName(),
                'lastName' => $user->getLastName(),
                'roles' => $user->getRoles()
            ],
            'tenant' => [
                'id' => $tenant->getId(),
                'firstName' => $tenant->getFirstName(),
                'lastName' => $tenant->getLastName(),
                'email' => $tenant->getEmail(),
                'phone' => $tenant->getPhone()
            ],
            'settings' => $this->buildMobileSettings(),
        ]);
    }

    /**
     * Tableau de bord du locataire
     * GET /api/tenant/dashboard
     */
    #[Route('/dashboard', name: 'dashboard', methods: ['GET'])]
    public function dashboard(Request $request): JsonResponse
    {
        $tenant = $this->getAuthenticatedTenant($request);
        if (!$tenant) {
            return $this->json([
                'success' => false,
                'message' => 'Non autorisé - Token invalide ou expiré'
            ], Response::HTTP_UNAUTHORIZED);
        }

        // Récupérer le bail actif
        $activeLease = $this->leaseRepository->findOneBy([
            'tenant' => $tenant,
            'status' => 'Actif'
        ]);

        $dashboard = [
            'success' => true,
            'tenant' => [
                'id' => $tenant->getId(),
                'firstName' => $tenant->getFirstName(),
                'lastName' => $tenant->getLastName(),
                'email' => $tenant->getEmail(),
                'phone' => $tenant->getPhone(),
                'accountNumber' => $tenant->getId(), // Numéro de compte
                'clientNumber' => sprintf('%010d', $tenant->getId()) // Numéro client
            ],
            'currentLease' => null,
            'property' => null,
            'balances' => [
                'soldAt' => 0,
                'toPay' => 0
            ],
            'manager' => null
        ];

        if ($activeLease) {
            $property = $activeLease->getProperty();

            // Calculer les soldes en utilisant le même système que les contrôleurs web
            // Utiliser AccountingEntryRepository pour inclure tous les types d'écritures
            $tenantStats = $this->accountingEntryRepository->getTenantStatistics($tenant->getId());
            $balance = $tenantStats['balance'] ?? 0.0;

            // Calculer aussi à partir des paiements pour compatibilité et comparaison
            $payments = $this->paymentRepository->findBy(['lease' => $activeLease]);
            $totalDue = 0;
            $totalPaid = 0;

            foreach ($payments as $payment) {
                $totalDue += $payment->getAmount();
                if ($payment->getStatus() === 'Payé') {
                    $totalPaid += $payment->getAmount();
                }
            }

            $dashboard['currentLease'] = [
                'id' => $activeLease->getId(),
                'startDate' => $activeLease->getStartDate()->format('d/m/Y'),
                'endDate' => $activeLease->getEndDate()?->format('d/m/Y'),
                'monthlyRent' => $activeLease->getMonthlyRent(),
                'status' => $activeLease->getStatus()
            ];

            $dashboard['property'] = [
                'id' => $property->getId(),
                'reference' => $property->getId(),
                'name' => $property->getFullAddress(),
                'address' => $property->getAddress(),
                'city' => $property->getCity(),
                'postalCode' => $property->getPostalCode(),
                'fullAddress' => $property->getFullAddress(),
                'rooms' => $property->getRooms(),
                'surface' => $property->getSurface(),
                'type' => $property->getPropertyType()
            ];

            // Utiliser le balance du système comptable (cohérent avec les contrôleurs web)
            // Balance négative = dette, positive = crédit
            $dashboard['balances'] = [
                'soldAt' => $balance, // Solde comptable (négatif si dette, positif si crédit)
                'toPay' => max(0, -$balance), // Montant à payer (seulement si solde négatif)
                // Informations supplémentaires pour compatibilité
                'totalPaid' => $totalPaid,
                'totalDue' => $totalDue
            ];

            // Gestionnaire
            $managers = $property->getManagers();
            if (!$managers->isEmpty()) {
                // On prend le premier manager de la liste comme contact principal
                $mainManager = $managers->first();

                $dashboard['manager'] = [
                    'name' => $mainManager->getFullName(),
                    'company' => $property->getOrganization()?->getName(),
                    'address' => $property->getOrganization()?->getAddress(),
                    'city' => $property->getOrganization()?->getCity(),
                    'phone' => $mainManager->getPhone() ?? $property->getOrganization()?->getPhone(),
                    'email' => $mainManager->getEmail() ?? $property->getOrganization()?->getEmail()
                ];
            } else {
                // Fallback: utiliser les coordonnées de l'organisation si pas de gestionnaire assigné
                $org = $property->getOrganization();
                if ($org) {
                    $dashboard['manager'] = [
                        'name' => $org->getName() ?? 'Gestion',
                        'company' => $org->getName(),
                        'address' => $org->getAddress(),
                        'city' => $org->getCity(),
                        'phone' => $org->getPhone(),
                        'email' => $org->getEmail(),
                    ];
                }
            }
        }

        return $this->json($dashboard);
    }

    /**
     * Profil du locataire
     * GET /api/tenant/profile
     */
    #[Route('/profile', name: 'profile', methods: ['GET'])]
    public function profile(Request $request): JsonResponse
    {
        $tenant = $this->getAuthenticatedTenant($request);
        if (!$tenant) {
            return $this->json([
                'success' => false,
                'message' => 'Non autorisé - Token invalide ou expiré'
            ], Response::HTTP_UNAUTHORIZED);
        }

        return $this->json([
            'success' => true,
            'profile' => [
                'id' => $tenant->getId(),
                'firstName' => $tenant->getFirstName(),
                'lastName' => $tenant->getLastName(),
                'email' => $tenant->getEmail(),
                'phone' => $tenant->getPhone(),
                'address' => $tenant->getAddress(),
                'city' => $tenant->getCity(),
                'postalCode' => $tenant->getPostalCode(),
                'birthDate' => $tenant->getBirthDate()?->format('d/m/Y')
            ]
        ]);
    }

    /**
     * Mettre à jour le profil
     * PUT /api/tenant/profile
     */
    #[Route('/profile', name: 'profile_update', methods: ['PUT'])]
    public function updateProfile(Request $request): JsonResponse
    {
        $tenant = $this->getAuthenticatedTenant($request);
        if (!$tenant) {
            return $this->json([
                'success' => false,
                'message' => 'Non autorisé - Token invalide ou expiré'
            ], Response::HTTP_UNAUTHORIZED);
        }

        $data = json_decode($request->getContent(), true);

        if (isset($data['phone'])) $tenant->setPhone($data['phone']);
        if (isset($data['address'])) $tenant->setAddress($data['address']);
        if (isset($data['city'])) $tenant->setCity($data['city']);
        if (isset($data['postalCode'])) $tenant->setPostalCode($data['postalCode']);

        $this->entityManager->flush();

        return $this->json([
            'success' => true,
            'message' => 'Profil mis à jour avec succès'
        ]);
    }

    /**
     * Liste des paiements du locataire
     * GET /api/tenant/payments
     */
    #[Route('/payments', name: 'payments', methods: ['GET'])]
    public function payments(Request $request): JsonResponse
    {
        $tenant = $this->getAuthenticatedTenant($request);
        if (!$tenant) {
            return $this->json([
                'success' => false,
                'message' => 'Non autorisé - Token invalide ou expiré'
            ], Response::HTTP_UNAUTHORIZED);
        }

        // Récupérer tous les baux du locataire
        $leases = $this->leaseRepository->findBy(['tenant' => $tenant]);

        $allPayments = [];
        foreach ($leases as $lease) {
            $payments = $this->paymentRepository->findBy(
                ['lease' => $lease],
                ['dueDate' => 'DESC']
            );

            foreach ($payments as $payment) {
                $allPayments[] = [
                    'id' => $payment->getId(),
                    'type' => $payment->getType(),
                    'amount' => $payment->getAmount(),
                    'dueDate' => $payment->getDueDate()->format('d/m/Y'),
                    'paidDate' => $payment->getPaidDate()?->format('d/m/Y'),
                    'status' => $payment->getStatus(),
                    'paymentMethod' => $payment->getPaymentMethod(),
                    'reference' => $payment->getReference(),
                    'property' => [
                        'address' => $lease->getProperty()->getFullAddress()
                    ]
                ];
            }
        }

        // Calculer les statistiques
        $total = 0;
        $paid = 0;
        $pending = 0;

        foreach ($allPayments as $payment) {
            $total += $payment['amount'];
            if ($payment['status'] === 'Payé') {
                $paid += $payment['amount'];
            } else {
                $pending += $payment['amount'];
            }
        }

        // Utiliser aussi le système comptable pour avoir le balance exact (cohérent avec les contrôleurs web)
        $tenantStats = $this->accountingEntryRepository->getTenantStatistics($tenant->getId());
        $balance = $tenantStats['balance'] ?? 0.0;

        return $this->json([
            'success' => true,
            'statistics' => [
                'total' => $total,
                'paid' => $paid,
                'pending' => $pending,
                'balance' => $balance, // Balance comptable (cohérent avec les contrôleurs web)
                'balanceFromPayments' => $paid - $total // Balance calculée uniquement des paiements (pour référence)
            ],
            'payments' => $allPayments,
            'count' => count($allPayments)
        ]);
    }

    /**
     * Détails d'un paiement
     * GET /api/tenant/payments/{id}
     */
    #[Route('/payments/{id}', name: 'payment_show', methods: ['GET'])]
    public function paymentDetails(int $id, Request $request): JsonResponse
    {
        $tenant = $this->getAuthenticatedTenant($request);
        if (!$tenant) {
            return $this->json([
                'success' => false,
                'message' => 'Non autorisé - Token invalide ou expiré'
            ], Response::HTTP_UNAUTHORIZED);
        }

        $payment = $this->paymentRepository->find($id);

        if (!$payment || $payment->getLease()->getTenant()->getId() !== $tenant->getId()) {
            return $this->json([
                'success' => false,
                'message' => 'Paiement non trouvé'
            ], Response::HTTP_NOT_FOUND);
        }

        return $this->json([
            'success' => true,
            'payment' => [
                'id' => $payment->getId(),
                'type' => $payment->getType(),
                'amount' => $payment->getAmount(),
                'dueDate' => $payment->getDueDate()->format('d/m/Y'),
                'paidDate' => $payment->getPaidDate()?->format('d/m/Y'),
                'status' => $payment->getStatus(),
                'paymentMethod' => $payment->getPaymentMethod(),
                'reference' => $payment->getReference(),
                'notes' => $payment->getNotes(),
                'lease' => [
                    'id' => $payment->getLease()->getId(),
                    'property' => [
                        'address' => $payment->getLease()->getProperty()->getFullAddress()
                    ]
                ]
            ]
        ]);
    }

    /**
     * Initier le paiement d'un loyer (CinetPay) - JSON
     * POST /api/tenant/payments/{id}/pay
     */
    #[Route('/payments/{id}/pay', name: 'payment_pay', methods: ['POST'])]
    public function payRent(int $id, Request $request, CinetPayService $cinetpay): JsonResponse
    {
        $tenant = $this->getAuthenticatedTenant($request);
        if (!$tenant) {
            return $this->json([
                'success' => false,
                'message' => 'Non autorisé - Token invalide ou expiré'
            ], Response::HTTP_UNAUTHORIZED);
        }

        $payment = $this->paymentRepository->find($id);
        if (!$payment || $payment->getLease()?->getTenant()?->getId() !== $tenant->getId()) {
            return $this->json([
                'success' => false,
                'message' => 'Paiement introuvable'
            ], Response::HTTP_NOT_FOUND);
        }

        if ($payment->getStatus() === 'Payé') {
            return $this->json([
                'success' => false,
                'message' => 'Ce loyer a déjà été payé'
            ], Response::HTTP_BAD_REQUEST);
        }

        try {
            $transactionId = 'RENT-' . $payment->getId() . '-' . uniqid();
            $paymentMethod = (string) $request->query->get('method', 'mobile_money');

            // Créer l'enregistrement de transaction
            $onlinePayment = new OnlinePayment();
            $onlinePayment->setTransactionId($transactionId);
            $onlinePayment->setPaymentType('rent');
            $onlinePayment->setPaymentMethod($paymentMethod);
            $onlinePayment->setCurrency('XOF');
            $onlinePayment->setProvider('CinetPay');
            $onlinePayment->setStatus('pending');
            $onlinePayment->setLease($payment->getLease());
            $onlinePayment->setPayment($payment);
            $onlinePayment->setAmount($payment->getAmount());
            $onlinePayment->setCustomerName($tenant->getFullName());
            $onlinePayment->setCustomerPhone($tenant->getPhone());
            $onlinePayment->setCustomerEmail($tenant->getEmail());

            // URLs CinetPay
            $notifyUrl = $this->generateUrl('app_online_payment_notify', [], UrlGeneratorInterface::ABSOLUTE_URL);
            $returnUrl = $this->generateUrl('app_online_payment_return', ['transactionId' => $transactionId], UrlGeneratorInterface::ABSOLUTE_URL);

            // Paramétrer CinetPay
            $cinetpay
                ->setTransactionId($transactionId)
                ->setAmount((int) $payment->getAmount())
                ->setDescription("Paiement loyer - Bail #{$payment->getLease()->getId()}")
                ->setNotifyUrl($notifyUrl)
                ->setReturnUrl($returnUrl)
                ->setCustomer([
                    'customer_name' => $tenant->getLastName() ?? 'Locataire',
                    'customer_surname' => $tenant->getFirstName() ?? '',
                    'customer_phone_number' => $tenant->getPhone() ?? '22500000000',
                    'customer_email' => $tenant->getEmail() ?? 'noreply@app.lokapro.tech',
                    'customer_address' => $tenant->getAddress() ?? 'Adresse',
                    'customer_city' => $tenant->getCity() ?? 'Ville',
                    'customer_country' => 'CI',
                    'customer_state' => 'AB',
                    'customer_zip_code' => $tenant->getPostalCode() ?? '00000',
                ])
                ->setMetadata([
                    'payment_id' => $payment->getId(),
                    'lease_id' => $payment->getLease()->getId(),
                    'type' => 'rent',
                ]);

            $paymentUrl = $cinetpay->initPayment();

            $onlinePayment->setPaymentUrl($paymentUrl);
            $this->entityManager->persist($onlinePayment);
            $this->entityManager->flush();

            return $this->json([
                'success' => true,
                'payment' => [
                    'id' => $payment->getId(),
                    'amount' => (float) $payment->getAmount(),
                    'dueDate' => $payment->getDueDate()?->format('Y-m-d'),
                    'status' => $payment->getStatus(),
                ],
                'transaction' => [
                    'id' => $transactionId,
                    'provider' => 'CinetPay',
                    'method' => $paymentMethod,
                    'notifyUrl' => $notifyUrl,
                    'returnUrl' => $returnUrl,
                    'paymentUrl' => $paymentUrl,
                ]
            ]);
        } catch (\Exception $e) {
            return $this->json([
                'success' => false,
                'message' => 'Erreur lors de l\'initialisation du paiement',
                'error' => $e->getMessage(),
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    /**
     * Liste des demandes d'intervention du locataire
     * GET /api/tenant/requests
     */
    #[Route('/requests', name: 'requests', methods: ['GET'])]
    public function requests(Request $request): JsonResponse
    {
        $tenant = $this->getAuthenticatedTenant($request);
        if (!$tenant) {
            return $this->json([
                'success' => false,
                'message' => 'Non autorisé - Token invalide ou expiré'
            ], Response::HTTP_UNAUTHORIZED);
        }

        // Récupérer les demandes de maintenance
        $maintenanceRequests = $this->entityManager
            ->getRepository(\App\Entity\MaintenanceRequest::class)
            ->createQueryBuilder('m')
            ->join('m.property', 'p')
            ->join('p.leases', 'l')
            ->where('l.tenant = :tenant')
            ->andWhere('l.status = :status')
            ->setParameter('tenant', $tenant)
            ->setParameter('status', 'Actif')
            ->orderBy('m.createdAt', 'DESC')
            ->getQuery()
            ->getResult();

        $requests = [];
        foreach ($maintenanceRequests as $maintenance) {
            $requests[] = [
                'id' => $maintenance->getId(),
                'reference' => sprintf('#T000ZR%02dT', $maintenance->getId()),
                'title' => $maintenance->getTitle(),
                'category' => $maintenance->getCategory(),
                'description' => $maintenance->getDescription(),
                'status' => $maintenance->getStatus(),
                'priority' => $maintenance->getPriority(),
                'reportedDate' => $maintenance->getCreatedAt()->format('d/m/Y'),
                'scheduledDate' => $maintenance->getScheduledDate()?->format('d/m/Y'),
                'completedDate' => $maintenance->getCompletedDate()?->format('d/m/Y'),
                'property' => [
                    'address' => $maintenance->getProperty()->getFullAddress()
                ]
            ];
        }

        return $this->json([
            'success' => true,
            'requests' => $requests,
            'count' => count($requests),
            'statistics' => [
                'total' => count($requests),
                'pending' => count(array_filter($requests, fn($r) => $r['status'] === 'En attente')),
                'inProgress' => count(array_filter($requests, fn($r) => $r['status'] === 'En cours')),
                'completed' => count(array_filter($requests, fn($r) => $r['status'] === 'Terminé'))
            ]
        ]);
    }

    /**
     * Créer une nouvelle demande d'intervention
     * POST /api/tenant/requests
     */
    #[Route('/requests', name: 'request_create', methods: ['POST'])]
    public function createRequest(Request $request): JsonResponse
    {
        $tenant = $this->getAuthenticatedTenant($request);
        if (!$tenant) {
            return $this->json([
                'success' => false,
                'message' => 'Non autorisé - Token invalide ou expiré'
            ], Response::HTTP_UNAUTHORIZED);
        }

        $data = json_decode($request->getContent(), true);

        // Récupérer le bail actif
        $activeLease = $this->leaseRepository->findOneBy([
            'tenant' => $tenant,
            'status' => 'Actif'
        ]);

        if (!$activeLease) {
            return $this->json([
                'success' => false,
                'message' => 'Aucun bail actif trouvé'
            ], Response::HTTP_BAD_REQUEST);
        }

        $maintenance = new \App\Entity\MaintenanceRequest();
        $maintenance->setProperty($activeLease->getProperty());
        $maintenance->setTitle($data['title'] ?? 'Demande d\'intervention');
        $maintenance->setCategory($data['category'] ?? 'Autre');
        $maintenance->setDescription($data['description'] ?? '');
        $maintenance->setStatus('Nouvelle');
        $maintenance->setPriority($data['priority'] ?? 'Normale');
        $maintenance->setTenant($tenant);

        // Définir l'organisation en priorité depuis le bail, puis le tenant, puis le user, puis la propriété
        $organization = $activeLease->getOrganization()
            ?? $tenant->getOrganization()
            ?? ($tenant->getUser() ? $tenant->getUser()->getOrganization() : null)
            ?? $activeLease->getProperty()->getOrganization();

        if (!$organization) {
            // Log les informations pour debug
            $debugInfo = [
                'lease_has_org' => $activeLease->getOrganization() !== null,
                'tenant_has_org' => $tenant->getOrganization() !== null,
                'tenant_has_user' => $tenant->getUser() !== null,
                'user_has_org' => $tenant->getUser() ? $tenant->getUser()->getOrganization() !== null : false,
                'property_has_org' => $activeLease->getProperty()->getOrganization() !== null,
            ];

            return $this->json([
                'success' => false,
                'message' => 'Impossible de déterminer l\'organisation. Données manquantes.',
                'debug' => $debugInfo
            ], Response::HTTP_BAD_REQUEST);
        }

        $maintenance->setOrganization($organization);
        $maintenance->setCreatedAt(new \DateTime());
        $maintenance->setRequestedDate(new \DateTime());

        $this->entityManager->persist($maintenance);
        $this->entityManager->flush();

        return $this->json([
            'success' => true,
            'message' => 'Demande créée avec succès',
            'request' => [
                'id' => $maintenance->getId(),
                'reference' => sprintf('#T000ZR%02dT', $maintenance->getId()),
                'status' => $maintenance->getStatus()
            ]
        ], Response::HTTP_CREATED);
    }

    /**
     * Liste des documents du locataire
     * GET /api/tenant/documents
     */
    #[Route('/documents', name: 'documents', methods: ['GET'])]
    public function documents(Request $request): JsonResponse
    {
        $tenant = $this->getAuthenticatedTenant($request);
        if (!$tenant) {
            return $this->json([
                'success' => false,
                'message' => 'Non autorisé - Token invalide ou expiré'
            ], Response::HTTP_UNAUTHORIZED);
        }

        // Récupérer les documents liés au locataire
        $documents = $this->entityManager
            ->getRepository(\App\Entity\Document::class)
            ->createQueryBuilder('d')
            ->leftJoin('d.tenant', 't')
            ->where('t.id = :tenantId')
            ->setParameter('tenantId', $tenant->getId())
            ->orderBy('d.createdAt', 'DESC')
            ->getQuery()
            ->getResult();

        $documentsList = [];
        foreach ($documents as $doc) {
            $documentsList[] = [
                'id' => $doc->getId(),
                'name' => $doc->getName(),
                'type' => $doc->getType(),
                'fileName' => $doc->getFileName(),
                'fileSize' => $doc->getFileSize(),
                'uploadDate' => $doc->getCreatedAt()->format('d/m/Y H:i'),
                'description' => $doc->getDescription(),
                'downloadUrl' => $this->generateUrl('api_tenant_document_download', ['id' => $doc->getId()], true)
            ];
        }

        return $this->json([
            'success' => true,
            'documents' => $documentsList,
            'count' => count($documentsList)
        ]);
    }

    /**
     * Télécharger un document
     * GET /api/tenant/documents/{id}/download
     */
    #[Route('/documents/{id}/download', name: 'document_download', methods: ['GET'])]
    public function downloadDocument(int $id, Request $request): Response
    {
        $tenant = $this->getAuthenticatedTenant($request);
        if (!$tenant) {
            // Fallback: accepter le token via query string ?token=...
            $queryToken = $request->query->get('token');
            if ($queryToken) {
                $payload = $this->jwtService->verifyToken($queryToken);
                if ($payload && isset($payload['tenant_id'])) {
                    $tenant = $this->tenantRepository->find($payload['tenant_id']);
                }
            }
            if (!$tenant) {
                return $this->json([
                    'success' => false,
                    'message' => 'Non autorisé - Token invalide ou expiré'
                ], Response::HTTP_UNAUTHORIZED);
            }
        }

        $document = $this->entityManager->getRepository(\App\Entity\Document::class)->find($id);

        if (!$document || $document->getTenant()?->getId() !== $tenant->getId()) {
            return $this->json([
                'success' => false,
                'message' => 'Document non trouvé'
            ], Response::HTTP_NOT_FOUND);
        }

        // Chemin physique du fichier
        $filePath = $this->getParameter('kernel.project_dir') . '/public/uploads/documents/' . $document->getFileName();
        if (!file_exists($filePath)) {
            return $this->json([
                'success' => false,
                'message' => 'Fichier introuvable sur le serveur'
            ], Response::HTTP_NOT_FOUND);
        }

        // Réponse binaire avec en-têtes de téléchargement
        $response = new Response();
        $mimeType = $document->getMimeType() ?: 'application/octet-stream';
        $downloadName = $document->getOriginalFileName() ?: ($document->getName() ?: ('document_' . $document->getId())) ;
        $response->headers->set('Content-Type', $mimeType);
        $response->headers->set('Content-Disposition', sprintf('attachment; filename="%s"', addslashes($downloadName)));
        $response->headers->set('Content-Length', (string) filesize($filePath));
        $response->setContent(file_get_contents($filePath));
        return $response;
    }

    /**
     * Mon bien immobilier (propriété louée)
     * GET /api/tenant/property
     */
    #[Route('/property', name: 'property', methods: ['GET'])]
    public function property(Request $request): JsonResponse
    {
        $tenant = $this->getAuthenticatedTenant($request);
        if (!$tenant) {
            return $this->json([
                'success' => false,
                'message' => 'Non autorisé - Token invalide ou expiré'
            ], Response::HTTP_UNAUTHORIZED);
        }

        $activeLease = $this->leaseRepository->findOneBy([
            'tenant' => $tenant,
            'status' => 'Actif'
        ]);

        if (!$activeLease) {
            return $this->json([
                'success' => true,
                'property' => null,
                'message' => 'Aucun bail actif'
            ]);
        }

        $property = $activeLease->getProperty();

        return $this->json([
            'success' => true,
            'property' => [
                'id' => $property->getId(),
                'reference' => sprintf('N°%s', $property->getId()),
                'name' => $property->getFullAddress(),
                'address' => $property->getAddress(),
                'city' => $property->getCity(),
                'postalCode' => $property->getPostalCode(),
                'fullAddress' => $property->getFullAddress(),
                'type' => $property->getPropertyType(),
                // Dimensions et pièces
                'surface' => $property->getSurface(),
                'rooms' => $property->getRooms(),
                'bedrooms' => $property->getBedrooms(),
                'bathrooms' => $property->getBathrooms(),
                'toilets' => $property->getToilets(),
                // Étages
                'floor' => $property->getFloor(),
                'totalFloors' => $property->getTotalFloors(),
                // Extérieurs / surfaces additionnelles
                'balconies' => $property->getBalconies(),
                'terraceSurface' => $property->getTerraceSurface(),
                'gardenSurface' => $property->getGardenSurface(),
                'landSurface' => $property->getLandSurface(),
                // Stationnement / annexes
                'parkingSpaces' => $property->getParkingSpaces(),
                'garageSpaces' => $property->getGarageSpaces(),
                'cellarSurface' => $property->getCellarSurface(),
                'atticSurface' => $property->getAtticSurface(),
                // Construction / énergie
                'constructionYear' => $property->getConstructionYear(),
                'renovationYear' => $property->getRenovationYear(),
                'heatingType' => $property->getHeatingType(),
                'hotWaterType' => $property->getHotWaterType(),
                'energyClass' => $property->getEnergyClass(),
                'energyConsumption' => $property->getEnergyConsumption(),
                'orientation' => $property->getOrientation(),
                // Équipements booléens
                'furnished' => $property->isFurnished(),
                'petsAllowed' => $property->isPetsAllowed(),
                'smokingAllowed' => $property->isSmokingAllowed(),
                'elevator' => $property->isElevator(),
                'hasBalcony' => $property->isHasBalcony(),
                'hasParking' => $property->isHasParking(),
                'airConditioning' => $property->isAirConditioning(),
                'heating' => $property->isHeating(),
                'hotWater' => $property->isHotWater(),
                'internet' => $property->isInternet(),
                'cable' => $property->isCable(),
                'dishwasher' => $property->isDishwasher(),
                'washingMachine' => $property->isWashingMachine(),
                'dryer' => $property->isDryer(),
                'refrigerator' => $property->isRefrigerator(),
                'oven' => $property->isOven(),
                'microwave' => $property->isMicrowave(),
                'stove' => $property->isStove(),
                // Descriptions
                'description' => $property->getDescription(),
                'equipment' => $property->getEquipment(),
                'proximity' => $property->getProximity(),
                'restrictions' => $property->getRestrictions(),
                'notes' => $property->getNotes(),
                'equipmentList' => $property->getEquipmentList(),
                // Localisation étendue
                'country' => $property->getCountry(),
                'region' => $property->getRegion(),
                'district' => $property->getDistrict(),
                'latitude' => $property->getLatitude(),
                'longitude' => $property->getLongitude(),
                // Médias
                'photos' => $property->getPhotos() ?? [],
                'videoUrl' => $property->getVideoUrl(),
            ],
            'lease' => [
                'id' => $activeLease->getId(),
                'startDate' => $activeLease->getStartDate()->format('d/m/Y'),
                'endDate' => $activeLease->getEndDate()?->format('d/m/Y'),
                'monthlyRent' => $activeLease->getMonthlyRent(),
                'charges' => $activeLease->getCharges(),
                'deposit' => $activeLease->getDeposit(),
                'status' => $activeLease->getStatus()
            ]
        ]);
    }

    /**
     * Statistiques comptables
     * GET /api/tenant/accounting
     */
    #[Route('/accounting', name: 'accounting', methods: ['GET'])]
    public function accounting(Request $request): JsonResponse
    {
        $tenant = $this->getAuthenticatedTenant($request);
        if (!$tenant) {
            return $this->json([
                'success' => false,
                'message' => 'Non autorisé - Token invalide ou expiré'
            ], Response::HTTP_UNAUTHORIZED);
        }

        $includeEntries = (string) $request->query->get('entries', '0') === '1';
        $entriesLimit = max(1, min(100, (int) ($request->query->get('entriesLimit', 20))));

        // Utiliser le système comptable pour avoir les mêmes calculs que les contrôleurs web
        $tenantStats = $this->accountingEntryRepository->getTenantStatistics($tenant->getId());
        $balance = $tenantStats['balance'] ?? 0.0;

        // Calculer aussi à partir des paiements pour les informations supplémentaires
        $leases = $this->leaseRepository->findBy(['tenant' => $tenant]);
        $totalPaid = 0;
        $totalDue = 0;
        $lastPaymentDate = null;
        $nextPaymentDate = null;

        foreach ($leases as $lease) {
            $payments = $this->paymentRepository->findBy(['lease' => $lease]);

            foreach ($payments as $payment) {
                $totalDue += $payment->getAmount();

                if ($payment->getStatus() === 'Payé') {
                    $totalPaid += $payment->getAmount();
                    if (!$lastPaymentDate || $payment->getPaidDate() > $lastPaymentDate) {
                        $lastPaymentDate = $payment->getPaidDate();
                    }
                }

                if ($payment->getStatus() === 'En attente' &&
                    (!$nextPaymentDate || $payment->getDueDate() < $nextPaymentDate)) {
                    $nextPaymentDate = $payment->getDueDate();
                }
            }
        }

        $response = [
            'success' => true,
            'accounting' => [
                'balance' => $balance, // Solde comptable (cohérent avec les contrôleurs web)
                'totalPaid' => $totalPaid,
                'totalDue' => $totalDue,
                'toPay' => max(0, -$balance), // Montant à payer si balance négative
                'lastPaymentDate' => $lastPaymentDate?->format('d/m/Y'),
                'nextPaymentDate' => $nextPaymentDate?->format('d/m/Y'),
                'totalCredits' => $tenantStats['total_credits'] ?? 0,
                'totalDebits' => $tenantStats['total_debits'] ?? 0,
                'currentMonthCredits' => $tenantStats['current_month_credits'] ?? 0,
                'currentMonthDebits' => $tenantStats['current_month_debits'] ?? 0
            ]
        ];

        if ($includeEntries) {
            $entries = $this->accountingEntryRepository->findByTenantWithFilters($tenant->getId());
            $entries = array_slice($entries, 0, $entriesLimit);
            $response['entries'] = array_map(function ($e) {
                /** @var \App\Entity\AccountingEntry $e */
                $type = $e->getType();
                // Normalisation d'affichage: LOYER_ATTENDU et LOYER en DEBIT côté locataire si marqués en CREDIT
                $cat = strtoupper((string)$e->getCategory());
                if (($cat === 'LOYER_ATTENDU' || $cat === 'LOYER') && strtoupper((string)$type) === 'CREDIT') {
                    $type = 'DEBIT';
                }
                $amount = (float) $e->getAmount();
                $signed = ($type === 'CREDIT') ? $amount : -$amount;
                return [
                    'id' => $e->getId(),
                    'date' => $e->getEntryDate()?->format('Y-m-d'),
                    'type' => $type,
                    'category' => $e->getCategory(),
                    'amount' => $amount,
                    'signedAmount' => $signed,
                    'runningBalance' => method_exists($e, 'getRunningBalance') ? (float) $e->getRunningBalance() : null,
                    'reference' => $e->getReference(),
                    'description' => $e->getDescription(),
                ];
            }, $entries);
            $response['entriesLimit'] = $entriesLimit;
        }

        return $this->json($response);
    }

    /**
     * Écritures comptables détaillées du locataire (paginées)
     * GET /api/tenant/accounting/entries
     * Query params: page, perPage, type, category, year, month
     */
    #[Route('/accounting/entries', name: 'accounting_entries', methods: ['GET'])]
    public function accountingEntries(Request $request): JsonResponse
    {
        $tenant = $this->getAuthenticatedTenant($request);
        if (!$tenant) {
            return $this->json([
                'success' => false,
                'message' => 'Non autorisé - Token invalide ou expiré'
            ], Response::HTTP_UNAUTHORIZED);
        }

        $page = max(1, (int) $request->query->get('page', 1));
        $perPage = min(100, max(1, (int) $request->query->get('perPage', 20)));
        $type = $request->query->get('type'); // CREDIT|DEBIT|null
        $category = $request->query->get('category');
        $year = $request->query->get('year') !== null ? (int) $request->query->get('year') : null;
        $month = $request->query->get('month') !== null ? (int) $request->query->get('month') : null;

        $entries = $this->accountingEntryRepository->findByTenantWithFilters(
            $tenant->getId(),
            $type ?: null,
            $category ?: null,
            $year,
            $month
        );

        $total = count($entries);
        $totalPages = (int) ceil($total / $perPage);
        $offset = ($page - 1) * $perPage;
        $paged = array_slice($entries, $offset, $perPage);

        $items = array_map(function ($e) {
            /** @var \App\Entity\AccountingEntry $e */
            $type = $e->getType();
            $cat = strtoupper((string)$e->getCategory());
            if (($cat === 'LOYER_ATTENDU' || $cat === 'LOYER') && strtoupper((string)$type) === 'CREDIT') {
                $type = 'DEBIT';
            }
            $amount = (float) $e->getAmount();
            $signed = ($type === 'CREDIT') ? $amount : -$amount;
            return [
                'id' => $e->getId(),
                'date' => $e->getEntryDate()?->format('Y-m-d'),
                'type' => $type, // CREDIT|DEBIT
                'category' => $e->getCategory(),
                'amount' => $amount,
                'signedAmount' => $signed,
                'runningBalance' => method_exists($e, 'getRunningBalance') ? (float) $e->getRunningBalance() : null,
                'reference' => $e->getReference(),
                'description' => $e->getDescription(),
                'notes' => method_exists($e, 'getNotes') ? $e->getNotes() : null,
            ];
        }, $paged);

        return $this->json([
            'success' => true,
            'pagination' => [
                'page' => $page,
                'perPage' => $perPage,
                'total' => $total,
                'totalPages' => $totalPages,
            ],
            'filters' => [
                'type' => $type,
                'category' => $category,
                'year' => $year,
                'month' => $month,
            ],
            'entries' => $items,
        ]);
    }

    /**
     * Paramètres/infos globales de l'application organisation (branding)
     * GET /api/tenant/settings
     */
    #[Route('/settings', name: 'settings', methods: ['GET'])]
    public function settings(): JsonResponse
    {
        return $this->json([
            'success' => true,
            'settings' => $this->buildMobileSettings(),
        ]);
    }

    /**
     * Construit la structure des paramètres mobiles exposés à l'app Flutter
     */
    private function buildMobileSettings(): array
    {
        // Organisation / branding
        $org = [
            'name' => $this->settingsService->get('company_name', 'LOKAPRO Gestion'),
            'logo' => $this->settingsService->get('app_logo', ''),
            'address' => $this->settingsService->get('company_address', ''),
            'city' => $this->settingsService->get('company_city', ''),
            'postalCode' => $this->settingsService->get('company_postal_code', ''),
            'country' => $this->settingsService->get('company_country', ''),
            'phone' => $this->settingsService->get('company_phone', ''),
            'email' => $this->settingsService->get('company_email', ''),
            'website' => $this->settingsService->get('company_website', ''),
            'support' => [
                'email' => $this->settingsService->get('support_email', $this->settingsService->get('company_email', '')),
                'phone' => $this->settingsService->get('support_phone', $this->settingsService->get('company_phone', '')),
            ],
            'legal' => [
                'termsUrl' => $this->settingsService->get('terms_url', ''),
                'privacyUrl' => $this->settingsService->get('privacy_url', ''),
            ],
        ];

        // Localisation / devise / formats
        $locFromService = $this->currencyService->getLocalizationSettings();
        $localization = [
            'defaultCurrency' => $locFromService['default_currency'] ?? $this->settingsService->get('default_currency', 'XOF'),
            'cinetpayCurrency' => $this->settingsService->get('cinetpay_currency', 'XOF'),
            'dateFormat' => $locFromService['date_format'] ?? $this->settingsService->get('date_format', 'd/m/Y'),
            'timeFormat' => $locFromService['time_format'] ?? $this->settingsService->get('time_format', 'H:i'),
            'timezone' => $locFromService['timezone'] ?? $this->settingsService->get('timezone', 'Europe/Paris'),
            'locale' => $locFromService['locale'] ?? $this->settingsService->get('locale', 'fr_FR'),
            'decimalSeparator' => $locFromService['decimal_separator'] ?? $this->settingsService->get('decimal_separator', ','),
            'thousandsSeparator' => $locFromService['thousands_separator'] ?? $this->settingsService->get('thousands_separator', ' '),
        ];

        // Paiements
        $payments = [
            'defaultRentDueDay' => $this->settingsService->get('default_rent_due_day', 1),
            'lateFeeRate' => (float) $this->settingsService->get('late_fee_rate', 5.0),
            'autoGenerateRent' => (bool) $this->settingsService->get('auto_generate_rent', true),
            'paymentReminderDays' => (int) $this->settingsService->get('payment_reminder_days', 7),
            'allowPartialPayments' => (bool) $this->settingsService->get('allow_partial_payments', false),
            'minimumPaymentAmount' => (float) $this->settingsService->get('minimum_payment_amount', 10),
            'allowAdvancePayments' => (bool) $this->settingsService->get('allow_advance_payments', true),
            'minimumAdvanceAmount' => (float) $this->settingsService->get('minimum_advance_amount', 50),
        ];

        // Passerelles de paiement (CinetPay)
        $gateways = [
            'cinetpay' => [
                'enabled' => (bool) $this->settingsService->get('cinetpay_enabled', true),
                'environment' => $this->settingsService->get('cinetpay_environment', 'test'),
                'currency' => $this->settingsService->get('cinetpay_currency', 'XOF'),
                'channels' => $this->settingsService->get('cinetpay_channels', 'ALL'),
                'siteId' => $this->settingsService->get('cinetpay_site_id', ''),
            ],
        ];

        // Fonctionnalités
        $features = [
            'urgentMaintenanceNotification' => (bool) $this->settingsService->get('urgent_notification', true),
            'autoAssignMaintenance' => (bool) $this->settingsService->get('auto_assign_maintenance', false),
            'emailNotifications' => (bool) $this->settingsService->get('email_notifications', true),
            'registrationEnabled' => (bool) $this->settingsService->get('registration_enabled', true),
        ];

        // Application
        $app = [
            'name' => $this->settingsService->get('app_name', 'LOKAPRO'),
            'description' => $this->settingsService->get('app_description', 'Logiciel de gestion locative professionnel'),
            'maintenanceMode' => (bool) $this->settingsService->get('maintenance_mode', false),
            'version' => '1.0.0',
        ];

        return [
            'organization' => $org,
            'localization' => $localization,
            'payments' => $payments,
            'gateways' => $gateways,
            'features' => $features,
            'app' => $app,
        ];
    }

    /**
     * Méthode privée pour récupérer le locataire authentifié depuis le JWT
     */
    private function getAuthenticatedTenant(Request $request): ?Tenant
    {
        // Extraire le token du header Authorization
        $authHeader = $request->headers->get('Authorization');
        $token = $this->jwtService->extractTokenFromHeader($authHeader);

        if (!$token) {
            return null;
        }

        // Vérifier et décoder le token
        $payload = $this->jwtService->verifyToken($token);

        if (!$payload || !isset($payload['tenant_id'])) {
            return null;
        }

        // Récupérer le locataire depuis la BDD
        return $this->tenantRepository->find($payload['tenant_id']);
    }
}
