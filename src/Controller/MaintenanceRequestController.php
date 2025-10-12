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
        $status = $request->query->get('status');
        $priority = $request->query->get('priority');
        $category = $request->query->get('category');

        if ($status) {
            $requests = $maintenanceRequestRepository->findByStatus($status);
        } elseif ($priority) {
            $requests = $maintenanceRequestRepository->findByPriority($priority);
        } elseif ($category) {
            $requests = $maintenanceRequestRepository->findByCategory($category);
        } else {
            $requests = $maintenanceRequestRepository->findBy([], ['createdAt' => 'DESC']);
        }

        $stats = $maintenanceRequestRepository->getStatistics();

        return $this->render('maintenance_request/index.html.twig', [
            'maintenance_requests' => $requests,
            'stats' => $stats,
            'current_status' => $status,
            'current_priority' => $priority,
            'current_category' => $category,
        ]);
    }

    #[Route('/nouvelle', name: 'app_maintenance_request_new', methods: ['GET', 'POST'])]
    public function new(Request $request, EntityManagerInterface $entityManager): Response
    {
        $maintenanceRequest = new MaintenanceRequest();
        $form = $this->createForm(MaintenanceRequestType::class, $maintenanceRequest);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->persist($maintenanceRequest);
            $entityManager->flush();

            $this->addFlash('success', 'La demande de maintenance a été créée avec succès.');

            return $this->redirectToRoute('app_maintenance_request_show', ['id' => $maintenanceRequest->getId()]);
        }

        return $this->render('maintenance_request/new.html.twig', [
            'maintenance_request' => $maintenanceRequest,
            'form' => $form,
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
}
