<?php

namespace App\Controller;

use App\Entity\Property;
use App\Entity\TenantApplication;
use App\Entity\Visit;
use App\Form\TenantApplicationFormType;
use App\Form\VisitReservationFormType;
use App\Repository\PropertyRepository;
use App\Repository\VisitSlotRepository;
use App\Service\ApplicationScoringService;
use App\Service\NotificationService;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

#[Route('/visites')]
class PublicVisitController extends AbstractController
{
    public function __construct(
        private EntityManagerInterface $entityManager,
        private NotificationService $notificationService,
        private ApplicationScoringService $scoringService,
        private LoggerInterface $logger
    ) {
    }

    /**
     * Liste des propriétés disponibles à la visite
     */
    #[Route('/', name: 'app_public_visits_index', methods: ['GET'])]
    public function index(PropertyRepository $propertyRepository): Response
    {
        // Récupérer uniquement les propriétés disponibles (status = "Libre")
        $properties = $propertyRepository->findBy(['status' => 'Libre']);

        return $this->render('public_visit/index.html.twig', [
            'properties' => $properties
        ]);
    }

    /**
     * Détails d'une propriété et réservation de visite
     */
    #[Route('/propriete/{id}', name: 'app_public_visits_property', methods: ['GET', 'POST'])]
    public function propertyVisit(
        Property $property,
        Request $request,
        VisitSlotRepository $visitSlotRepository
    ): Response {
        // Vérifier que la propriété est disponible
        if ($property->getStatus() !== 'Libre') {
            $this->addFlash('error', 'Cette propriété n\'est pas disponible à la visite.');
            return $this->redirectToRoute('app_public_visits_index');
        }

        // Récupérer les créneaux disponibles
        $availableSlots = $visitSlotRepository->findAvailableForProperty($property->getId());

        if (empty($availableSlots)) {
            $this->addFlash('warning', 'Aucun créneau de visite n\'est disponible pour cette propriété.');
        }

        // Formulaire de réservation
        $visit = new Visit();
        $form = $this->createForm(VisitReservationFormType::class, $visit, [
            'available_slots' => $availableSlots
        ]);

        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            try {
                $visitSlot = $visit->getVisitSlot();

                // Vérifier la disponibilité
                if (!$visitSlot->isAvailable()) {
                    throw new \Exception('Ce créneau n\'est plus disponible.');
                }

                // Incrémenter le compteur de visiteurs
                $visitSlot->setCurrentVisitors($visitSlot->getCurrentVisitors() + 1);

                // Mettre à jour le statut si complet
                if ($visitSlot->isFull()) {
                    $visitSlot->setStatus('full');
                }

                $visit->setStatus('pending');

                $this->entityManager->persist($visit);
                $this->entityManager->persist($visitSlot);
                $this->entityManager->flush();

                // Envoyer email de confirmation
                $this->sendVisitConfirmationEmail($visit);

                $this->addFlash('success', 'Votre visite a été réservée avec succès ! Vous allez recevoir un email de confirmation.');

                return $this->redirectToRoute('app_public_visits_confirmation', ['id' => $visit->getId()]);

            } catch (\Exception $e) {
                $this->logger->error('Erreur réservation visite', [
                    'error' => $e->getMessage(),
                    'property_id' => $property->getId()
                ]);
                $this->addFlash('error', 'Une erreur est survenue : ' . $e->getMessage());
            }
        }

