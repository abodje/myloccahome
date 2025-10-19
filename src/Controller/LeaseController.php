<?php

namespace App\Controller;

use App\Entity\Lease;
use App\Form\LeaseType;
use App\Repository\LeaseRepository;
use App\Repository\PaymentRepository;
use App\Service\PdfService;
use App\Service\ContractGenerationService;
use App\Service\PaymentSettingsService;
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
        /** @var \App\Entity\User $user */
        $user = $this->getUser();
        $status = $request->query->get('status');
        $search = $request->query->get('search');

        // Filtrer les contrats selon le rôle de l'utilisateur connecté
        if ($user && in_array('ROLE_TENANT', $user->getRoles())) {
            // LOCATAIRE : Voir uniquement ses propres contrats
            $tenant = $user->getTenant();
            if ($tenant) {
                if ($status) {
                    $leases = $leaseRepository->findBy(['tenant' => $tenant, 'status' => $status], ['startDate' => 'DESC']);
                } else {
                    $leases = $leaseRepository->findBy(['tenant' => $tenant], ['startDate' => 'DESC']);
                }
                error_log("LeaseController - Tenant: " . $tenant->getFullName() . ", Found " . count($leases) . " leases");
            } else {
                $leases = [];
                error_log("LeaseController - Tenant: No tenant profile found for user " . $user->getEmail());
            }
        } elseif ($user && in_array('ROLE_MANAGER', $user->getRoles())) {
            // MANAGER : Voir les contrats de sa société
            $owner = $user->getOwner();
            if ($owner) {
                if ($status) {
                    $leases = $leaseRepository->findByManagerAndStatus($owner->getId(), $status);
                } else {
                    $leases = $leaseRepository->findByManager($owner->getId());
                }
                error_log("LeaseController - Manager: " . $owner->getFullName() . ", Found " . count($leases) . " leases");
            } else {
                $leases = [];
                error_log("LeaseController - Manager: No owner profile found for user " . $user->getEmail());
            }
        } elseif ($user && (in_array('ROLE_ADMIN', $user->getRoles()) || in_array('ROLE_SUPER_ADMIN', $user->getRoles()))) {
            // ADMIN/SUPER_ADMIN : Filtrer par organization/company
            $organization = $user->getOrganization();
            $company = $user->getCompany();

            if ($company) {
                // Admin avec company spécifique : voir UNIQUEMENT les contrats de sa company
                error_log("LeaseController - Admin with company: " . $company->getName());
                if ($status) {
                    $leases = $leaseRepository->findByCompanyAndStatus($company->getId(), $status);
                } else {
                    $leases = $leaseRepository->findByCompany($company->getId());
                }
            } elseif ($organization) {
                // Admin sans company : voir TOUS les contrats de son organization
                error_log("LeaseController - Admin with organization: " . $organization->getName());
                if ($status) {
                    $leases = $leaseRepository->findByOrganizationAndStatus($organization->getId(), $status);
                } else {
                    $leases = $leaseRepository->findByOrganization($organization->getId());
                }
            } else {
                // SUPER_ADMIN sans organization/company : voir TOUT
                error_log("LeaseController - Super Admin without organization/company, showing all leases");
                if ($status) {
                    $leases = $leaseRepository->findByStatus($status);
                } else {
                    $leases = $leaseRepository->findBy([], ['startDate' => 'DESC']);
                }
            }
        } else {
            // Utilisateur sans rôle spécifique : aucun accès
            $leases = [];
            error_log("LeaseController - User without specific role, no access");
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
    public function new(
        Request $request,
        EntityManagerInterface $entityManager,
        PaymentSettingsService $paymentSettings
    ): Response {
        $lease = new Lease();

        // Appliquer le jour d'échéance par défaut configuré
        $lease->setRentDueDay($paymentSettings->getDefaultRentDueDay());

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
        try {
            // Générer le PDF
            $pdfContent = $pdfService->generateLeaseContract($lease, false);

            // Créer la réponse avec les headers appropriés
            $response = new Response($pdfContent);
            $response->headers->set('Content-Type', 'application/pdf');
            $response->headers->set('Content-Disposition', sprintf(
                'attachment; filename="Contrat_Bail_%s_%s.pdf"',
                $lease->getId(),
                $lease->getStartDate()->format('Y-m-d')
            ));
            $response->headers->set('Cache-Control', 'no-cache, must-revalidate');
            $response->headers->set('Pragma', 'no-cache');

            return $response;
        } catch (\Exception $e) {
            // En cas d'erreur, rediriger vers la page du bail avec un message d'erreur
            $this->addFlash('error', 'Erreur lors de la génération du contrat : ' . $e->getMessage());
            return $this->redirectToRoute('app_lease_show', ['id' => $lease->getId()]);
        }
    }

    #[Route('/{id}/test-pdf', name: 'app_lease_test_pdf', methods: ['GET'])]
    public function testPdf(Lease $lease, PdfService $pdfService): Response
    {
        try {
            $pdfContent = $pdfService->generateLeaseContract($lease, false);

            return new Response($pdfContent, 200, [
                'Content-Type' => 'application/pdf',
                'Content-Disposition' => 'inline; filename="test_contract.pdf"'
            ]);
        } catch (\Exception $e) {
            return new Response('Erreur: ' . $e->getMessage(), 500);
        }
    }

    #[Route('/{id}/echeancier-pdf', name: 'app_lease_schedule_pdf', methods: ['GET'])]
    public function downloadSchedule(Lease $lease, PdfService $pdfService, Request $request): Response
    {
        $months = $request->query->getInt('months', 12);
        $pdfService->generatePaymentSchedule($lease, $months, true);
        return new Response(); // Le PDF est déjà envoyé par generatePaymentSchedule
    }

    #[Route('/{id}/generer-contrat-document', name: 'app_lease_generate_contract_document', methods: ['POST'])]
    public function generateContractDocument(Lease $lease, ContractGenerationService $contractService): Response
    {
        try {
            $document = $contractService->generateContractManually($lease);
            $this->addFlash('success', '📄 Le contrat de bail a été généré et enregistré dans les documents !');
        } catch (\Exception $e) {
            $this->addFlash('error', 'Erreur lors de la génération : ' . $e->getMessage());
        }

        return $this->redirectToRoute('app_lease_show', ['id' => $lease->getId()]);
    }
}
