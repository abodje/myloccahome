<?php

namespace App\Controller;

use App\Repository\PaymentRepository;
use App\Repository\LeaseRepository;
use App\Repository\MaintenanceRequestRepository;
use App\Repository\PropertyRepository;
use App\Entity\User;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/calendrier')]
class CalendarController extends AbstractController
{
    #[Route('/', name: 'app_calendar_index', methods: ['GET'])]
    public function index(): Response
    {
        /** @var User|null $user */
        $user = $this->getUser();
        $userInfo = null;

        if ($user) {
            $criteria = $this->getUserFilterCriteria($user);
            $userInfo = [
                'email' => $user->getEmail(),
                'roles' => $user->getRoles(),
                'organization' => $user->getOrganization() ? $user->getOrganization()->getName() : null,
                'company' => $user->getCompany() ? $user->getCompany()->getName() : null,
                'criteria' => $criteria
            ];
        }

        return $this->render('calendar/index.html.twig', [
            'user_info' => $userInfo
        ]);
    }

    /**
     * API pour récupérer les événements du calendrier
     */
    #[Route('/events', name: 'app_calendar_events', methods: ['GET'])]
    public function events(
        Request $request,
        PaymentRepository $paymentRepo,
        LeaseRepository $leaseRepo,
        MaintenanceRequestRepository $maintenanceRepo,
        PropertyRepository $propertyRepo
    ): JsonResponse {
        try {
            $start = $request->query->get('start');
            $end = $request->query->get('end');
            $filters = $request->query->all('filters') ?? [];

            // Parser les dates ISO 8601 correctement (avec timezone)
            if ($start) {
                // Extraire seulement la partie date si format ISO complet
                $startDate = \DateTime::createFromFormat(\DateTime::ATOM, $start);
                if (!$startDate) {
                    // Fallback : extraire juste YYYY-MM-DD
                    $startDateStr = substr($start, 0, 10);
                    $startDate = new \DateTime($startDateStr);
                }
            } else {
                $startDate = new \DateTime('-1 month');
            }

            if ($end) {
                // Extraire seulement la partie date si format ISO complet
                $endDate = \DateTime::createFromFormat(\DateTime::ATOM, $end);
                if (!$endDate) {
                    // Fallback : extraire juste YYYY-MM-DD
                    $endDateStr = substr($end, 0, 10);
                    $endDate = new \DateTime($endDateStr);
                }
            } else {
                $endDate = new \DateTime('+2 months');
            }

            $events = [];

            // Filtrer par rôle utilisateur
            /** @var User|null $user */
            $user = $this->getUser();

            // Log pour débogage
            if ($user) {
                error_log("Calendar - User: " . $user->getEmail() .
                         ", Roles: " . implode(', ', $user->getRoles()) .
                         ", Organization: " . ($user->getOrganization() ? $user->getOrganization()->getName() : 'None') .
                         ", Company: " . ($user->getCompany() ? $user->getCompany()->getName() : 'None'));
            }

            // 1. PAIEMENTS
            if (!isset($filters['types']) || in_array('payments', $filters['types'])) {
                $payments = $this->getPaymentsForCalendar($paymentRepo, $startDate, $endDate, $user);
                $events = array_merge($events, $payments);
            }

            // 2. EXPIRATIONS DE BAUX
            if (!isset($filters['types']) || in_array('leases', $filters['types'])) {
                $leases = $this->getLeasesForCalendar($leaseRepo, $startDate, $endDate, $user);
                $events = array_merge($events, $leases);
            }

            // 3. MAINTENANCES
            if (!isset($filters['types']) || in_array('maintenance', $filters['types'])) {
                $maintenances = $this->getMaintenanceForCalendar($maintenanceRepo, $startDate, $endDate, $user);
                $events = array_merge($events, $maintenances);
            }

            // 4. BIENS (pour les locataires)
            if (!isset($filters['types']) || in_array('properties', $filters['types'])) {
                $properties = $this->getPropertiesForCalendar($propertyRepo, $leaseRepo, $startDate, $endDate, $user);
                $events = array_merge($events, $properties);
            }

            return new JsonResponse($events);
        } catch (\Exception $e) {
            // Retourner une réponse d'erreur claire
            return new JsonResponse([
                'error' => true,
                'message' => $e->getMessage(),
                'events' => []
            ], 500);
        }
    }

