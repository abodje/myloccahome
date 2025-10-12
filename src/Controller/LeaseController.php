<?php

namespace App\Controller;

use App\Entity\Lease;
use App\Form\LeaseType;
use App\Repository\LeaseRepository;
use App\Repository\PaymentRepository;
use App\Service\PdfService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/contrats')]
class LeaseController extends AbstractController
{
    #[Route('/', name: 'app_lease_index', methods: ['GET'])]
    public function index(LeaseRepository $leaseRepository, Request $request): Response
    {
        $status = $request->query->get('status');
        $search = $request->query->get('search');

        if ($status) {
            $leases = $leaseRepository->findByStatus($status);
        } else {
            $leases = $leaseRepository->findBy([], ['startDate' => 'DESC']);
        }

        $stats = $leaseRepository->getStatistics();

        return $this->render('lease/index.html.twig', [
            'leases' => $leases,
            'stats' => $stats,
            'current_status' => $status,
            'search' => $search,
        ]);
    }

    #[Route('/nouveau', name: 'app_lease_new', methods: ['GET', 'POST'])]
    public function new(Request $request, EntityManagerInterface $entityManager): Response
    {
        $lease = new Lease();

        // Pré-remplir avec les paramètres de l'URL si disponibles
        $propertyId = $request->query->get('property');
        $tenantId = $request->query->get('tenant');

        if ($propertyId) {
            $property = $entityManager->getRepository(\App\Entity\Property::class)->find($propertyId);
            if ($property) {
                $lease->setProperty($property);
                $lease->setMonthlyRent($property->getMonthlyRent());
                $lease->setCharges($property->getCharges());
                $lease->setDeposit($property->getDeposit());
            }
        }

        if ($tenantId) {
            $tenant = $entityManager->getRepository(\App\Entity\Tenant::class)->find($tenantId);
            if ($tenant) {
                $lease->setTenant($tenant);
            }
        }

        $form = $this->createForm(LeaseType::class, $lease);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            // Mettre à jour le statut de la propriété
            if ($lease->getProperty()) {
                $lease->getProperty()->setStatus('Occupé');
            }

            $entityManager->persist($lease);
            $entityManager->flush();

            $this->addFlash('success', 'Le contrat de location a été créé avec succès.');

            return $this->redirectToRoute('app_lease_show', ['id' => $lease->getId()]);
        }

