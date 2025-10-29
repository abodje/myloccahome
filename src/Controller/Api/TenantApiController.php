<?php

namespace App\Controller\Api;

use App\Entity\Tenant;
use App\Entity\User;
use App\Repository\LeaseRepository;
use App\Repository\PaymentRepository;
use App\Repository\PropertyRepository;
use App\Repository\TenantRepository;
use App\Service\JwtService;
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
        private JwtService $jwtService
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
            ]
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

            // Calculer les soldes
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

            $dashboard['balances'] = [
                'soldAt' => $totalPaid - $totalDue, // Solde (négatif si dette)
                'toPay' => max(0, $totalDue - $totalPaid) // Montant à payer
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

        return $this->json([
            'success' => true,
            'statistics' => [
                'total' => $total,
                'paid' => $paid,
                'pending' => $pending,
                'balance' => $paid - $total
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
        $maintenance->setOrganization($activeLease->getProperty()->getOrganization());
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
            return $this->json([
                'success' => false,
                'message' => 'Non autorisé - Token invalide ou expiré'
            ], Response::HTTP_UNAUTHORIZED);
        }

        $document = $this->entityManager->getRepository(\App\Entity\Document::class)->find($id);

        if (!$document || $document->getTenant()?->getId() !== $tenant->getId()) {
            return $this->json([
                'success' => false,
                'message' => 'Document non trouvé'
            ], Response::HTTP_NOT_FOUND);
        }

        // TODO: Implémenter le téléchargement réel du fichier
        return $this->json([
            'success' => true,
            'message' => 'Téléchargement disponible',
            'document' => [
                'id' => $document->getId(),
                'name' => $document->getName(),
                'fileName' => $document->getFileName()
            ]
        ]);
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
                'rooms' => $property->getRooms(),
                'bedrooms' => $property->getBedrooms(),
                'bathrooms' => $property->getBathrooms(),
                'surface' => $property->getSurface(),
                'floor' => $property->getFloor(),
                'hasElevator' => $property->isHasElevator(),
                'hasParking' => $property->isHasParking(),
                'hasGarden' => $property->isHasGarden(),
                'description' => $property->getDescription(),
                'photos' => $property->getPhotos() ?? []
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

        return $this->json([
            'success' => true,
            'accounting' => [
                'balance' => $totalPaid - $totalDue,
                'totalPaid' => $totalPaid,
                'totalDue' => $totalDue,
                'toPay' => max(0, $totalDue - $totalPaid),
                'lastPaymentDate' => $lastPaymentDate?->format('d/m/Y'),
                'nextPaymentDate' => $nextPaymentDate?->format('d/m/Y')
            ]
        ]);
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
