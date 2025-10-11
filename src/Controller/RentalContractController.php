<?php

namespace App\Controller;

use App\Entity\RentalContract;
use App\Repository\RentalContractRepository;
use App\Repository\PropertyRepository;
use App\Repository\TenantRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/rental-contract')]
class RentalContractController extends AbstractController
{
    #[Route('/', name: 'app_rental_contract_index', methods: ['GET'])]
    public function index(RentalContractRepository $contractRepository, Request $request): Response
    {
        $status = $request->query->get('status');
        $property = $request->query->get('property');
        $tenant = $request->query->get('tenant');

        if ($status === 'active') {
            $contracts = $contractRepository->findActiveContracts();
        } elseif ($status === 'ending_soon') {
            $contracts = $contractRepository->findContractsEndingSoon(30);
        } elseif ($property) {
            $contracts = $contractRepository->findByProperty($property);
        } elseif ($tenant) {
            $contracts = $contractRepository->findByTenant($tenant);
        } else {
            $contracts = $contractRepository->findBy([], ['createdAt' => 'DESC']);
        }

        return $this->render('rental_contract/index.html.twig', [
            'contracts' => $contracts,
            'status' => $status,
            'property' => $property,
            'tenant' => $tenant,
        ]);
    }

    #[Route('/new', name: 'app_rental_contract_new', methods: ['GET', 'POST'])]
    public function new(
        Request $request, 
        EntityManagerInterface $entityManager,
        PropertyRepository $propertyRepository,
        TenantRepository $tenantRepository
    ): Response {
        $contract = new RentalContract();
        $properties = $propertyRepository->findAvailableProperties();
        $tenants = $tenantRepository->findAll();

        // Pré-sélection si passé en paramètre
        $preselectedProperty = $request->query->get('property');
        $preselectedTenant = $request->query->get('tenant');

        if ($request->isMethod('POST')) {
            $property = $propertyRepository->find($request->request->get('property_id'));
            $tenant = $tenantRepository->find($request->request->get('tenant_id'));

            if (!$property || !$tenant) {
                $this->addFlash('error', 'Propriété ou locataire non trouvé.');
                return $this->render('rental_contract/new.html.twig', [
                    'contract' => $contract,
                    'properties' => $properties,
                    'tenants' => $tenants,
                    'preselectedProperty' => $preselectedProperty,
                    'preselectedTenant' => $preselectedTenant,
                ]);
            }

            $contract->setProperty($property);
            $contract->setTenant($tenant);
            $contract->setStartDate(new \DateTime($request->request->get('start_date')));
            
            if ($request->request->get('end_date')) {
                $contract->setEndDate(new \DateTime($request->request->get('end_date')));
            }
            
            $contract->setMonthlyRent($request->request->get('monthly_rent'));
            $contract->setCharges($request->request->get('charges'));
            $contract->setDeposit($request->request->get('deposit'));
            $contract->setStatus($request->request->get('status'));
            $contract->setConditions($request->request->get('conditions'));
            $contract->setNotes($request->request->get('notes'));

            try {
                $entityManager->persist($contract);
                
                // Marquer la propriété comme occupée si le contrat est actif
                if ($contract->getStatus() === 'active') {
                    $property->setAvailable(false);
                }
                
                $entityManager->flush();

                $this->addFlash('success', 'Contrat de location créé avec succès !');
                return $this->redirectToRoute('app_rental_contract_show', ['id' => $contract->getId()]);
            } catch (\Exception $e) {
                $this->addFlash('error', 'Erreur lors de la création du contrat.');
            }
        }

        return $this->render('rental_contract/new.html.twig', [
            'contract' => $contract,
            'properties' => $properties,
            'tenants' => $tenants,
            'preselectedProperty' => $preselectedProperty,
            'preselectedTenant' => $preselectedTenant,
        ]);
    }

    #[Route('/{id}', name: 'app_rental_contract_show', methods: ['GET'])]
    public function show(RentalContract $contract): Response
    {
        return $this->render('rental_contract/show.html.twig', [
            'contract' => $contract,
        ]);
    }

