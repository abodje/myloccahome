<?php

namespace App\Controller;

use App\Entity\Tenant;
use App\Entity\User;
use App\Event\ResourceQuotaCheckEvent;
use App\Form\TenantType;
use App\Repository\TenantRepository;
use App\Repository\LeaseRepository;
use App\Repository\PaymentRepository;
use App\Repository\DocumentRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/locataires')]
class TenantController extends AbstractController
{
    public function __construct(
        private EventDispatcherInterface $eventDispatcher
    ) {
    }
    #[Route('/', name: 'app_tenant_index', methods: ['GET'])]
    public function index(TenantRepository $tenantRepository, Request $request): Response
    {
        /** @var \App\Entity\User $user */
        $user = $this->getUser();
        $search = $request->query->get('search');
        $status = $request->query->get('status'); // actif, inactif

        // Filtrer les locataires selon le rôle de l'utilisateur
        if ($user && in_array('ROLE_TENANT', $user->getRoles())) {
            // Si l'utilisateur est un locataire, ne montrer que lui-même
            $tenant = $user->getTenant();
            if ($tenant) {
                $tenants = [$tenant];
            } else {
                $tenants = [];
            }
        } elseif ($user && in_array('ROLE_MANAGER', $user->getRoles())) {
            // Si l'utilisateur est un gestionnaire, montrer les locataires des propriétés qu'il gère
            $owner = $user->getOwner();
            if ($owner) {
                $tenants = $tenantRepository->findByManager($owner->getId(), $search, $status);
            } else {
                $tenants = $tenantRepository->findBy([], ['lastName' => 'ASC', 'firstName' => 'ASC']);
            }
        } elseif ($user && (in_array('ROLE_ADMIN', $user->getRoles()) || in_array('ROLE_SUPER_ADMIN', $user->getRoles()))) {
            // Pour les admins, filtrer selon l'organisation/société
            $organization = $user->getOrganization();
            $company = $user->getCompany();

            error_log("TenantController - Admin: organization=" . ($organization ? $organization->getName() : 'null') . ", company=" . ($company ? $company->getName() : 'null'));

            if ($company) {
                // Admin avec société spécifique : filtrer par société
                $tenants = $tenantRepository->findByCompany($company, $search, $status);
                error_log("TenantController - Filtered by company: " . $company->getName());
            } elseif ($organization) {
                // Admin avec organisation : filtrer par organisation
                $tenants = $tenantRepository->findByOrganization($organization, $search, $status);
                error_log("TenantController - Filtered by organization: " . $organization->getName());
            } else {
                // Super Admin sans organisation/société : tous les locataires
                $tenants = $tenantRepository->findBy([], ['lastName' => 'ASC', 'firstName' => 'ASC']);
                error_log("TenantController - Super Admin: showing all tenants");
            }
        } else {
            // Pour les autres rôles, montrer tous les locataires
            $tenants = $tenantRepository->findBy([], ['lastName' => 'ASC', 'firstName' => 'ASC']);
        }

        // Statistiques filtrées selon le rôle
        $stats = $this->calculateFilteredTenantStats($tenantRepository, $user);

        return $this->render('tenant/index.html.twig', [
            'tenants' => $tenants,
            'stats' => $stats,
            'search' => $search,
            'current_status' => $status,
        ]);
    }