    /**
     * Récupère les paiements pour le calendrier
     */
    private function getPaymentsForCalendar($paymentRepo, $startDate, $endDate, $user): array
    {
        $payments = [];

        // Récupérer les paiements selon le rôle
        if ($user && in_array('ROLE_TENANT', $user->getRoles())) {
            // LOCATAIRE : Voir uniquement ses propres paiements
            $tenant = $user->getTenant();
            if ($tenant) {
                $allPayments = $paymentRepo->findByTenantWithFilters($tenant->getId());
                error_log("Calendar Payments - Tenant: " . count($allPayments) . " payments found for tenant " . $tenant->getFullName());
            } else {
                error_log("Calendar Payments - Tenant: No tenant found for user " . $user->getEmail());
                return [];
            }
        } elseif ($user && in_array('ROLE_MANAGER', $user->getRoles())) {
            // MANAGER : Voir les paiements de SA company
            $owner = $user->getOwner();
            if ($owner) {
                $allPayments = $paymentRepo->findByManagerWithFilters($owner->getId());
            } else {
                return [];
            }
        } elseif ($user && in_array('ROLE_ADMIN', $user->getRoles())) {
            // ADMIN : Filtrer par organization/company
            $criteria = $this->getUserFilterCriteria($user);

            if ($criteria['organization']) {
                $qb = $this->createFilteredQuery($paymentRepo, 'p', $criteria);
                $allPayments = $qb->getQuery()->getResult();

                error_log("Calendar Payments - Admin filtered: " . count($allPayments) . " payments found for " .
                         ($criteria['company'] ? 'company ' . $criteria['company']->getName() : 'organization ' . $criteria['organization']->getName()));
            } else {
                // Super admin : tous les paiements
                $allPayments = $paymentRepo->findAll();
                error_log("Calendar Payments - Super admin: " . count($allPayments) . " payments found");
            }
        } else {
            // Utilisateur sans rôle spécifique
            return [];
        }

        foreach ($allPayments as $payment) {
            try {
                $dueDate = $payment->getDueDate();

                if (!$dueDate || $dueDate < $startDate || $dueDate > $endDate) {
                    continue;
                }

                $lease = $payment->getLease();
                if (!$lease) {
                    continue; // Skip si pas de bail
                }

                $tenant = $lease->getTenant();
                $property = $lease->getProperty();

                $color = match($payment->getStatus()) {
                    'Payé' => '#28a745',        // Vert
                    'En attente' => '#ffc107',  // Jaune
                    'En retard' => '#dc3545',   // Rouge
                    default => '#6c757d'        // Gris
                };

                $icon = match($payment->getStatus()) {
                    'Payé' => '✓',
                    'En attente' => '⏰',
                    'En retard' => '⚠️',
                    default => '💰'
                };

                // Obtenir la devise (avec fallback)
                $currency = 'FCFA'; // Valeur par défaut
                if (method_exists($payment, 'getCurrency')) {
                    $currency = $payment->getCurrency();
                } elseif ($lease && method_exists($lease, 'getCurrency')) {
                    $currency = $lease->getCurrency();
                }

                $payments[] = [
                    'id' => 'payment-' . $payment->getId(),
                    'title' => $icon . ' ' . number_format($payment->getAmount(), 0, ',', ' ') . ' ' . $currency,
                    'start' => $dueDate->format('Y-m-d'),
                    'backgroundColor' => $color,
                    'borderColor' => $color,
                    'extendedProps' => [
                        'type' => 'payment',
                        'status' => $payment->getStatus(),
                        'amount' => $payment->getAmount(),
                        'tenant' => $tenant ? $tenant->getFullName() : 'N/A',
                        'property' => $property ? $property->getAddress() : 'N/A',
                        'paymentId' => $payment->getId()
                    ]
                ];
            } catch (\Exception $e) {
                // Skip ce paiement en cas d'erreur
                continue;
            }
        }

        return $payments;
    }