    #[Route('/{id}/edit', name: 'app_rental_contract_edit', methods: ['GET', 'POST'])]
    public function edit(Request $request, RentalContract $contract, EntityManagerInterface $entityManager): Response
    {
        $oldStatus = $contract->getStatus();

        if ($request->isMethod('POST')) {
            $contract->setStartDate(new \DateTime($request->request->get('start_date')));
            
            if ($request->request->get('end_date')) {
                $contract->setEndDate(new \DateTime($request->request->get('end_date')));
            } else {
                $contract->setEndDate(null);
            }
            
            $contract->setMonthlyRent($request->request->get('monthly_rent'));
            $contract->setCharges($request->request->get('charges'));
            $contract->setDeposit($request->request->get('deposit'));
            $contract->setStatus($request->request->get('status'));
            $contract->setConditions($request->request->get('conditions'));
            $contract->setNotes($request->request->get('notes'));
            $contract->setUpdatedAt(new \DateTime());

            // Gérer la disponibilité de la propriété selon le statut
            $property = $contract->getProperty();
            if ($contract->getStatus() === 'active' && $oldStatus !== 'active') {
                $property->setAvailable(false);
            } elseif ($contract->getStatus() !== 'active' && $oldStatus === 'active') {
                // Vérifier s'il n'y a pas d'autres contrats actifs pour cette propriété
                $activeContracts = $property->getRentalContracts()->filter(
                    fn($c) => $c->getStatus() === 'active' && $c->getId() !== $contract->getId()
                );
                if ($activeContracts->isEmpty()) {
                    $property->setAvailable(true);
                }
            }

            try {
                $entityManager->flush();

                $this->addFlash('success', 'Contrat modifié avec succès !');
                return $this->redirectToRoute('app_rental_contract_show', ['id' => $contract->getId()]);
            } catch (\Exception $e) {
                $this->addFlash('error', 'Erreur lors de la modification du contrat.');
            }
        }

        return $this->render('rental_contract/edit.html.twig', [
            'contract' => $contract,
        ]);
    }

    #[Route('/{id}', name: 'app_rental_contract_delete', methods: ['POST'])]
    public function delete(Request $request, RentalContract $contract, EntityManagerInterface $entityManager): Response
    {
        if ($this->isCsrfTokenValid('delete'.$contract->getId(), $request->request->get('_token'))) {
            try {
                $property = $contract->getProperty();
                
                $entityManager->remove($contract);
                
                // Rendre la propriété disponible si c'était le seul contrat actif
                if ($contract->getStatus() === 'active') {
                    $activeContracts = $property->getRentalContracts()->filter(
                        fn($c) => $c->getStatus() === 'active' && $c->getId() !== $contract->getId()
                    );
                    if ($activeContracts->isEmpty()) {
                        $property->setAvailable(true);
                    }
                }
                
                $entityManager->flush();
                
                $this->addFlash('success', 'Contrat supprimé avec succès !');
            } catch (\Exception $e) {
                $this->addFlash('error', 'Impossible de supprimer ce contrat car il a des paiements associés.');
            }
        }

        return $this->redirectToRoute('app_rental_contract_index');
    }

    #[Route('/{id}/terminate', name: 'app_rental_contract_terminate', methods: ['POST'])]
    public function terminate(Request $request, RentalContract $contract, EntityManagerInterface $entityManager): Response
    {
        if ($this->isCsrfTokenValid('terminate'.$contract->getId(), $request->request->get('_token'))) {
            $endDate = $request->request->get('end_date') ? new \DateTime($request->request->get('end_date')) : new \DateTime();
            
            $contract->setEndDate($endDate);
            $contract->setStatus('terminated');
            $contract->setUpdatedAt(new \DateTime());

            // Rendre la propriété disponible
            $property = $contract->getProperty();
            $activeContracts = $property->getRentalContracts()->filter(
                fn($c) => $c->getStatus() === 'active' && $c->getId() !== $contract->getId()
            );
            if ($activeContracts->isEmpty()) {
                $property->setAvailable(true);
            }

            $entityManager->flush();

            $this->addFlash('success', 'Contrat terminé avec succès !');
        }

        return $this->redirectToRoute('app_rental_contract_show', ['id' => $contract->getId()]);
    }
}