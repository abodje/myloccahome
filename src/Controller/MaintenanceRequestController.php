<?php

namespace App\Controller;

use App\Entity\MaintenanceRequest;
use App\Form\MaintenanceRequestType;
use App\Repository\MaintenanceRequestRepository;
use App\Repository\PropertyRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/mes-demandes')]
class MaintenanceRequestController extends AbstractController
{
    #[Route('/', name: 'app_maintenance_request_index', methods: ['GET'])]
    public function index(MaintenanceRequestRepository $maintenanceRequestRepository, Request $request): Response
    {
        /** @var \App\Entity\User|null $user */
        $user = $this->getUser();
        $status = $request->query->get('status');
        $priority = $request->query->get('priority');
        $category = $request->query->get('category');

        // Filtrer les demandes selon le rôle de l'utilisateur
        if ($user && in_array('ROLE_TENANT', $user->getRoles())) {
            // Si l'utilisateur est un locataire, ne montrer que ses demandes
            $tenant = $user->getTenant();
            if ($tenant) {
                $requests = $maintenanceRequestRepository->findByTenantWithFilters($tenant->getId(), $status, $priority, $category);
            } else {
                $requests = [];
            }
        } elseif ($user && in_array('ROLE_MANAGER', $user->getRoles())) {
            // Si l'utilisateur est un gestionnaire, montrer les demandes de ses propriétés
            $owner = $user->getOwner();
            if ($owner) {
                $requests = $maintenanceRequestRepository->findByManagerWithFilters($owner->getId(), $status, $priority, $category);
            } else {
                $requests = $maintenanceRequestRepository->findWithFilters($status, $priority, $category);
            }
        } else {
            // Pour les admins, montrer toutes les demandes
            $requests = $maintenanceRequestRepository->findWithFilters($status, $priority, $category);
        }

        $stats = $this->calculateFilteredStats($maintenanceRequestRepository, $user);

        // Passer une variable pour indiquer si c'est la vue locataire
        $isTenantView = $user && in_array('ROLE_TENANT', $user->getRoles());

        return $this->render('maintenance_request/index.html.twig', [
            'maintenance_requests' => $requests,
            'stats' => $stats,
            'current_status' => $status,
            'current_priority' => $priority,
            'current_category' => $category,
            'is_tenant_view' => $isTenantView,
        ]);
    }

    #[Route('/nouvelle', name: 'app_maintenance_request_new', methods: ['GET', 'POST'])]
    public function new(Request $request, EntityManagerInterface $entityManager, PropertyRepository $propertyRepository): Response
    {
        /** @var \App\Entity\User|null $user */
        $user = $this->getUser();

        $maintenanceRequest = new MaintenanceRequest();

        // Préparer les options du formulaire selon le rôle
        $formOptions = [];
        $isTenantView = $user && in_array('ROLE_TENANT', $user->getRoles());

        if ($isTenantView) {
            $tenant = $user->getTenant();
            if ($tenant) {
                // Pour les locataires, limiter aux propriétés qu'ils louent
                $tenantProperties = $propertyRepository->findByTenantWithFilters($tenant->getId());
                $formOptions['is_tenant_view'] = true;
                $formOptions['tenant_properties'] = $tenantProperties;

                // Pré-remplir avec la première propriété si disponible
                if (!empty($tenantProperties)) {
                    $maintenanceRequest->setProperty($tenantProperties[0]);
                }
            }
        }

        $form = $this->createForm(MaintenanceRequestType::class, $maintenanceRequest, $formOptions);

        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            // Définir automatiquement les données selon le rôle
            if ($user && in_array('ROLE_TENANT', $user->getRoles())) {
                $tenant = $user->getTenant();
                if ($tenant) {
                    // Pour les locataires, s'assurer que la propriété appartient bien à un de leurs baux
                    $property = $maintenanceRequest->getProperty();
                    $tenantProperties = $propertyRepository->findByTenantWithFilters($tenant->getId());

                    if (!in_array($property, $tenantProperties)) {
                        $this->addFlash('error', 'Vous ne pouvez créer une demande que pour vos propriétés louées.');
                        return $this->redirectToRoute('app_maintenance_request_new');
                    }

                    // Définir automatiquement le locataire
                    $maintenanceRequest->setTenant($tenant);
                }
            }

            $maintenanceRequest->setCreatedAt(new \DateTime());
            $maintenanceRequest->setStatus('En attente');
            $maintenanceRequest->setPriority('Normale');

            $entityManager->persist($maintenanceRequest);
            $entityManager->flush();

            $this->addFlash('success', 'La demande de maintenance a été créée avec succès.');

            return $this->redirectToRoute('app_maintenance_request_show', ['id' => $maintenanceRequest->getId()]);
        }