    /**
     * Récupère les baux pour le calendrier (dates d'expiration)
     */
    private function getLeasesForCalendar($leaseRepo, $startDate, $endDate, $user): array
    {
        $leases = [];

        // Récupérer selon le rôle
        if ($user && in_array('ROLE_TENANT', $user->getRoles())) {
            // LOCATAIRE : Voir uniquement ses propres baux
            $tenant = $user->getTenant();
            if ($tenant) {
                $allLeases = $leaseRepo->findBy(['tenant' => $tenant]);
                error_log("Calendar Leases - Tenant: " . count($allLeases) . " leases found for tenant " . $tenant->getFullName());
            } else {
                error_log("Calendar Leases - Tenant: No tenant found for user " . $user->getEmail());
                return [];
            }
        } elseif ($user && in_array('ROLE_MANAGER', $user->getRoles())) {
            // MANAGER : Voir les baux de SA company
            $owner = $user->getOwner();
            if ($owner) {
                $allLeases = $leaseRepo->findByManager($owner->getId());
            } else {
                return [];
            }
        } elseif ($user && in_array('ROLE_ADMIN', $user->getRoles())) {
            // ADMIN : Filtrer par organization/company
            $criteria = $this->getUserFilterCriteria($user);

            if ($criteria['organization']) {
                $qb = $this->createFilteredQuery($leaseRepo, 'l', $criteria);
                $allLeases = $qb->getQuery()->getResult();

                error_log("Calendar Leases - Admin filtered: " . count($allLeases) . " leases found for " .
                         ($criteria['company'] ? 'company ' . $criteria['company']->getName() : 'organization ' . $criteria['organization']->getName()));
            } else {
                // Super admin : tous les baux
                $allLeases = $leaseRepo->findAll();
                error_log("Calendar Leases - Super admin: " . count($allLeases) . " leases found");
            }
        } else {
            // Utilisateur sans rôle spécifique
            return [];
        }

        foreach ($allLeases as $lease) {
            try {
                $endDate_lease = $lease->getEndDate();

                if (!$endDate_lease || $endDate_lease < $startDate || $endDate_lease > $endDate) {
                    continue;
                }

                $tenant = $lease->getTenant();
                $property = $lease->getProperty();

                if (!$tenant) {
                    continue; // Skip si pas de locataire
                }

                // Calculer la couleur selon la proximité
                $now = new \DateTime();
                $daysUntil = $endDate_lease > $now ? $endDate_lease->diff($now)->days : 0;
                $color = $daysUntil <= 30 ? '#dc3545' : ($daysUntil <= 60 ? '#ffc107' : '#17a2b8');

                $leases[] = [
                    'id' => 'lease-' . $lease->getId(),
                    'title' => '📄 Expiration bail - ' . $tenant->getFullName(),
                    'start' => $endDate_lease->format('Y-m-d'),
                    'backgroundColor' => $color,
                    'borderColor' => $color,
                    'extendedProps' => [
                        'type' => 'lease',
                        'tenant' => $tenant->getFullName(),
                        'property' => $property ? $property->getAddress() : 'N/A',
                        'startDate' => $lease->getStartDate() ? $lease->getStartDate()->format('d/m/Y') : 'N/A',
                        'endDate' => $endDate_lease->format('d/m/Y'),
                        'leaseId' => $lease->getId()
                    ]
                ];
            } catch (\Exception $e) {
                // Skip ce bail en cas d'erreur
                continue;
            }
        }

        return $leases;
    }