    #[Route('/nouveau', name: 'app_tenant_new', methods: ['GET', 'POST'])]
    public function new(Request $request, EntityManagerInterface $entityManager, UserPasswordHasherInterface $passwordHasher): Response
    {
        /** @var \App\Entity\User $user */
        $user = $this->getUser();

        $tenant = new Tenant();

        // Auto-assigner organization et company
        if ($user && method_exists($user, 'getOrganization') && $user->getOrganization()) {
            $tenant->setOrganization($user->getOrganization());

            if (method_exists($user, 'getCompany') && $user->getCompany()) {
                $tenant->setCompany($user->getCompany());
            } else {
                $headquarter = $user->getOrganization()->getHeadquarterCompany();
                if ($headquarter) {
                    $tenant->setCompany($headquarter);
                }
            }
        }

        $form = $this->createForm(TenantType::class, $tenant);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            // Vérifier les quotas avant de créer via Event
            /** @var \App\Entity\User|null $currentUser */
            $currentUser = $this->getUser();
            if ($currentUser && $currentUser->getOrganization()) {
                $quotaEvent = new ResourceQuotaCheckEvent(
                    $currentUser->getOrganization(),
                    'tenants',
                    'app_tenant_index'
                );
                
                $this->eventDispatcher->dispatch($quotaEvent);

                if (!$quotaEvent->isAllowed()) {
                    // Ajouter les messages flash depuis l'event
                    foreach ($quotaEvent->getFlashMessages() as $flashMessage) {
                        $this->addFlash($flashMessage['type'], $flashMessage['message']);
                    }

                    return $this->redirectToRoute($quotaEvent->getRedirectRoute() ?? 'app_tenant_index');
                }
            }
            
            $entityManager->persist($tenant);

            // Créer un compte User si demandé
            $createAccount = $request->request->get('create_user_account');
            if ($createAccount) {
                $user = new User();
                $user->setEmail($tenant->getEmail())
                     ->setFirstName($tenant->getFirstName())
                     ->setLastName($tenant->getLastName())
                     ->setPhone($tenant->getPhone())
                     ->setAddress($tenant->getAddress())
                     ->setCity($tenant->getCity())
                     ->setPostalCode($tenant->getPostalCode())
                     ->setBirthDate($tenant->getBirthDate())
                     ->setRoles(['ROLE_TENANT'])
                     ->setActive(true);

                // Définir l'organization et la company sur l'utilisateur
                if ($tenant->getOrganization()) {
                    $user->setOrganization($tenant->getOrganization());
                }
                if ($tenant->getCompany()) {
                    $user->setCompany($tenant->getCompany());
                }

                // Générer un mot de passe aléatoire ou utiliser celui fourni
                $password = $request->request->get('user_password') ?? bin2hex(random_bytes(8));
                $hashedPassword = $passwordHasher->hashPassword($user, $password);
                $user->setPassword($hashedPassword);

                // Lier le User au Tenant
                $tenant->setUser($user);

                $entityManager->persist($user);

                $this->addFlash('success', "Le locataire a été créé avec succès. Compte créé avec le mot de passe : {$password}");
            } else {
                $this->addFlash('success', 'Le locataire a été créé avec succès.');
            }

            $entityManager->flush();

            return $this->redirectToRoute('app_tenant_show', ['id' => $tenant->getId()]);
        }

