<?php

namespace App\Controller;

use App\Entity\Lease;
use App\Repository\LeaseRepository;
use App\Repository\PropertyRepository;
use App\Repository\TenantRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

#[Route('/leases')]
class LeaseController extends AbstractController
{
    #[Route('/', name: 'app_lease_index', methods: ['GET'])]
    public function index(LeaseRepository $leaseRepository, Request $request): Response
    {
        $status = $request->query->get('status', '');

        if ($status === 'active') {
            $leases = $leaseRepository->findActive();
        } elseif ($status === 'ending_soon') {
            $leases = $leaseRepository->findEndingSoon(30);
        } else {
            $leases = $leaseRepository->findAllWithRelations();
        }

        return $this->render('lease/index.html.twig', [
            'leases' => $leases,
            'status' => $status,
        ]);
    }

    #[Route('/new', name: 'app_lease_new', methods: ['GET', 'POST'])]
    public function new(
        Request $request, 
        EntityManagerInterface $entityManager,
        PropertyRepository $propertyRepository,
        TenantRepository $tenantRepository
    ): Response {
        $lease = new Lease();
        
        if ($request->isMethod('POST')) {
            $propertyId = $request->get('property_id');
            $tenantId = $request->get('tenant_id');
            
            $property = $propertyRepository->find($propertyId);
            $tenant = $tenantRepository->find($tenantId);
            
            if (!$property || !$tenant) {
                $this->addFlash('error', 'Propriété ou locataire non trouvé.');
                return $this->render('lease/new.html.twig', [
                    'lease' => $lease,
                    'properties' => $propertyRepository->findAvailable(),
                    'tenants' => $tenantRepository->findAll(),
                ]);
            }

            // Vérifier si la propriété est disponible
            if ($property->getCurrentLease()) {
                $this->addFlash('error', 'Cette propriété est déjà louée.');
                return $this->render('lease/new.html.twig', [
                    'lease' => $lease,
                    'properties' => $propertyRepository->findAvailable(),
                    'tenants' => $tenantRepository->findAll(),
                ]);
            }

            $lease->setProperty($property);
            $lease->setTenant($tenant);
            $lease->setStartDate(new \DateTime($request->get('start_date')));
            
            if ($request->get('end_date')) {
                $lease->setEndDate(new \DateTime($request->get('end_date')));
            }
            
            $lease->setMonthlyRent($request->get('monthly_rent'));
            $lease->setCharges($request->get('charges'));
            $lease->setDeposit($request->get('deposit'));
            $lease->setRentDueDay((int)$request->get('rent_due_day', 1));
            $lease->setTerms($request->get('terms'));

            $entityManager->persist($lease);
            $entityManager->flush();

            $this->addFlash('success', 'Contrat de location créé avec succès !');
            return $this->redirectToRoute('app_lease_index');
        }

        return $this->render('lease/new.html.twig', [
            'lease' => $lease,
            'properties' => $propertyRepository->findAvailable(),
            'tenants' => $tenantRepository->findAll(),
        ]);
    }

    #[Route('/{id}', name: 'app_lease_show', methods: ['GET'])]
    public function show(Lease $lease): Response
    {
        return $this->render('lease/show.html.twig', [
            'lease' => $lease,
        ]);
    }

    #[Route('/{id}/edit', name: 'app_lease_edit', methods: ['GET', 'POST'])]
    public function edit(Request $request, Lease $lease, EntityManagerInterface $entityManager): Response
    {
        if ($request->isMethod('POST')) {
            $lease->setStartDate(new \DateTime($request->get('start_date')));
            
            if ($request->get('end_date')) {
                $lease->setEndDate(new \DateTime($request->get('end_date')));
            } else {
                $lease->setEndDate(null);
            }
            
            $lease->setMonthlyRent($request->get('monthly_rent'));
            $lease->setCharges($request->get('charges'));
            $lease->setDeposit($request->get('deposit'));
            $lease->setRentDueDay((int)$request->get('rent_due_day'));
            $lease->setStatus($request->get('status'));
            $lease->setTerms($request->get('terms'));
            $lease->setUpdatedAt(new \DateTime());

            $entityManager->flush();

            $this->addFlash('success', 'Contrat modifié avec succès !');
            return $this->redirectToRoute('app_lease_show', ['id' => $lease->getId()]);
        }

        return $this->render('lease/edit.html.twig', [
            'lease' => $lease,
        ]);
    }

    #[Route('/{id}/terminate', name: 'app_lease_terminate', methods: ['POST'])]
    public function terminate(Request $request, Lease $lease, EntityManagerInterface $entityManager): Response
    {
        $endDate = $request->get('end_date') ? new \DateTime($request->get('end_date')) : new \DateTime();
        
        $lease->setEndDate($endDate);
        $lease->setStatus('Terminé');
        $lease->setUpdatedAt(new \DateTime());

        $entityManager->flush();

        $this->addFlash('success', 'Contrat terminé avec succès !');
        return $this->redirectToRoute('app_lease_show', ['id' => $lease->getId()]);
    }
}