    /**
     * Récupère les maintenances pour le calendrier
     */
    private function getMaintenanceForCalendar($maintenanceRepo, $startDate, $endDate, $user): array
    {
        $maintenances = [];

        // Récupérer selon le rôle
        if ($user && in_array('ROLE_TENANT', $user->getRoles())) {
            // LOCATAIRE : Voir uniquement ses propres demandes de maintenance
            $tenant = $user->getTenant();
            if ($tenant) {
                $allMaintenances = $maintenanceRepo->findByTenantWithFilters($tenant->getId());
                error_log("Calendar Maintenance - Tenant: " . count($allMaintenances) . " maintenances found for tenant " . $tenant->getFullName());
            } else {
                error_log("Calendar Maintenance - Tenant: No tenant found for user " . $user->getEmail());
                return [];
            }
        } elseif ($user && in_array('ROLE_MANAGER', $user->getRoles())) {
            // MANAGER : Voir les maintenances de SA company
            $owner = $user->getOwner();
            if ($owner) {
                $allMaintenances = $maintenanceRepo->findByManagerWithFilters($owner->getId());
            } else {
                return [];
            }
        } elseif ($user && in_array('ROLE_ADMIN', $user->getRoles())) {
            // ADMIN : Filtrer par organization/company
            $criteria = $this->getUserFilterCriteria($user);

            if ($criteria['organization']) {
                $qb = $this->createFilteredQuery($maintenanceRepo, 'm', $criteria);
                $allMaintenances = $qb->getQuery()->getResult();

                error_log("Calendar Maintenance - Admin filtered: " . count($allMaintenances) . " maintenances found for " .
                         ($criteria['company'] ? 'company ' . $criteria['company']->getName() : 'organization ' . $criteria['organization']->getName()));
            } else {
                // Super admin : toutes les maintenances
                $allMaintenances = $maintenanceRepo->findAll();
                error_log("Calendar Maintenance - Super admin: " . count($allMaintenances) . " maintenances found");
            }
        } else {
            // Utilisateur sans rôle spécifique
            return [];
        }

        foreach ($allMaintenances as $maintenance) {
            try {
                // Récupérer la date (scheduledDate ou createdAt)
                $scheduledDate = null;
                if (method_exists($maintenance, 'getScheduledDate')) {
                    $scheduledDate = $maintenance->getScheduledDate();
                }
                if (!$scheduledDate) {
                    $scheduledDate = $maintenance->getCreatedAt();
                }

                if (!$scheduledDate || $scheduledDate < $startDate || $scheduledDate > $endDate) {
                    continue;
                }

                $property = $maintenance->getProperty();

                $color = match($maintenance->getStatus()) {
                    'Nouvelle' => '#dc3545',     // Rouge (urgent)
                    'En cours' => '#ffc107',     // Jaune
                    'Terminée' => '#28a745',     // Vert
                    default => '#6c757d'         // Gris
                };

                $icon = match($maintenance->getStatus()) {
                    'Nouvelle' => '🔴',
                    'En cours' => '🔧',
                    'Terminée' => '✅',
                    default => '🛠️'
                };

                // Obtenir le titre
                $title = 'Maintenance';
                if (method_exists($maintenance, 'getTitle') && $maintenance->getTitle()) {
                    $title = $maintenance->getTitle();
                } elseif (method_exists($maintenance, 'getCategory') && $maintenance->getCategory()) {
                    $title = $maintenance->getCategory();
                }

                $maintenances[] = [
                    'id' => 'maintenance-' . $maintenance->getId(),
                    'title' => $icon . ' ' . $title,
                    'start' => $scheduledDate->format('Y-m-d'),
                    'backgroundColor' => $color,
                    'borderColor' => $color,
                    'extendedProps' => [
                        'type' => 'maintenance',
                        'status' => $maintenance->getStatus(),
                        'property' => $property ? $property->getAddress() : 'N/A',
                        'description' => $maintenance->getDescription() ?? 'Pas de description',
                        'maintenanceId' => $maintenance->getId()
                    ]
                ];
            } catch (\Exception $e) {
                // Skip cette maintenance en cas d'erreur
                continue;
            }
        }

        return $maintenances;
    }