        return $this->render('tenant/new.html.twig', [
            'tenant' => $tenant,
            'form' => $form,
        ]);
    }

    #[Route('/{id}', name: 'app_tenant_show', methods: ['GET'])]
    public function show(
        Tenant $tenant,
        LeaseRepository $leaseRepository,
        PaymentRepository $paymentRepository
    ): Response {
        $leases = $leaseRepository->findByTenant($tenant->getId());
        $currentLease = $tenant->getCurrentLease();

        // Historique des paiements du locataire
        $payments = [];
        foreach ($leases as $lease) {
            $leasePayments = $paymentRepository->findByLease($lease->getId());
            $payments = array_merge($payments, $leasePayments);
        }

        // Trier par date décroissante
        usort($payments, function($a, $b) {
            return $b->getDueDate() <=> $a->getDueDate();
        });

        return $this->render('tenant/show.html.twig', [
            'tenant' => $tenant,
            'current_lease' => $currentLease,
            'leases' => $leases,
            'recent_payments' => array_slice($payments, 0, 10),
        ]);
    }

    #[Route('/{id}/modifier', name: 'app_tenant_edit', methods: ['GET', 'POST'])]
    public function edit(Request $request, Tenant $tenant, EntityManagerInterface $entityManager): Response
    {
        $form = $this->createForm(TenantType::class, $tenant);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $tenant->setUpdatedAt(new \DateTime());
            $entityManager->flush();

            $this->addFlash('success', 'Le locataire a été modifié avec succès.');

            return $this->redirectToRoute('app_tenant_show', ['id' => $tenant->getId()]);
        }

        return $this->render('tenant/edit.html.twig', [
            'tenant' => $tenant,
            'form' => $form,
        ]);
    }

    #[Route('/{id}/supprimer', name: 'app_tenant_delete', methods: ['POST'])]
    public function delete(Request $request, Tenant $tenant, EntityManagerInterface $entityManager): Response
    {
        if ($this->isCsrfTokenValid('delete'.$tenant->getId(), $request->getPayload()->getString('_token'))) {
            // Vérifier qu'il n'y a pas de contrat actif
            if ($tenant->getCurrentLease()) {
                $this->addFlash('error', 'Impossible de supprimer un locataire avec un contrat actif.');
                return $this->redirectToRoute('app_tenant_show', ['id' => $tenant->getId()]);
            }

            $entityManager->remove($tenant);
            $entityManager->flush();

            $this->addFlash('success', 'Le locataire a été supprimé avec succès.');
        }

        return $this->redirectToRoute('app_tenant_index');
    }

    #[Route('/{id}/contrats', name: 'app_tenant_leases', methods: ['GET'])]
    public function leases(Tenant $tenant, LeaseRepository $leaseRepository): Response
    {
        $leases = $leaseRepository->findByTenant($tenant->getId());

        return $this->render('tenant/leases.html.twig', [
            'tenant' => $tenant,
            'leases' => $leases,
        ]);
    }

    #[Route('/{id}/paiements', name: 'app_tenant_payments', methods: ['GET'])]
    public function payments(Tenant $tenant, PaymentRepository $paymentRepository): Response
    {
        // Récupérer tous les paiements du locataire via ses contrats
        $payments = [];
        foreach ($tenant->getLeases() as $lease) {
            $leasePayments = $paymentRepository->findByLease($lease->getId());
            $payments = array_merge($payments, $leasePayments);
        }

        // Trier par date décroissante
        usort($payments, function($a, $b) {
            return $b->getDueDate() <=> $a->getDueDate();
        });

        return $this->render('tenant/payments.html.twig', [
            'tenant' => $tenant,
            'payments' => $payments,
        ]);
    }


    #[Route('/recherche', name: 'app_tenant_search', methods: ['GET'])]
    public function search(Request $request, TenantRepository $tenantRepository): Response
    {
        $query = $request->query->get('q', '');
        $tenants = [];

        if ($query) {
            $tenants = $tenantRepository->findByNameOrEmail($query);
        }

        return $this->render('tenant/search.html.twig', [
            'tenants' => $tenants,
            'query' => $query,
        ]);
    }

    #[Route('/export', name: 'app_tenant_export', methods: ['GET'])]
    public function export(TenantRepository $tenantRepository): Response
    {
        $tenants = $tenantRepository->findBy([], ['lastName' => 'ASC', 'firstName' => 'ASC']);

        $response = new Response();
        $response->headers->set('Content-Type', 'text/csv');
        $response->headers->set('Content-Disposition', 'attachment; filename="locataires_' . date('Y-m-d') . '.csv"');

        $output = fopen('php://temp', 'w');

        // En-têtes CSV
        fputcsv($output, [
            'ID',
            'Prénom',
            'Nom',
            'Email',
            'Téléphone',
            'Date de naissance',
            'Profession',
            'Revenus mensuels',
            'Adresse',
            'Ville',
            'Code postal',
            'Date de création'
        ], ';');

        // Données
        foreach ($tenants as $tenant) {
            fputcsv($output, [
                $tenant->getId(),
                $tenant->getFirstName(),
                $tenant->getLastName(),
                $tenant->getEmail(),
                $tenant->getPhone(),
                $tenant->getBirthDate() ? $tenant->getBirthDate()->format('d/m/Y') : '',
                $tenant->getProfession(),
                $tenant->getMonthlyIncome(),
                $tenant->getAddress(),
                $tenant->getCity(),
                $tenant->getPostalCode(),
                $tenant->getCreatedAt()->format('d/m/Y H:i')
            ], ';');
        }

        rewind($output);
        $response->setContent(stream_get_contents($output));
        fclose($output);

        return $response;
    }

    #[Route('/{id}/creer-compte', name: 'app_tenant_create_account', methods: ['POST'])]
    public function createAccount(
        Tenant $tenant,
        Request $request,
        EntityManagerInterface $entityManager,
        UserPasswordHasherInterface $passwordHasher
    ): Response {
        // Vérifier si le tenant a déjà un compte
        if ($tenant->getUser()) {
            $this->addFlash('warning', 'Ce locataire possède déjà un compte utilisateur.');
            return $this->redirectToRoute('app_tenant_show', ['id' => $tenant->getId()]);
        }

        // Créer le compte User
        $user = new User();
        $user->setEmail($tenant->getEmail())
             ->setFirstName($tenant->getFirstName())
             ->setLastName($tenant->getLastName())
             ->setPhone($tenant->getPhone())
             ->setAddress($tenant->getAddress())
             ->setCity($tenant->getCity())
             ->setPostalCode($tenant->getPostalCode())
             ->setBirthDate($tenant->getBirthDate())
             ->setRoles(['ROLE_TENANT'])
             ->setActive(true);

        // Définir l'organization et la company sur l'utilisateur
        if ($tenant->getOrganization()) {
            $user->setOrganization($tenant->getOrganization());
        }
        if ($tenant->getCompany()) {
            $user->setCompany($tenant->getCompany());
        }

        // Générer un mot de passe aléatoire
        $password = bin2hex(random_bytes(8));
        $hashedPassword = $passwordHasher->hashPassword($user, $password);
        $user->setPassword($hashedPassword);

        // Lier au Tenant
        $tenant->setUser($user);

        $entityManager->persist($user);
        $entityManager->flush();

        $this->addFlash('success', "Compte créé avec succès ! Email: {$tenant->getEmail()} / Mot de passe: {$password}");
        $this->addFlash('info', "N'oubliez pas de communiquer ces identifiants au locataire de manière sécurisée.");

        return $this->redirectToRoute('app_tenant_show', ['id' => $tenant->getId()]);
    }

    #[Route('/{id}/documents', name: 'app_tenant_documents', methods: ['GET'])]
    public function documents(Tenant $tenant, DocumentRepository $documentRepository): Response
    {
        // Récupérer les documents du locataire
        $tenantDocuments = $documentRepository->findByTenant($tenant->getId());

        // Organiser par type
        $documentsByType = [
            'Assurance' => [],
            'Avis d\'échéance' => [],
            'Bail' => [],
            'Diagnostics' => [],
            'OK' => [],
        ];

        foreach ($tenantDocuments as $document) {
            $type = $document->getType();

            // Grouper "Bail" et "Contrat de location" ensemble
            if ($type === 'Bail' || $type === 'Contrat de location') {
                $type = 'Bail';
            }
            // Grouper "Conseils" sous "OK"
            elseif ($type === 'Conseils') {
                $type = 'OK';
            }

            if (isset($documentsByType[$type])) {
                $documentsByType[$type][] = $document;
            }
        }

        // Calculer les statistiques
        $stats = [
            'total' => count($tenantDocuments),
            'archived' => 0,
            'expiring_soon' => 0,
            'expired' => 0
        ];

        foreach ($tenantDocuments as $document) {
            if ($document->isArchived()) {
                $stats['archived']++;
            }

            $expirationDate = $document->getExpirationDate();
            if ($expirationDate) {
                $now = new \DateTime();
                $in30Days = new \DateTime('+30 days');

                if ($expirationDate <= $in30Days && $expirationDate > $now) {
                    $stats['expiring_soon']++;
                } elseif ($expirationDate <= $now) {
                    $stats['expired']++;
                }
            }
        }

        return $this->render('tenant/documents.html.twig', [
            'tenant' => $tenant,
            'documents_by_type' => $documentsByType,
            'stats' => $stats,
        ]);
    }

    /**
     * Calcule les statistiques filtrées selon le rôle de l'utilisateur
     */
    private function calculateFilteredTenantStats(TenantRepository $tenantRepository, $user): array
    {
        if ($user && in_array('ROLE_TENANT', $user->getRoles())) {
            // Pour les locataires, calculer les stats sur eux-mêmes seulement
            $tenant = $user->getTenant();
            if ($tenant) {
                $hasActiveLease = $tenantRepository->hasActiveLease($tenant->getId());

                return [
                    'total_tenants' => 1,
                    'with_active_contract' => $hasActiveLease ? 1 : 0,
                    'without_active_contract' => $hasActiveLease ? 0 : 1,
                    'occupancy_rate' => $hasActiveLease ? 100.0 : 0.0
                ];
            }
            return [
                'total_tenants' => 0,
                'with_active_contract' => 0,
                'without_active_contract' => 0,
                'occupancy_rate' => 0.0
            ];
        } elseif ($user && in_array('ROLE_MANAGER', $user->getRoles())) {
            // Pour les gestionnaires, calculer les stats sur les locataires qu'ils gèrent
            $owner = $user->getOwner();
            if ($owner) {
                $managerTenants = $tenantRepository->findByManager($owner->getId());

                $stats = [
                    'total_tenants' => count($managerTenants),
                    'with_active_contract' => 0,
                    'without_active_contract' => 0,
                    'occupancy_rate' => 0.0
                ];

                foreach ($managerTenants as $tenant) {
                    if ($tenantRepository->hasActiveLease($tenant->getId())) {
                        $stats['with_active_contract']++;
                    } else {
                        $stats['without_active_contract']++;
                    }
                }

                // Calculer le taux d'occupation
                if ($stats['total_tenants'] > 0) {
                    $stats['occupancy_rate'] = round(($stats['with_active_contract'] / $stats['total_tenants']) * 100, 2);
                }

                return $stats;
            }
        } elseif ($user && (in_array('ROLE_ADMIN', $user->getRoles()) || in_array('ROLE_SUPER_ADMIN', $user->getRoles()))) {
            // Pour les admins, calculer les stats selon l'organisation/société
            $organization = $user->getOrganization();
            $company = $user->getCompany();

            if ($company) {
                // Admin avec société spécifique
                $companyTenants = $tenantRepository->findByCompany($company);

                $stats = [
                    'total_tenants' => count($companyTenants),
                    'with_active_contract' => 0,
                    'without_active_contract' => 0,
                    'occupancy_rate' => 0.0
                ];

                foreach ($companyTenants as $tenant) {
                    if ($tenantRepository->hasActiveLease($tenant->getId())) {
                        $stats['with_active_contract']++;
                    } else {
                        $stats['without_active_contract']++;
                    }
                }

                // Calculer le taux d'occupation
                if ($stats['total_tenants'] > 0) {
                    $stats['occupancy_rate'] = round(($stats['with_active_contract'] / $stats['total_tenants']) * 100, 2);
                }

                return $stats;
            } elseif ($organization) {
                // Admin avec organisation
                $orgTenants = $tenantRepository->findByOrganization($organization);

                $stats = [
                    'total_tenants' => count($orgTenants),
                    'with_active_contract' => 0,
                    'without_active_contract' => 0,
                    'occupancy_rate' => 0.0
                ];

                foreach ($orgTenants as $tenant) {
                    if ($tenantRepository->hasActiveLease($tenant->getId())) {
                        $stats['with_active_contract']++;
                    } else {
                        $stats['without_active_contract']++;
                    }
                }

                // Calculer le taux d'occupation
                if ($stats['total_tenants'] > 0) {
                    $stats['occupancy_rate'] = round(($stats['with_active_contract'] / $stats['total_tenants']) * 100, 2);
                }

                return $stats;
            }
        }

        // Pour les super admins sans organisation/société, retourner les stats globales
        $allTenants = $tenantRepository->findAll();

        $stats = [
            'total_tenants' => count($allTenants),
            'with_active_contract' => 0,
            'without_active_contract' => 0,
            'occupancy_rate' => 0.0
        ];

        foreach ($allTenants as $tenant) {
            if ($tenantRepository->hasActiveLease($tenant->getId())) {
                $stats['with_active_contract']++;
            } else {
                $stats['without_active_contract']++;
            }
        }

        // Calculer le taux d'occupation
        if ($stats['total_tenants'] > 0) {
            $stats['occupancy_rate'] = round(($stats['with_active_contract'] / $stats['total_tenants']) * 100, 2);
        }

        return $stats;
    }
}