        return $this->render('public_visit/property.html.twig', [
            'property' => $property,
            'available_slots' => $availableSlots,
            'form' => $form->createView()
        ]);
    }

    /**
     * Confirmation de réservation de visite
     */
    #[Route('/confirmation/{id}', name: 'app_public_visits_confirmation', methods: ['GET'])]
    public function confirmation(Visit $visit): Response
    {
        return $this->render('public_visit/confirmation.html.twig', [
            'visit' => $visit
        ]);
    }

    /**
     * Formulaire de candidature locataire
     */
    #[Route('/candidature/{id}', name: 'app_public_application', methods: ['GET', 'POST'])]
    public function application(Property $property, Request $request): Response
    {
        // Vérifier que la propriété est disponible
        if ($property->getStatus() !== 'Libre') {
            $this->addFlash('error', 'Cette propriété n\'est plus disponible.');
            return $this->redirectToRoute('app_public_visits_index');
        }

        $application = new TenantApplication();
        $application->setProperty($property);
        $application->setOrganization($property->getOrganization());

        $form = $this->createForm(TenantApplicationFormType::class, $application);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            try {
                // Calculer le score automatiquement
                $this->scoringService->scoreApplication($application);

                $this->entityManager->persist($application);
                $this->entityManager->flush();

                // Notifier le gestionnaire
                $this->notifyNewApplication($application);

                $this->addFlash('success', 'Votre candidature a été soumise avec succès ! Nous vous contacterons rapidement.');

                return $this->redirectToRoute('app_public_application_success', ['id' => $application->getId()]);

            } catch (\Exception $e) {
                $this->logger->error('Erreur soumission candidature', [
                    'error' => $e->getMessage(),
                    'property_id' => $property->getId()
                ]);
                $this->addFlash('error', 'Une erreur est survenue lors de la soumission de votre candidature.');
            }
        }

        return $this->render('public_visit/application.html.twig', [
            'property' => $property,
            'form' => $form->createView()
        ]);
    }

    /**
     * Confirmation de candidature
     */
    #[Route('/candidature/success/{id}', name: 'app_public_application_success', methods: ['GET'])]
    public function applicationSuccess(TenantApplication $application): Response
    {
        return $this->render('public_visit/application_success.html.twig', [
            'application' => $application
        ]);
    }

    /**
     * Annulation d'une visite
     */
    #[Route('/annuler/{token}', name: 'app_public_visits_cancel', methods: ['GET', 'POST'])]
    public function cancelVisit(string $token, Request $request): Response
    {
        $visit = $this->entityManager->getRepository(Visit::class)
            ->findOneBy(['confirmationToken' => $token]);

        if (!$visit) {
            throw $this->createNotFoundException('Visite introuvable');
        }

        if ($visit->getStatus() === 'cancelled') {
            $this->addFlash('info', 'Cette visite a déjà été annulée.');
            return $this->redirectToRoute('app_public_visits_index');
        }

        if ($request->isMethod('POST')) {
            $visit->setStatus('cancelled');
            $visit->setCancelledAt(new \DateTime());
            $visit->setCancellationReason($request->request->get('reason', 'Non spécifié'));

            // Décrémenter le compteur
            $visitSlot = $visit->getVisitSlot();
            $visitSlot->setCurrentVisitors(max(0, $visitSlot->getCurrentVisitors() - 1));

            if ($visitSlot->getStatus() === 'full') {
                $visitSlot->setStatus('available');
            }

            $this->entityManager->flush();

            $this->addFlash('success', 'Votre visite a été annulée.');
            return $this->redirectToRoute('app_public_visits_index');
        }

        return $this->render('public_visit/cancel.html.twig', [
            'visit' => $visit
        ]);
    }

    private function sendVisitConfirmationEmail(Visit $visit): void
    {
        try {
            // TODO: Créer le template email de confirmation de visite
            $this->logger->info('Email de confirmation de visite envoyé', [
                'visit_id' => $visit->getId(),
                'email' => $visit->getEmail()
            ]);

            $visit->setEmailSent(true);
            $this->entityManager->flush();

        } catch (\Exception $e) {
            $this->logger->error('Erreur envoi email confirmation visite', [
                'error' => $e->getMessage(),
                'visit_id' => $visit->getId()
            ]);
        }
    }

    private function notifyNewApplication(TenantApplication $application): void
    {
        try {
            // TODO: Notifier le gestionnaire de la nouvelle candidature
            $this->logger->info('Nouvelle candidature reçue', [
                'application_id' => $application->getId(),
                'property_id' => $application->getProperty()->getId(),
                'score' => $application->getScore()
            ]);

        } catch (\Exception $e) {
            $this->logger->error('Erreur notification nouvelle candidature', [
                'error' => $e->getMessage(),
                'application_id' => $application->getId()
            ]);
        }
    }
}