        return $this->render('maintenance_request/new.html.twig', [
            'maintenance_request' => $maintenanceRequest,
            'form' => $form,
            'is_tenant_view' => $user && in_array('ROLE_TENANT', $user->getRoles()),
        ]);
    }

    #[Route('/{id}', name: 'app_maintenance_request_show', methods: ['GET'])]
    public function show(MaintenanceRequest $maintenanceRequest): Response
    {
        return $this->render('maintenance_request/show.html.twig', [
            'maintenance_request' => $maintenanceRequest,
        ]);
    }

    #[Route('/{id}/modifier', name: 'app_maintenance_request_edit', methods: ['GET', 'POST'])]
    public function edit(Request $request, MaintenanceRequest $maintenanceRequest, EntityManagerInterface $entityManager): Response
    {
        /** @var \App\Entity\User|null $user */
        $user = $this->getUser();
        $isTenantView = $user && in_array('ROLE_TENANT', $user->getRoles());

        $form = $this->createForm(MaintenanceRequestType::class, $maintenanceRequest);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $maintenanceRequest->setUpdatedAt(new \DateTime());
            $entityManager->flush();

            $this->addFlash('success', 'La demande de maintenance a été modifiée avec succès.');

            return $this->redirectToRoute('app_maintenance_request_show', ['id' => $maintenanceRequest->getId()]);
        }

        return $this->render('maintenance_request/edit.html.twig', [
            'maintenance_request' => $maintenanceRequest,
            'form' => $form,
            'is_tenant_view' => $isTenantView,
        ]);
    }

    #[Route('/{id}/terminer', name: 'app_maintenance_request_complete', methods: ['POST'])]
    public function complete(Request $request, MaintenanceRequest $maintenanceRequest, EntityManagerInterface $entityManager): Response
    {
        if ($this->isCsrfTokenValid('complete'.$maintenanceRequest->getId(), $request->getPayload()->getString('_token'))) {
            $workPerformed = $request->getPayload()->getString('work_performed');
            $actualCost = $request->getPayload()->getString('actual_cost');

            $maintenanceRequest->markAsCompleted(new \DateTime(), $workPerformed);

            if ($actualCost) {
                $maintenanceRequest->setActualCost($actualCost);
            }

            $entityManager->flush();

            $this->addFlash('success', 'La demande de maintenance a été marquée comme terminée.');
        }

        return $this->redirectToRoute('app_maintenance_request_show', ['id' => $maintenanceRequest->getId()]);
    }

    #[Route('/{id}/supprimer', name: 'app_maintenance_request_delete', methods: ['POST'])]
    public function delete(Request $request, MaintenanceRequest $maintenanceRequest, EntityManagerInterface $entityManager): Response
    {
        if ($this->isCsrfTokenValid('delete'.$maintenanceRequest->getId(), $request->getPayload()->getString('_token'))) {
            $entityManager->remove($maintenanceRequest);
            $entityManager->flush();

            $this->addFlash('success', 'La demande de maintenance a été supprimée avec succès.');
        }

        return $this->redirectToRoute('app_maintenance_request_index');
    }

    #[Route('/urgentes', name: 'app_maintenance_request_urgent', methods: ['GET'])]
    public function urgent(MaintenanceRequestRepository $maintenanceRequestRepository): Response
    {
        $urgentRequests = $maintenanceRequestRepository->findUrgentPending();

        return $this->render('maintenance_request/urgent.html.twig', [
            'urgent_requests' => $urgentRequests,
        ]);
    }

    #[Route('/en-retard', name: 'app_maintenance_request_overdue', methods: ['GET'])]
    public function overdue(MaintenanceRequestRepository $maintenanceRequestRepository): Response
    {
        $overdueRequests = $maintenanceRequestRepository->findOverdue();

        return $this->render('maintenance_request/overdue.html.twig', [
            'overdue_requests' => $overdueRequests,
        ]);
    }

    #[Route('/categories/{category}', name: 'app_maintenance_request_by_category', methods: ['GET'])]
    public function byCategory(string $category, MaintenanceRequestRepository $maintenanceRequestRepository): Response
    {
        $requests = $maintenanceRequestRepository->findByCategory($category);

        return $this->render('maintenance_request/by_category.html.twig', [
            'requests' => $requests,
            'category' => $category,
        ]);
    }

    /**
     * Calcule les statistiques filtrées selon le rôle de l'utilisateur
     */
    private function calculateFilteredStats(MaintenanceRequestRepository $maintenanceRequestRepository, $user): array
    {
        if ($user && in_array('ROLE_TENANT', $user->getRoles())) {
            // Pour les locataires, calculer les stats sur leurs demandes seulement
            $tenant = $user->getTenant();
            if ($tenant) {
                $tenantRequests = $maintenanceRequestRepository->findByTenantWithFilters($tenant->getId());

                $stats = [
                    'total' => count($tenantRequests),
                    'pending' => 0,
                    'urgent' => 0,
                    'overdue' => 0,
                    'terminees' => 0
                ];

                foreach ($tenantRequests as $request) {
                    if ($request->getStatus() === 'En attente') {
                        $stats['pending']++;
                    } elseif ($request->getStatus() === 'En cours') {
                        $stats['urgent']++;
                    } elseif ($request->getStatus() === 'Terminée') {
                        $stats['terminees']++;
                    }

                    // Vérifier si la demande est en retard
                    if ($request->getStatus() === 'En attente' && $request->getCreatedAt() < new \DateTime('-7 days')) {
                        $stats['overdue']++;
                    }
                }

                return $stats;
            }
        } elseif ($user && in_array('ROLE_MANAGER', $user->getRoles())) {
            // Pour les gestionnaires, calculer les stats sur les demandes de leurs propriétés
            $owner = $user->getOwner();
            if ($owner) {
                $managerRequests = $maintenanceRequestRepository->findByManagerWithFilters($owner->getId());

                $stats = [
                    'total' => count($managerRequests),
                    'pending' => 0,
                    'urgent' => 0,
                    'overdue' => 0,
                    'terminees' => 0
                ];

                foreach ($managerRequests as $request) {
                    if ($request->getStatus() === 'En attente') {
                        $stats['pending']++;
                    } elseif ($request->getStatus() === 'En cours') {
                        $stats['urgent']++;
                    } elseif ($request->getStatus() === 'Terminée') {
                        $stats['terminees']++;
                    }

                    // Vérifier si la demande est en retard
                    if ($request->getStatus() === 'En attente' && $request->getCreatedAt() < new \DateTime('-7 days')) {
                        $stats['overdue']++;
                    }
                }

                return $stats;
            }
        }

        // Pour les admins, retourner les stats globales
        return $maintenanceRequestRepository->getStatistics();
    }
}