    /**
     * Récupère les biens pour le calendrier
     */
    private function getPropertiesForCalendar($propertyRepo, $leaseRepo, $startDate, $endDate, $user): array
    {
        $properties = [];

        // Récupérer selon le rôle
        if ($user && in_array('ROLE_TENANT', $user->getRoles())) {
            // LOCATAIRE : Voir uniquement les biens qu'il loue
            $tenant = $user->getTenant();
            if ($tenant) {
                // Récupérer les baux du locataire pour obtenir ses biens
                $tenantLeases = $leaseRepo->findBy(['tenant' => $tenant]);
                $tenantProperties = [];

                foreach ($tenantLeases as $lease) {
                    if ($lease->getProperty()) {
                        $tenantProperties[] = $lease->getProperty();
                    }
                }

                error_log("Calendar Properties - Tenant: " . count($tenantProperties) . " properties found for tenant " . $tenant->getFullName());
            } else {
                return [];
            }
        } elseif ($user && in_array('ROLE_MANAGER', $user->getRoles())) {
            // MANAGER : Voir les biens de SA company
            $owner = $user->getOwner();
            if ($owner) {
                $tenantProperties = $propertyRepo->findByManager($owner->getId());
                error_log("Calendar Properties - Manager: " . count($tenantProperties) . " properties found for manager");
            } else {
                return [];
            }
        } elseif ($user && in_array('ROLE_ADMIN', $user->getRoles())) {
            // ADMIN : Filtrer par organization/company
            $criteria = $this->getUserFilterCriteria($user);

            if ($criteria['organization']) {
                $qb = $this->createFilteredQuery($propertyRepo, 'p', $criteria);
                $tenantProperties = $qb->getQuery()->getResult();

                error_log("Calendar Properties - Admin filtered: " . count($tenantProperties) . " properties found for " .
                         ($criteria['company'] ? 'company ' . $criteria['company']->getName() : 'organization ' . $criteria['organization']->getName()));
            } else {
                // Super admin : tous les biens
                $tenantProperties = $propertyRepo->findAll();
                error_log("Calendar Properties - Super admin: " . count($tenantProperties) . " properties found");
            }
        } else {
            // Utilisateur sans rôle spécifique
            return [];
        }

        foreach ($tenantProperties as $property) {
            try {
                // Créer un événement pour chaque bien (date de création ou dernière mise à jour)
                $eventDate = $property->getCreatedAt() ?? new \DateTime();

                if ($eventDate < $startDate || $eventDate > $endDate) {
                    continue;
                }

                $properties[] = [
                    'id' => 'property-' . $property->getId(),
                    'title' => '🏠 Bien - ' . $property->getAddress(),
                    'start' => $eventDate->format('Y-m-d'),
                    'backgroundColor' => '#17a2b8', // Bleu pour les biens
                    'borderColor' => '#17a2b8',
                    'extendedProps' => [
                        'type' => 'property',
                        'address' => $property->getAddress(),
                        'description' => $property->getDescription() ?? 'Pas de description',
                        'propertyId' => $property->getId(),
                        'rent' => $property->getRent() ?? 0,
                        'area' => $property->getArea() ?? 0,
                        'rooms' => $property->getRooms() ?? 0
                    ]
                ];
            } catch (\Exception $e) {
                // Skip ce bien en cas d'erreur
                continue;
            }
        }

        return $properties;
    }

    /**
     * Méthode utilitaire pour obtenir les critères de filtrage selon l'utilisateur
     */
    private function getUserFilterCriteria($user): array
    {
        $criteria = [
            'organization' => null,
            'company' => null,
            'user_type' => 'unknown'
        ];

        if (!$user) {
            return $criteria;
        }

        $criteria['user_type'] = 'authenticated';

        // Déterminer le type d'utilisateur et les critères de filtrage
        if (in_array('ROLE_TENANT', $user->getRoles())) {
            $criteria['user_type'] = 'tenant';
        } elseif (in_array('ROLE_MANAGER', $user->getRoles())) {
            $criteria['user_type'] = 'manager';
        } elseif (in_array('ROLE_ADMIN', $user->getRoles())) {
            $criteria['user_type'] = 'admin';

            // Pour les admins, récupérer l'organisation et la société
            if ($user->getOrganization()) {
                $criteria['organization'] = $user->getOrganization();

                if ($user->getCompany()) {
                    $criteria['company'] = $user->getCompany();
                }
            }
        }

        return $criteria;
    }

    /**
     * Méthode utilitaire pour créer une requête filtrée par organisation/company
     */
    private function createFilteredQuery($repository, $alias, $criteria)
    {
        $qb = $repository->createQueryBuilder($alias);

        if ($criteria['company']) {
            // Filtrer par société spécifique
            $qb->where($alias . '.company = :company')
               ->setParameter('company', $criteria['company']);
        } elseif ($criteria['organization']) {
            // Filtrer par organisation
            $qb->where($alias . '.organization = :organization')
               ->setParameter('organization', $criteria['organization']);
        }

        return $qb;
    }

    /**
     * Route de debug pour tester le filtrage par organisation/company
     */
    #[Route('/debug/filter', name: 'app_calendar_debug_filter', methods: ['GET'])]
    public function debugFilter(): JsonResponse
    {
        /** @var User|null $user */
        $user = $this->getUser();

        if (!$user) {
            return new JsonResponse(['error' => 'User not authenticated']);
        }

        $criteria = $this->getUserFilterCriteria($user);

        $debugInfo = [
            'user' => [
                'id' => $user->getId(),
                'email' => $user->getEmail(),
                'roles' => $user->getRoles(),
                'organization_id' => $user->getOrganization() ? $user->getOrganization()->getId() : null,
                'organization_name' => $user->getOrganization() ? $user->getOrganization()->getName() : null,
                'company_id' => $user->getCompany() ? $user->getCompany()->getId() : null,
                'company_name' => $user->getCompany() ? $user->getCompany()->getName() : null,
            ],
            'filter_criteria' => $criteria,
            'filter_description' => $this->getFilterDescription($criteria)
        ];

        return new JsonResponse($debugInfo);
    }

