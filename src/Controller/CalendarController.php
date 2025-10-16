<?php

namespace App\Controller;

use App\Repository\PaymentRepository;
use App\Repository\LeaseRepository;
use App\Repository\MaintenanceRequestRepository;
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
        return $this->render('calendar/index.html.twig');
    }

    /**
     * API pour récupérer les événements du calendrier
     */
    #[Route('/events', name: 'app_calendar_events', methods: ['GET'])]
    public function events(
        Request $request,
        PaymentRepository $paymentRepo,
        LeaseRepository $leaseRepo,
        MaintenanceRequestRepository $maintenanceRepo
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
            $user = $this->getUser();

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
            } else {
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
            $qb = $paymentRepo->createQueryBuilder('p');

            if (method_exists($user, 'getOrganization') && $user->getOrganization()) {
                // Si l'admin a une company spécifique
                if (method_exists($user, 'getCompany') && $user->getCompany()) {
                    $qb->where('p.company = :company')
                       ->setParameter('company', $user->getCompany());
                } else {
                    // Sinon voir toute l'organization
                    $qb->where('p.organization = :organization')
                       ->setParameter('organization', $user->getOrganization());
                }
                $allPayments = $qb->getQuery()->getResult();
            } else {
                // Super admin : tous les paiements
                $allPayments = $paymentRepo->findAll();
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
            } else {
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
            $qb = $leaseRepo->createQueryBuilder('l');

            if (method_exists($user, 'getOrganization') && $user->getOrganization()) {
                // Si l'admin a une company spécifique
                if (method_exists($user, 'getCompany') && $user->getCompany()) {
                    $qb->where('l.company = :company')
                       ->setParameter('company', $user->getCompany());
                } else {
                    // Sinon voir toute l'organization
                    $qb->where('l.organization = :organization')
                       ->setParameter('organization', $user->getOrganization());
                }
                $allLeases = $qb->getQuery()->getResult();
            } else {
                // Super admin : tous les baux
                $allLeases = $leaseRepo->findAll();
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
            } else {
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
            $qb = $maintenanceRepo->createQueryBuilder('m');

            if (method_exists($user, 'getOrganization') && $user->getOrganization()) {
                // Si l'admin a une company spécifique
                if (method_exists($user, 'getCompany') && $user->getCompany()) {
                    $qb->where('m.company = :company')
                       ->setParameter('company', $user->getCompany());
                } else {
                    // Sinon voir toute l'organization
                    $qb->where('m.organization = :organization')
                       ->setParameter('organization', $user->getOrganization());
                }
                $allMaintenances = $qb->getQuery()->getResult();
            } else {
                // Super admin : toutes les maintenances
                $allMaintenances = $maintenanceRepo->findAll();
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
}

