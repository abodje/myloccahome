<?php

namespace App\Controller;

use App\Entity\Tenant;
use App\Form\TenantType;
use App\Repository\TenantRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/tenants')]
class TenantController extends AbstractController
{
    #[Route('/', name: 'app_tenant_index', methods: ['GET'])]
    public function index(Request $request, TenantRepository $tenantRepository): Response
    {
        $search = $request->query->get('search');
        $status = $request->query->get('status');

        if ($search) {
            $tenants = $tenantRepository->searchTenants($search);
        } else {
            $tenants = $tenantRepository->findAll();
        }

        if ($status) {
            $tenants = array_filter($tenants, fn($tenant) => $tenant->getStatus() === $status);
        }

        return $this->render('tenant/index.html.twig', [
            'tenants' => $tenants,
            'search' => $search,
            'status' => $status,
        ]);
    }

    #[Route('/new', name: 'app_tenant_new', methods: ['GET', 'POST'])]
    public function new(Request $request, EntityManagerInterface $entityManager): Response
    {
        $tenant = new Tenant();
        $form = $this->createForm(TenantType::class, $tenant);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->persist($tenant);
            $entityManager->flush();

            $this->addFlash('success', 'Le locataire a été créé avec succès.');

            return $this->redirectToRoute('app_tenant_index', [], Response::HTTP_SEE_OTHER);
        }

        return $this->render('tenant/new.html.twig', [
            'tenant' => $tenant,
            'form' => $form,
        ]);
    }

    #[Route('/{id}', name: 'app_tenant_show', methods: ['GET'])]
    public function show(Tenant $tenant): Response
    {
        $currentContract = $tenant->getCurrentContract();
        $contracts = $tenant->getRentalContracts()->toArray();
        
        // Trier les contrats par date de début (plus récents en premier)
        usort($contracts, fn($a, $b) => $b->getStartDate() <=> $a->getStartDate());

        return $this->render('tenant/show.html.twig', [
            'tenant' => $tenant,
            'currentContract' => $currentContract,
            'contracts' => $contracts,
        ]);
    }

    #[Route('/{id}/edit', name: 'app_tenant_edit', methods: ['GET', 'POST'])]
    public function edit(Request $request, Tenant $tenant, EntityManagerInterface $entityManager): Response
    {
        $form = $this->createForm(TenantType::class, $tenant);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $tenant->setUpdatedAt(new \DateTime());
            $entityManager->flush();

            $this->addFlash('success', 'Le locataire a été modifié avec succès.');

            return $this->redirectToRoute('app_tenant_index', [], Response::HTTP_SEE_OTHER);
        }

        return $this->render('tenant/edit.html.twig', [
            'tenant' => $tenant,
            'form' => $form,
        ]);
    }

    #[Route('/{id}', name: 'app_tenant_delete', methods: ['POST'])]
    public function delete(Request $request, Tenant $tenant, EntityManagerInterface $entityManager): Response
    {
        if ($this->isCsrfTokenValid('delete'.$tenant->getId(), $request->getPayload()->getString('_token'))) {
            // Vérifier qu'il n'y a pas de contrat actif
            if ($tenant->getCurrentContract()) {
                $this->addFlash('error', 'Impossible de supprimer un locataire avec un contrat actif.');
            } else {
                $entityManager->remove($tenant);
                $entityManager->flush();
                $this->addFlash('success', 'Le locataire a été supprimé avec succès.');
            }
        }

        return $this->redirectToRoute('app_tenant_index', [], Response::HTTP_SEE_OTHER);
    }

    #[Route('/active', name: 'app_tenant_active', methods: ['GET'])]
    public function active(TenantRepository $tenantRepository): Response
    {
        $tenants = $tenantRepository->findActive();

        return $this->render('tenant/active.html.twig', [
            'tenants' => $tenants,
        ]);
    }

    #[Route('/with-contracts', name: 'app_tenant_with_contracts', methods: ['GET'])]
    public function withContracts(TenantRepository $tenantRepository): Response
    {
        $tenants = $tenantRepository->findWithActiveContracts();

        return $this->render('tenant/with_contracts.html.twig', [
            'tenants' => $tenants,
        ]);
    }

    #[Route('/{id}/change-status', name: 'app_tenant_change_status', methods: ['POST'])]
    public function changeStatus(Request $request, Tenant $tenant, EntityManagerInterface $entityManager): Response
    {
        $newStatus = $request->request->get('status');
        
        if (in_array($newStatus, ['active', 'inactive', 'blacklisted'])) {
            $tenant->setStatus($newStatus);
            $tenant->setUpdatedAt(new \DateTime());
            $entityManager->flush();

            $this->addFlash('success', 'Le statut du locataire a été mis à jour.');
        } else {
            $this->addFlash('error', 'Statut invalide.');
        }

        return $this->redirectToRoute('app_tenant_show', ['id' => $tenant->getId()]);
    }
}