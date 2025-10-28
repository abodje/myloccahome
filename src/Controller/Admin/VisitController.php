<?php

namespace App\Controller\Admin;

use App\Entity\TenantApplication;
use App\Entity\Visit;
use App\Entity\VisitSlot;
use App\Form\VisitSlotType;
use App\Repository\TenantApplicationRepository;
use App\Repository\VisitRepository;
use App\Repository\VisitSlotRepository;
use App\Service\ApplicationScoringService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/admin/visites')]
class VisitController extends AbstractController
{
    public function __construct(
        private EntityManagerInterface $entityManager,
        private ApplicationScoringService $scoringService
    ) {
    }

    /**
     * Vérifie que l'utilisateur a accès à la gestion des visites
     */
    private function checkVisitManagementAccess(): void
    {
        $user = $this->getUser();

        // Vérifier que l'utilisateur est ADMIN, MANAGER ou SUPER_ADMIN
        $allowedRoles = ['ROLE_ADMIN', 'ROLE_MANAGER', 'ROLE_SUPER_ADMIN'];
        $hasRole = false;
        foreach ($allowedRoles as $role) {
            if (in_array($role, $user->getRoles())) {
                $hasRole = true;
                break;
            }
        }

        if (!$hasRole) {
            throw $this->createAccessDeniedException('Accès refusé : vous devez être administrateur ou gestionnaire.');
        }

        // SUPER_ADMIN a accès à tout, pas besoin de vérifier la feature
        if (in_array('ROLE_SUPER_ADMIN', $user->getRoles())) {
            return;
        }

        // Vérifier que l'organisation existe et a la feature "visit_management"
        $organization = $user->getOrganization();
        if (!$organization) {
            throw $this->createAccessDeniedException('Aucune organisation associée à votre compte.');
        }

        if (!$organization->hasFeature('visit_management')) {
            throw $this->createAccessDeniedException('Cette fonctionnalité n\'est pas activée pour votre organisation.');
        }
    }

    /**
     * Dashboard des visites et candidatures
     */
    #[Route('/', name: 'app_admin_visits_index', methods: ['GET'])]
    #[IsGranted('ROLE_MANAGER')]
    public function index(
        VisitSlotRepository $visitSlotRepository,
        VisitRepository $visitRepository,
        TenantApplicationRepository $applicationRepository
    ): Response {
        $this->checkVisitManagementAccess();

        $organization = $this->getUser()->getOrganization();

        if (!$organization) {
            $this->addFlash('error', 'Aucune organisation associée à votre compte. Veuillez contacter l\'administrateur.');
            return $this->redirectToRoute('app_dashboard');
        }

        // Statistiques
        $upcomingVisits = $visitRepository->createQueryBuilder('v')
            ->join('v.visitSlot', 'vs')
            ->where('vs.organization = :org')
            ->andWhere('v.status IN (:statuses)')
            ->andWhere('vs.startTime >= :now')
            ->setParameter('org', $organization)
            ->setParameter('statuses', ['pending', 'confirmed'])
            ->setParameter('now', new \DateTime())
            ->orderBy('vs.startTime', 'ASC')
            ->setMaxResults(10)
            ->getQuery()
            ->getResult();

        $pendingApplications = $applicationRepository->findPendingForOrganization($organization->getId());
        $applicationStats = $applicationRepository->getStatsByStatus($organization->getId());

        return $this->render('admin/visit/index.html.twig', [
            'upcoming_visits' => $upcomingVisits,
            'pending_applications' => $pendingApplications,
            'application_stats' => $applicationStats
        ]);
    }

    /**
     * Gestion des créneaux de visite
     */
    #[Route('/creneaux', name: 'app_admin_visit_slots', methods: ['GET'])]
    #[IsGranted('ROLE_MANAGER')]
    public function slots(VisitSlotRepository $visitSlotRepository): Response
    {
        $this->checkVisitManagementAccess();

        $organization = $this->getUser()->getOrganization();

        if (!$organization) {
            $this->addFlash('error', 'Aucune organisation associée à votre compte.');
            return $this->redirectToRoute('app_admin_visits_index');
        }

        $slots = $visitSlotRepository->findUpcomingForOrganization($organization->getId(), 50);

        return $this->render('admin/visit/slots.html.twig', [
            'slots' => $slots
        ]);
    }

