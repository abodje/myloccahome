<?php

namespace App\Controller;

use App\Entity\MaintenanceRequest;
use App\Repository\MaintenanceRequestRepository;
use App\Repository\PropertyRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/maintenance-request')]
class MaintenanceRequestController extends AbstractController
{
    #[Route('/', name: 'app_maintenance_request_index', methods: ['GET'])]
    public function index(MaintenanceRequestRepository $maintenanceRepository, Request $request): Response
    {
        $filter = $request->query->get('filter');
        $priority = $request->query->get('priority');
        $status = $request->query->get('status');
        $property = $request->query->get('property');

        if ($filter === 'urgent') {
            $requests = $maintenanceRepository->findUrgentRequests();
        } elseif ($filter === 'pending') {
            $requests = $maintenanceRepository->findPendingRequests();
        } elseif ($priority) {
            $requests = $maintenanceRepository->findBy(['priority' => $priority], ['createdAt' => 'DESC']);
        } elseif ($status) {
            $requests = $maintenanceRepository->findBy(['status' => $status], ['createdAt' => 'DESC']);
        } elseif ($property) {
            $requests = $maintenanceRepository->findByProperty($property);
        } else {
            $requests = $maintenanceRepository->findBy([], ['createdAt' => 'DESC']);
        }

        return $this->render('maintenance_request/index.html.twig', [
            'requests' => $requests,
            'filter' => $filter,
            'priority' => $priority,
            'status' => $status,
            'property' => $property,
        ]);
    }

    #[Route('/new', name: 'app_maintenance_request_new', methods: ['GET', 'POST'])]
    public function new(
        Request $request, 
        EntityManagerInterface $entityManager,
        PropertyRepository $propertyRepository
    ): Response {
        $maintenanceRequest = new MaintenanceRequest();
        $properties = $propertyRepository->findAll();

        // Pré-sélection si passé en paramètre
        $preselectedProperty = $request->query->get('property');

        if ($request->isMethod('POST')) {
            $property = $propertyRepository->find($request->request->get('property_id'));

            if (!$property) {
                $this->addFlash('error', 'Propriété non trouvée.');
                return $this->render('maintenance_request/new.html.twig', [
                    'maintenanceRequest' => $maintenanceRequest,
                    'properties' => $properties,
                    'preselectedProperty' => $preselectedProperty,
                ]);
            }

            $maintenanceRequest->setProperty($property);
            $maintenanceRequest->setTitle($request->request->get('title'));
            $maintenanceRequest->setDescription($request->request->get('description'));
            $maintenanceRequest->setPriority($request->request->get('priority'));
            $maintenanceRequest->setCategory($request->request->get('category'));
            
            if ($request->request->get('estimated_cost')) {
                $maintenanceRequest->setEstimatedCost($request->request->get('estimated_cost'));
            }
            
            $maintenanceRequest->setAssignedTo($request->request->get('assigned_to'));
            
            if ($request->request->get('scheduled_date')) {
                $maintenanceRequest->setScheduledDate(new \DateTime($request->request->get('scheduled_date')));
            }
            
            $maintenanceRequest->setNotes($request->request->get('notes'));

            try {
                $entityManager->persist($maintenanceRequest);
                $entityManager->flush();

                $this->addFlash('success', 'Demande de maintenance créée avec succès !');
                return $this->redirectToRoute('app_maintenance_request_show', ['id' => $maintenanceRequest->getId()]);
            } catch (\Exception $e) {
                $this->addFlash('error', 'Erreur lors de la création de la demande.');
            }
        }

        return $this->render('maintenance_request/new.html.twig', [
            'maintenanceRequest' => $maintenanceRequest,
            'properties' => $properties,
            'preselectedProperty' => $preselectedProperty,
        ]);
    }

    #[Route('/{id}', name: 'app_maintenance_request_show', methods: ['GET'])]
    public function show(MaintenanceRequest $maintenanceRequest): Response
    {
        return $this->render('maintenance_request/show.html.twig', [
            'maintenanceRequest' => $maintenanceRequest,
        ]);
    }

