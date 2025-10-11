<?php

namespace App\Controller;

use App\Entity\Maintenance;
use App\Form\MaintenanceType;
use App\Repository\MaintenanceRepository;
use App\Repository\PropertyRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/maintenances')]
class MaintenanceController extends AbstractController
{
    #[Route('/', name: 'app_maintenance_index', methods: ['GET'])]
    public function index(Request $request, MaintenanceRepository $maintenanceRepository): Response
    {
        $status = $request->query->get('status');
        $priority = $request->query->get('priority');
        $type = $request->query->get('type');
        $propertyId = $request->query->get('property');

        $maintenances = $maintenanceRepository->findAll();

        if ($status) {
            $maintenances = array_filter($maintenances, fn($m) => $m->getStatus() === $status);
        }
        
        if ($priority) {
            $maintenances = array_filter($maintenances, fn($m) => $m->getPriority() === $priority);
        }
        
        if ($type) {
            $maintenances = array_filter($maintenances, fn($m) => $m->getType() === $type);
        }
        
        if ($propertyId) {
            $maintenances = $maintenanceRepository->findByProperty($propertyId);
        }

        // Trier par priorité puis par date
        usort($maintenances, function($a, $b) {
            $priorityOrder = ['urgent' => 1, 'high' => 2, 'normal' => 3, 'low' => 4];
            $aPriority = $priorityOrder[$a->getPriority()] ?? 5;
            $bPriority = $priorityOrder[$b->getPriority()] ?? 5;
            
            if ($aPriority === $bPriority) {
                return $a->getReportedDate() <=> $b->getReportedDate();
            }
            
            return $aPriority <=> $bPriority;
        });

        return $this->render('maintenance/index.html.twig', [
            'maintenances' => $maintenances,
            'status' => $status,
            'priority' => $priority,
            'type' => $type,
            'property_id' => $propertyId,
        ]);
    }

    #[Route('/new', name: 'app_maintenance_new', methods: ['GET', 'POST'])]
    public function new(Request $request, EntityManagerInterface $entityManager, PropertyRepository $propertyRepository): Response
    {
        $maintenance = new Maintenance();
        
        // Pré-remplir si property_id est fourni
        $propertyId = $request->query->get('property_id');
        if ($propertyId) {
            $property = $propertyRepository->find($propertyId);
            if ($property) {
                $maintenance->setProperty($property);
            }
        }

        $form = $this->createForm(MaintenanceType::class, $maintenance);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->persist($maintenance);
            $entityManager->flush();

            $this->addFlash('success', 'La maintenance a été créée avec succès.');

            return $this->redirectToRoute('app_maintenance_index', [], Response::HTTP_SEE_OTHER);
        }