    /**
     * Route de debug spécifique pour les locataires
     */
    #[Route('/debug/tenant-data', name: 'app_calendar_debug_tenant', methods: ['GET'])]
    public function debugTenantData(PaymentRepository $paymentRepo, LeaseRepository $leaseRepo, MaintenanceRequestRepository $maintenanceRepo, PropertyRepository $propertyRepo): JsonResponse
    {
        /** @var User|null $user */
        $user = $this->getUser();

        if (!$user) {
            return new JsonResponse(['error' => 'User not authenticated']);
        }

        if (!in_array('ROLE_TENANT', $user->getRoles())) {
            return new JsonResponse(['error' => 'User is not a tenant']);
        }

        $tenant = $user->getTenant();
        if (!$tenant) {
            return new JsonResponse(['error' => 'No tenant profile found for this user']);
        }

        // Récupérer toutes les données du locataire
        $payments = $paymentRepo->findByTenantWithFilters($tenant->getId());
        $leases = $leaseRepo->findBy(['tenant' => $tenant]);
        $maintenances = $maintenanceRepo->findByTenantWithFilters($tenant->getId());

        // Récupérer les biens via les baux
        $properties = [];
        foreach ($leases as $lease) {
            if ($lease->getProperty()) {
                $properties[] = $lease->getProperty();
            }
        }

        $debugInfo = [
            'user' => [
                'id' => $user->getId(),
                'email' => $user->getEmail(),
                'roles' => $user->getRoles(),
            ],
            'tenant' => [
                'id' => $tenant->getId(),
                'fullName' => $tenant->getFullName(),
                'phone' => $tenant->getPhone(),
                'email' => $tenant->getEmail(),
            ],
            'data_counts' => [
                'payments' => count($payments),
                'leases' => count($leases),
                'maintenances' => count($maintenances),
                'properties' => count($properties),
            ],
            'details' => [
                'payments' => array_map(function($p) {
                    return [
                        'id' => $p->getId(),
                        'amount' => $p->getAmount(),
                        'status' => $p->getStatus(),
                        'dueDate' => $p->getDueDate() ? $p->getDueDate()->format('Y-m-d') : null,
                        'lease_id' => $p->getLease() ? $p->getLease()->getId() : null,
                    ];
                }, $payments),
                'leases' => array_map(function($l) {
                    return [
                        'id' => $l->getId(),
                        'startDate' => $l->getStartDate() ? $l->getStartDate()->format('Y-m-d') : null,
                        'endDate' => $l->getEndDate() ? $l->getEndDate()->format('Y-m-d') : null,
                        'property_id' => $l->getProperty() ? $l->getProperty()->getId() : null,
                        'property_address' => $l->getProperty() ? $l->getProperty()->getAddress() : null,
                    ];
                }, $leases),
                'properties' => array_map(function($p) {
                    return [
                        'id' => $p->getId(),
                        'address' => $p->getAddress(),
                        'description' => $p->getDescription(),
                        'rent' => $p->getRent(),
                    ];
                }, $properties),
            ]
        ];

        return new JsonResponse($debugInfo);
    }

    /**
     * Génère une description textuelle des critères de filtrage
     */
    private function getFilterDescription(array $criteria): string
    {
        switch ($criteria['user_type']) {
            case 'tenant':
                return 'Utilisateur LOCATAIRE : Voit uniquement ses propres données';
            case 'manager':
                return 'Utilisateur MANAGER : Voit les données de sa société';
            case 'admin':
                if ($criteria['company']) {
                    return 'Utilisateur ADMIN : Voit les données de la société "' . $criteria['company']->getName() . '"';
                } elseif ($criteria['organization']) {
                    return 'Utilisateur ADMIN : Voit toutes les données de l\'organisation "' . $criteria['organization']->getName() . '"';
                } else {
                    return 'Utilisateur SUPER ADMIN : Voit toutes les données';
                }
            default:
                return 'Utilisateur sans rôle spécifique : Aucun accès';
        }
    }
}