    #[Route('/{id}/edit', name: 'app_maintenance_request_edit', methods: ['GET', 'POST'])]
    public function edit(Request $request, MaintenanceRequest $maintenanceRequest, EntityManagerInterface $entityManager): Response
    {
        if ($request->isMethod('POST')) {
            $maintenanceRequest->setTitle($request->request->get('title'));
            $maintenanceRequest->setDescription($request->request->get('description'));
            $maintenanceRequest->setPriority($request->request->get('priority'));
            $maintenanceRequest->setStatus($request->request->get('status'));
            $maintenanceRequest->setCategory($request->request->get('category'));
            
            if ($request->request->get('estimated_cost')) {
                $maintenanceRequest->setEstimatedCost($request->request->get('estimated_cost'));
            } else {
                $maintenanceRequest->setEstimatedCost(null);
            }

            if ($request->request->get('actual_cost')) {
                $maintenanceRequest->setActualCost($request->request->get('actual_cost'));
            } else {
                $maintenanceRequest->setActualCost(null);
            }
            
            $maintenanceRequest->setAssignedTo($request->request->get('assigned_to'));
            
            if ($request->request->get('scheduled_date')) {
                $maintenanceRequest->setScheduledDate(new \DateTime($request->request->get('scheduled_date')));
            } else {
                $maintenanceRequest->setScheduledDate(null);
            }

            if ($request->request->get('completed_date')) {
                $maintenanceRequest->setCompletedDate(new \DateTime($request->request->get('completed_date')));
            } else {
                $maintenanceRequest->setCompletedDate(null);
            }
            
            $maintenanceRequest->setResolution($request->request->get('resolution'));
            $maintenanceRequest->setNotes($request->request->get('notes'));
            $maintenanceRequest->setUpdatedAt(new \DateTime());

            try {
                $entityManager->flush();

                $this->addFlash('success', 'Demande de maintenance modifiée avec succès !');
                return $this->redirectToRoute('app_maintenance_request_show', ['id' => $maintenanceRequest->getId()]);
            } catch (\Exception $e) {
                $this->addFlash('error', 'Erreur lors de la modification de la demande.');
            }
        }

        return $this->render('maintenance_request/edit.html.twig', [
            'maintenanceRequest' => $maintenanceRequest,
        ]);
    }

    #[Route('/{id}', name: 'app_maintenance_request_delete', methods: ['POST'])]
    public function delete(Request $request, MaintenanceRequest $maintenanceRequest, EntityManagerInterface $entityManager): Response
    {
        if ($this->isCsrfTokenValid('delete'.$maintenanceRequest->getId(), $request->request->get('_token'))) {
            try {
                $entityManager->remove($maintenanceRequest);
                $entityManager->flush();
                $this->addFlash('success', 'Demande de maintenance supprimée avec succès !');
            } catch (\Exception $e) {
                $this->addFlash('error', 'Erreur lors de la suppression de la demande.');
            }
        }

        return $this->redirectToRoute('app_maintenance_request_index');
    }

    #[Route('/{id}/complete', name: 'app_maintenance_request_complete', methods: ['POST'])]
    public function complete(Request $request, MaintenanceRequest $maintenanceRequest, EntityManagerInterface $entityManager): Response
    {
        if ($this->isCsrfTokenValid('complete'.$maintenanceRequest->getId(), $request->request->get('_token'))) {
            $resolution = $request->request->get('resolution');
            $actualCost = $request->request->get('actual_cost') ? (float)$request->request->get('actual_cost') : null;

            $maintenanceRequest->markAsCompleted($resolution, $actualCost);

            $entityManager->flush();

            $this->addFlash('success', 'Demande de maintenance marquée comme terminée !');
        }

        return $this->redirectToRoute('app_maintenance_request_show', ['id' => $maintenanceRequest->getId()]);
    }

    #[Route('/{id}/start', name: 'app_maintenance_request_start', methods: ['POST'])]
    public function start(Request $request, MaintenanceRequest $maintenanceRequest, EntityManagerInterface $entityManager): Response
    {
        if ($this->isCsrfTokenValid('start'.$maintenanceRequest->getId(), $request->request->get('_token'))) {
            $maintenanceRequest->setStatus('in_progress');
            $maintenanceRequest->setUpdatedAt(new \DateTime());

            $assignedTo = $request->request->get('assigned_to');
            if ($assignedTo) {
                $maintenanceRequest->setAssignedTo($assignedTo);
            }

            $entityManager->flush();

            $this->addFlash('success', 'Demande de maintenance démarrée !');
        }

        return $this->redirectToRoute('app_maintenance_request_show', ['id' => $maintenanceRequest->getId()]);
    }
}