        return $this->render('maintenance/new.html.twig', [
            'maintenance' => $maintenance,
            'form' => $form,
        ]);
    }

    #[Route('/{id}', name: 'app_maintenance_show', methods: ['GET'])]
    public function show(Maintenance $maintenance): Response
    {
        return $this->render('maintenance/show.html.twig', [
            'maintenance' => $maintenance,
        ]);
    }

    #[Route('/{id}/edit', name: 'app_maintenance_edit', methods: ['GET', 'POST'])]
    public function edit(Request $request, Maintenance $maintenance, EntityManagerInterface $entityManager): Response
    {
        $form = $this->createForm(MaintenanceType::class, $maintenance);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $maintenance->setUpdatedAt(new \DateTime());
            $entityManager->flush();

            $this->addFlash('success', 'La maintenance a été modifiée avec succès.');

            return $this->redirectToRoute('app_maintenance_index', [], Response::HTTP_SEE_OTHER);
        }

        return $this->render('maintenance/edit.html.twig', [
            'maintenance' => $maintenance,
            'form' => $form,
        ]);
    }

    #[Route('/{id}', name: 'app_maintenance_delete', methods: ['POST'])]
    public function delete(Request $request, Maintenance $maintenance, EntityManagerInterface $entityManager): Response
    {
        if ($this->isCsrfTokenValid('delete'.$maintenance->getId(), $request->getPayload()->getString('_token'))) {
            $entityManager->remove($maintenance);
            $entityManager->flush();
            $this->addFlash('success', 'La maintenance a été supprimée avec succès.');
        }

        return $this->redirectToRoute('app_maintenance_index', [], Response::HTTP_SEE_OTHER);
    }

    #[Route('/{id}/complete', name: 'app_maintenance_complete', methods: ['POST'])]
    public function complete(Request $request, Maintenance $maintenance, EntityManagerInterface $entityManager): Response
    {
        if ($this->isCsrfTokenValid('complete'.$maintenance->getId(), $request->getPayload()->getString('_token'))) {
            $completedDate = new \DateTime($request->request->get('completed_date', 'now'));
            $workPerformed = $request->request->get('work_performed');
            $actualCost = $request->request->get('actual_cost');
            
            $maintenance->markAsCompleted($completedDate, $workPerformed, $actualCost);
            $entityManager->flush();

            $this->addFlash('success', 'La maintenance a été marquée comme terminée.');
        }

        return $this->redirectToRoute('app_maintenance_show', ['id' => $maintenance->getId()]);
    }

    #[Route('/{id}/schedule', name: 'app_maintenance_schedule', methods: ['POST'])]
    public function schedule(Request $request, Maintenance $maintenance, EntityManagerInterface $entityManager): Response
    {
        if ($this->isCsrfTokenValid('schedule'.$maintenance->getId(), $request->getPayload()->getString('_token'))) {
            $scheduledDate = new \DateTime($request->request->get('scheduled_date'));
            $contractorName = $request->request->get('contractor_name');
            $contractorPhone = $request->request->get('contractor_phone');
            $estimatedCost = $request->request->get('estimated_cost');
            
            $maintenance->setScheduledDate($scheduledDate);
            $maintenance->setContractorName($contractorName);
            $maintenance->setContractorPhone($contractorPhone);
            $maintenance->setEstimatedCost($estimatedCost);
            $maintenance->setStatus('in_progress');
            $maintenance->setUpdatedAt(new \DateTime());
            
            $entityManager->flush();

            $this->addFlash('success', 'La maintenance a été programmée avec succès.');
        }

        return $this->redirectToRoute('app_maintenance_show', ['id' => $maintenance->getId()]);
    }

    #[Route('/urgent', name: 'app_maintenance_urgent', methods: ['GET'])]
    public function urgent(MaintenanceRepository $maintenanceRepository): Response
    {
        $maintenances = $maintenanceRepository->findUrgent();

        return $this->render('maintenance/urgent.html.twig', [
            'maintenances' => $maintenances,
        ]);
    }

    #[Route('/pending', name: 'app_maintenance_pending', methods: ['GET'])]
    public function pending(MaintenanceRepository $maintenanceRepository): Response
    {
        $maintenances = $maintenanceRepository->findPending();

        return $this->render('maintenance/pending.html.twig', [
            'maintenances' => $maintenances,
        ]);
    }

    #[Route('/overdue', name: 'app_maintenance_overdue', methods: ['GET'])]
    public function overdue(MaintenanceRepository $maintenanceRepository): Response
    {
        $maintenances = $maintenanceRepository->findOverdue();

        return $this->render('maintenance/overdue.html.twig', [
            'maintenances' => $maintenances,
        ]);
    }

    #[Route('/calendar/{year}/{month}', name: 'app_maintenance_calendar', methods: ['GET'], defaults: ['year' => null, 'month' => null])]
    public function calendar(?int $year, ?int $month, MaintenanceRepository $maintenanceRepository): Response
    {
        $year = $year ?? (int)date('Y');
        $month = $month ?? (int)date('n');
        
        $startDate = new \DateTime("{$year}-{$month}-01");
        $endDate = clone $startDate;
        $endDate->modify('last day of this month');
        
        // Récupérer toutes les maintenances programmées pour ce mois
        $maintenances = $maintenanceRepository->createQueryBuilder('m')
            ->andWhere('m.scheduledDate >= :startDate')
            ->andWhere('m.scheduledDate <= :endDate')
            ->setParameter('startDate', $startDate)
            ->setParameter('endDate', $endDate)
            ->orderBy('m.scheduledDate', 'ASC')
            ->getQuery()
            ->getResult();
        
        // Organiser par date
        $calendar = [];
        foreach ($maintenances as $maintenance) {
            $date = $maintenance->getScheduledDate()->format('Y-m-d');
            if (!isset($calendar[$date])) {
                $calendar[$date] = [];
            }
            $calendar[$date][] = $maintenance;
        }

        return $this->render('maintenance/calendar.html.twig', [
            'year' => $year,
            'month' => $month,
            'calendar' => $calendar,
            'startDate' => $startDate,
            'endDate' => $endDate,
        ]);
    }

    #[Route('/reports', name: 'app_maintenance_reports', methods: ['GET'])]
    public function reports(Request $request, MaintenanceRepository $maintenanceRepository): Response
    {
        $year = $request->query->get('year', date('Y'));
        $propertyId = $request->query->get('property');

        $statistics = $maintenanceRepository->getStatistics();
        
        if ($propertyId) {
            $totalCosts = $maintenanceRepository->getMaintenanceCostsByProperty($propertyId);
        } else {
            $totalCosts = $maintenanceRepository->getMaintenanceCostsByPeriod((int)$year);
        }

        return $this->render('maintenance/reports.html.twig', [
            'statistics' => $statistics,
            'totalCosts' => $totalCosts,
            'year' => $year,
            'property_id' => $propertyId,
        ]);
    }
}