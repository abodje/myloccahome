<?php

namespace App\Controller;

use App\Entity\Tenant;
use App\Repository\TenantRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/tenant')]
class TenantController extends AbstractController
{
    #[Route('/', name: 'app_tenant_index', methods: ['GET'])]
    public function index(TenantRepository $tenantRepository, Request $request): Response
    {
        $search = $request->query->get('search');
        $active = $request->query->get('active');

        if ($search) {
            $tenants = $tenantRepository->searchTenants($search);
        } elseif ($active === '1') {
            $tenants = $tenantRepository->findActiveTenants();
        } else {
            $tenants = $tenantRepository->findAll();
        }

        return $this->render('tenant/index.html.twig', [
            'tenants' => $tenants,
            'search' => $search,
            'active' => $active,
        ]);
    }

    #[Route('/new', name: 'app_tenant_new', methods: ['GET', 'POST'])]
    public function new(Request $request, EntityManagerInterface $entityManager): Response
    {
        $tenant = new Tenant();

        if ($request->isMethod('POST')) {
            $tenant->setFirstName($request->request->get('first_name'));
            $tenant->setLastName($request->request->get('last_name'));
            $tenant->setEmail($request->request->get('email'));
            $tenant->setPhone($request->request->get('phone'));
            
            if ($request->request->get('birth_date')) {
                $tenant->setBirthDate(new \DateTime($request->request->get('birth_date')));
            }
            
            $tenant->setAddress($request->request->get('address'));
            $tenant->setCity($request->request->get('city'));
            $tenant->setPostalCode($request->request->get('postal_code'));
            $tenant->setCountry($request->request->get('country'));
            $tenant->setProfession($request->request->get('profession'));
            
            if ($request->request->get('monthly_income')) {
                $tenant->setMonthlyIncome($request->request->get('monthly_income'));
            }
            
            $tenant->setEmergencyContactName($request->request->get('emergency_contact_name'));
            $tenant->setEmergencyContactPhone($request->request->get('emergency_contact_phone'));
            $tenant->setNotes($request->request->get('notes'));

            try {
                $entityManager->persist($tenant);
                $entityManager->flush();

                $this->addFlash('success', 'Locataire créé avec succès !');
                return $this->redirectToRoute('app_tenant_index');
            } catch (\Exception $e) {
                $this->addFlash('error', 'Erreur lors de la création du locataire. L\'email est peut-être déjà utilisé.');
            }
        }

        return $this->render('tenant/new.html.twig', [
            'tenant' => $tenant,
        ]);
    }

    #[Route('/{id}', name: 'app_tenant_show', methods: ['GET'])]
    public function show(Tenant $tenant): Response
    {
        return $this->render('tenant/show.html.twig', [
            'tenant' => $tenant,
        ]);
    }

    #[Route('/{id}/edit', name: 'app_tenant_edit', methods: ['GET', 'POST'])]
    public function edit(Request $request, Tenant $tenant, EntityManagerInterface $entityManager): Response
    {
        if ($request->isMethod('POST')) {
            $tenant->setFirstName($request->request->get('first_name'));
            $tenant->setLastName($request->request->get('last_name'));
            $tenant->setEmail($request->request->get('email'));
            $tenant->setPhone($request->request->get('phone'));
            
            if ($request->request->get('birth_date')) {
                $tenant->setBirthDate(new \DateTime($request->request->get('birth_date')));
            } else {
                $tenant->setBirthDate(null);
            }
            
            $tenant->setAddress($request->request->get('address'));
            $tenant->setCity($request->request->get('city'));
            $tenant->setPostalCode($request->request->get('postal_code'));
            $tenant->setCountry($request->request->get('country'));
            $tenant->setProfession($request->request->get('profession'));
            
            if ($request->request->get('monthly_income')) {
                $tenant->setMonthlyIncome($request->request->get('monthly_income'));
            } else {
                $tenant->setMonthlyIncome(null);
            }
            
            $tenant->setEmergencyContactName($request->request->get('emergency_contact_name'));
            $tenant->setEmergencyContactPhone($request->request->get('emergency_contact_phone'));
            $tenant->setNotes($request->request->get('notes'));
            $tenant->setUpdatedAt(new \DateTime());

            try {
                $entityManager->flush();

                $this->addFlash('success', 'Locataire modifié avec succès !');
                return $this->redirectToRoute('app_tenant_show', ['id' => $tenant->getId()]);
            } catch (\Exception $e) {
                $this->addFlash('error', 'Erreur lors de la modification du locataire. L\'email est peut-être déjà utilisé.');
            }
        }

        return $this->render('tenant/edit.html.twig', [
            'tenant' => $tenant,
        ]);
    }

    #[Route('/{id}', name: 'app_tenant_delete', methods: ['POST'])]
    public function delete(Request $request, Tenant $tenant, EntityManagerInterface $entityManager): Response
    {
        if ($this->isCsrfTokenValid('delete'.$tenant->getId(), $request->request->get('_token'))) {
            try {
                $entityManager->remove($tenant);
                $entityManager->flush();
                $this->addFlash('success', 'Locataire supprimé avec succès !');
            } catch (\Exception $e) {
                $this->addFlash('error', 'Impossible de supprimer ce locataire car il a des contrats associés.');
            }
        }

        return $this->redirectToRoute('app_tenant_index');
    }
}