        return $this->render('lease/new.html.twig', [
            'lease' => $lease,
            'form' => $form,
        ]);
    }

    #[Route('/{id}', name: 'app_lease_show', methods: ['GET'])]
    public function show(Lease $lease, PaymentRepository $paymentRepository): Response
    {
        $payments = $paymentRepository->findByLease($lease->getId());

        return $this->render('lease/show.html.twig', [
            'lease' => $lease,
            'payments' => $payments,
        ]);
    }

    #[Route('/{id}/modifier', name: 'app_lease_edit', methods: ['GET', 'POST'])]
    public function edit(Request $request, Lease $lease, EntityManagerInterface $entityManager): Response
    {
        $form = $this->createForm(LeaseType::class, $lease);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $lease->setUpdatedAt(new \DateTime());
            $entityManager->flush();

            $this->addFlash('success', 'Le contrat de location a été modifié avec succès.');

            return $this->redirectToRoute('app_lease_show', ['id' => $lease->getId()]);
        }

        return $this->render('lease/edit.html.twig', [
            'lease' => $lease,
            'form' => $form,
        ]);
    }

    #[Route('/{id}/resilier', name: 'app_lease_terminate', methods: ['POST'])]
    public function terminate(Request $request, Lease $lease, EntityManagerInterface $entityManager): Response
    {
        if ($this->isCsrfTokenValid('terminate'.$lease->getId(), $request->getPayload()->getString('_token'))) {
            $endDate = $request->getPayload()->getString('end_date');

            $lease->setEndDate(new \DateTime($endDate));
            $lease->setStatus('Terminé');
            $lease->setUpdatedAt(new \DateTime());

            // Libérer la propriété
            if ($lease->getProperty()) {
                $lease->getProperty()->setStatus('Libre');
            }

            $entityManager->flush();

            $this->addFlash('success', 'Le contrat a été résilié avec succès.');
        }

        return $this->redirectToRoute('app_lease_show', ['id' => $lease->getId()]);
    }

    #[Route('/{id}/renouveler', name: 'app_lease_renew', methods: ['GET', 'POST'])]
    public function renew(Request $request, Lease $lease, EntityManagerInterface $entityManager): Response
    {
        // Créer un nouveau contrat basé sur l'ancien
        $newLease = new Lease();
        $newLease->setProperty($lease->getProperty())
                 ->setTenant($lease->getTenant())
                 ->setMonthlyRent($lease->getMonthlyRent())
                 ->setCharges($lease->getCharges())
                 ->setDeposit($lease->getDeposit())
                 ->setRentDueDay($lease->getRentDueDay())
                 ->setTerms($lease->getTerms());

        $form = $this->createForm(LeaseType::class, $newLease);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            // Terminer l'ancien contrat
            $lease->setStatus('Terminé');
            $lease->setUpdatedAt(new \DateTime());

            $entityManager->persist($newLease);
            $entityManager->flush();

            $this->addFlash('success', 'Le contrat a été renouvelé avec succès.');

            return $this->redirectToRoute('app_lease_show', ['id' => $newLease->getId()]);
        }

        return $this->render('lease/renew.html.twig', [
            'old_lease' => $lease,
            'new_lease' => $newLease,
            'form' => $form,
        ]);
    }

    #[Route('/{id}/generer-loyers', name: 'app_lease_generate_rents', methods: ['POST'])]
    public function generateRents(Lease $lease, EntityManagerInterface $entityManager): Response
    {
        if (!$lease->isActive()) {
            $this->addFlash('error', 'Impossible de générer des loyers pour un contrat inactif.');
            return $this->redirectToRoute('app_lease_show', ['id' => $lease->getId()]);
        }

        $generated = 0;
        $currentDate = new \DateTime();

        // Générer les loyers pour les 6 prochains mois (ou jusqu'à la fin du bail)
        for ($i = 0; $i < 6; $i++) {
            $dueDate = clone $currentDate;
            $dueDate->modify("+{$i} months");
            $dueDate->setDate($dueDate->format('Y'), $dueDate->format('n'), $lease->getRentDueDay() ?? 1);

            // ⚠️ VÉRIFICATION : Ne pas générer de loyer après la fin du bail
            if ($lease->getEndDate() && $dueDate > $lease->getEndDate()) {
                // La date d'échéance dépasse la fin du bail, on arrête la génération
                break; // Arrêter la boucle complètement
            }

            // Vérifier si le loyer n'existe pas déjà
            $existingPayment = $entityManager->getRepository(\App\Entity\Payment::class)->findOneBy([
                'lease' => $lease,
                'dueDate' => $dueDate,
                'type' => 'Loyer'
            ]);

            if (!$existingPayment) {
                $payment = new \App\Entity\Payment();
                $payment->setLease($lease)
                       ->setDueDate($dueDate)
                       ->setAmount($lease->getMonthlyRent())
                       ->setType('Loyer')
                       ->setStatus('En attente');

                $entityManager->persist($payment);
                $generated++;
            }
        }

        if ($generated > 0) {
            $entityManager->flush();
            $this->addFlash('success', "{$generated} loyers ont été générés pour les prochains mois.");
        } else {
            $this->addFlash('info', 'Aucun nouveau loyer à générer.');
        }

        return $this->redirectToRoute('app_lease_show', ['id' => $lease->getId()]);
    }

    #[Route('/expires-bientot', name: 'app_lease_expiring', methods: ['GET'])]
    public function expiring(LeaseRepository $leaseRepository): Response
    {
        $expiringLeases = $leaseRepository->findExpiringSoon();

        return $this->render('lease/expiring.html.twig', [
            'expiring_leases' => $expiringLeases,
        ]);
    }

    #[Route('/{id}/supprimer', name: 'app_lease_delete', methods: ['POST'])]
    public function delete(Request $request, Lease $lease, EntityManagerInterface $entityManager): Response
    {
        if ($this->isCsrfTokenValid('delete'.$lease->getId(), $request->getPayload()->getString('_token'))) {
            // Libérer la propriété
            if ($lease->getProperty()) {
                $lease->getProperty()->setStatus('Libre');
            }

            $entityManager->remove($lease);
            $entityManager->flush();

            $this->addFlash('success', 'Le contrat a été supprimé avec succès.');
        }

        return $this->redirectToRoute('app_lease_index');
    }

    #[Route('/{id}/contrat-pdf', name: 'app_lease_contract_pdf', methods: ['GET'])]
    public function downloadContract(Lease $lease, PdfService $pdfService): Response
    {
        $pdfService->generateLeaseContract($lease, true);
        return new Response(); // Le PDF est déjà envoyé par generateLeaseContract
    }

    #[Route('/{id}/echeancier-pdf', name: 'app_lease_schedule_pdf', methods: ['GET'])]
    public function downloadSchedule(Lease $lease, PdfService $pdfService, Request $request): Response
    {
        $months = $request->query->getInt('months', 12);
        $pdfService->generatePaymentSchedule($lease, $months, true);
        return new Response(); // Le PDF est déjà envoyé par generatePaymentSchedule
    }
}