    /**
     * Créer un créneau de visite
     */
    #[Route('/creneaux/nouveau', name: 'app_admin_visit_slot_new', methods: ['GET', 'POST'])]
    #[IsGranted('ROLE_MANAGER')]
    public function newSlot(Request $request): Response
    {
        $this->checkVisitManagementAccess();

        $organization = $this->getUser()->getOrganization();

        if (!$organization) {
            $this->addFlash('error', 'Aucune organisation associée à votre compte.');
            return $this->redirectToRoute('app_admin_visits_index');
        }

        $slot = new VisitSlot();
        $slot->setOrganization($organization);

        $form = $this->createForm(VisitSlotType::class, $slot);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $this->entityManager->persist($slot);
            $this->entityManager->flush();

            $this->addFlash('success', 'Créneau de visite créé avec succès.');
            return $this->redirectToRoute('app_admin_visit_slots');
        }

        return $this->render('admin/visit/slot_form.html.twig', [
            'form' => $form->createView(),
            'slot' => $slot
        ]);
    }

    /**
     * Modifier un créneau de visite
     */
    #[Route('/creneaux/{id}/modifier', name: 'app_admin_visit_slot_edit', methods: ['GET', 'POST'])]
    #[IsGranted('ROLE_MANAGER')]
    public function editSlot(VisitSlot $slot, Request $request): Response
    {
        $this->checkVisitManagementAccess();

        $form = $this->createForm(VisitSlotType::class, $slot);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $slot->setUpdatedAt(new \DateTime());
            $this->entityManager->flush();

            $this->addFlash('success', 'Créneau de visite modifié avec succès.');
            return $this->redirectToRoute('app_admin_visit_slots');
        }

        return $this->render('admin/visit/slot_form.html.twig', [
            'form' => $form->createView(),
            'slot' => $slot
        ]);
    }

    /**
     * Supprimer un créneau de visite
     */
    #[Route('/creneaux/{id}/supprimer', name: 'app_admin_visit_slot_delete', methods: ['POST'])]
    #[IsGranted('ROLE_MANAGER')]
    public function deleteSlot(VisitSlot $slot): Response
    {
        $this->checkVisitManagementAccess();

        // Vérifier qu'il n'y a pas de visites réservées
        if ($slot->getCurrentVisitors() > 0) {
            $this->addFlash('error', 'Impossible de supprimer un créneau avec des visites réservées.');
            return $this->redirectToRoute('app_admin_visit_slots');
        }

        $this->entityManager->remove($slot);
        $this->entityManager->flush();

        $this->addFlash('success', 'Créneau de visite supprimé.');
        return $this->redirectToRoute('app_admin_visit_slots');
    }

    /**
     * Liste des visites
     */
    #[Route('/reservations', name: 'app_admin_visits_list', methods: ['GET'])]
    #[IsGranted('ROLE_MANAGER')]
    public function visitsList(VisitRepository $visitRepository): Response
    {
        $this->checkVisitManagementAccess();

        $organization = $this->getUser()->getOrganization();

        if (!$organization) {
            $this->addFlash('error', 'Aucune organisation associée à votre compte.');
            return $this->redirectToRoute('app_admin_visits_index');
        }

        $visits = $visitRepository->createQueryBuilder('v')
            ->join('v.visitSlot', 'vs')
            ->where('vs.organization = :org')
            ->setParameter('org', $organization)
            ->orderBy('vs.startTime', 'DESC')
            ->getQuery()
            ->getResult();

        return $this->render('admin/visit/visits_list.html.twig', [
            'visits' => $visits
        ]);
    }

    /**
     * Détails d'une visite
     */
    #[Route('/reservations/{id}', name: 'app_admin_visit_show', methods: ['GET'])]
    #[IsGranted('ROLE_MANAGER')]
    public function visitShow(Visit $visit): Response
    {
        $this->checkVisitManagementAccess();

        return $this->render('admin/visit/visit_show.html.twig', [
            'visit' => $visit
        ]);
    }

    /**
     * Confirmer une visite
     */
    #[Route('/reservations/{id}/confirmer', name: 'app_admin_visit_confirm', methods: ['POST'])]
    #[IsGranted('ROLE_MANAGER')]
    public function confirmVisit(Visit $visit): Response
    {
        $this->checkVisitManagementAccess();

        $visit->setStatus('confirmed');
        $visit->setConfirmedAt(new \DateTime());
        $this->entityManager->flush();

        $this->addFlash('success', 'Visite confirmée.');
        return $this->redirectToRoute('app_admin_visit_show', ['id' => $visit->getId()]);
    }

    /**
     * Marquer une visite comme effectuée
     */
    #[Route('/reservations/{id}/effectuee', name: 'app_admin_visit_complete', methods: ['POST'])]
    #[IsGranted('ROLE_MANAGER')]
    public function completeVisit(Visit $visit): Response
    {
        $this->checkVisitManagementAccess();

        $visit->setStatus('completed');
        $this->entityManager->flush();

        $this->addFlash('success', 'Visite marquée comme effectuée.');
        return $this->redirectToRoute('app_admin_visit_show', ['id' => $visit->getId()]);
    }

    /**
     * Liste des candidatures
     */
    #[Route('/candidatures', name: 'app_admin_applications_list', methods: ['GET'])]
    #[IsGranted('ROLE_MANAGER')]
    public function applicationsList(
        TenantApplicationRepository $applicationRepository,
        EntityManagerInterface $em
    ): Response {
        $this->checkVisitManagementAccess();

        $organization = $this->getUser()->getOrganization();

        if (!$organization) {
            $this->addFlash('error', 'Aucune organisation associée à votre compte.');
            return $this->redirectToRoute('app_admin_visits_index');
        }

        // Récupérer toutes les propriétés pour le filtre
        $properties = $em->getRepository(\App\Entity\Property::class)
            ->createQueryBuilder('p')
            ->where('p.organization = :org')
            ->setParameter('org', $organization)
            ->orderBy('p.address', 'ASC')
            ->getQuery()
            ->getResult();

        $applications = $applicationRepository->createQueryBuilder('ta')
            ->where('ta.organization = :org')
            ->setParameter('org', $organization)
            ->orderBy('ta.score', 'DESC')
            ->addOrderBy('ta.createdAt', 'DESC')
            ->getQuery()
            ->getResult();

        return $this->render('admin/visit/applications_list.html.twig', [
            'applications' => $applications,
            'properties' => $properties
        ]);
    }

    /**
     * Détails d'une candidature avec scoring
     */
    #[Route('/candidatures/{id}', name: 'app_admin_application_show', methods: ['GET'])]
    #[IsGranted('ROLE_MANAGER')]
    public function applicationShow(TenantApplication $application): Response
    {
        $this->checkVisitManagementAccess();

        // Recalculer le score si nécessaire
        if (!$application->getScore()) {
            $this->scoringService->scoreApplication($application);
            $this->entityManager->flush();
        }

        return $this->render('admin/visit/application_show.html.twig', [
            'application' => $application
        ]);
    }

    /**
     * Approuver une candidature
     */
    #[Route('/candidatures/{id}/approuver', name: 'app_admin_application_approve', methods: ['POST'])]
    #[IsGranted('ROLE_MANAGER')]
    public function approveApplication(TenantApplication $application, Request $request): Response
    {
        $this->checkVisitManagementAccess();

        $application->setStatus('approved');
        $application->setReviewedAt(new \DateTime());
        $application->setReviewedBy($this->getUser());
        $application->setReviewNotes($request->request->get('notes'));

        $this->entityManager->flush();

        $this->addFlash('success', 'Candidature approuvée.');
        return $this->redirectToRoute('app_admin_application_show', ['id' => $application->getId()]);
    }

    /**
     * Rejeter une candidature
     */
    #[Route('/candidatures/{id}/rejeter', name: 'app_admin_application_reject', methods: ['POST'])]
    #[IsGranted('ROLE_MANAGER')]
    public function rejectApplication(TenantApplication $application, Request $request): Response
    {
        $this->checkVisitManagementAccess();

        $application->setStatus('rejected');
        $application->setReviewedAt(new \DateTime());
        $application->setReviewedBy($this->getUser());
        $application->setReviewNotes($request->request->get('notes', 'Candidature rejetée'));

        $this->entityManager->flush();

        $this->addFlash('success', 'Candidature rejetée.');
        return $this->redirectToRoute('app_admin_application_show', ['id' => $application->getId()]);
    }

    /**
     * Recalculer le score d'une candidature
     */
    #[Route('/candidatures/{id}/recalculer-score', name: 'app_admin_application_rescore', methods: ['POST'])]
    #[IsGranted('ROLE_MANAGER')]
    public function rescoreApplication(TenantApplication $application): Response
    {
        $this->checkVisitManagementAccess();

        $this->scoringService->scoreApplication($application);
        $this->entityManager->flush();

        $this->addFlash('success', 'Score recalculé avec succès.');
        return $this->redirectToRoute('app_admin_application_show', ['id' => $application->getId()]);
    }
}
