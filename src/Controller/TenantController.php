<?php

namespace App\Controller;

use App\Entity\Tenant;
use App\Repository\TenantRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

#[Route('/tenants')]
class TenantController extends AbstractController
{
    #[Route('/', name: 'app_tenant_index', methods: ['GET'])]
    public function index(TenantRepository $tenantRepository, Request $request): Response
    {
        $search = $request->query->get('search', '');
        $status = $request->query->get('status', '');

        if ($search) {
            $tenants = $tenantRepository->findByNameOrEmail($search);
        } elseif ($status === 'active') {
            $tenants = $tenantRepository->findActive();
        } elseif ($status === 'former') {
            $tenants = $tenantRepository->findFormer();
        } else {
            $tenants = $tenantRepository->findAllWithCurrentLeases();
        }

        return $this->render('tenant/index.html.twig', [
            'tenants' => $tenants,
            'search' => $search,
            'status' => $status,
        ]);
    }

    #[Route('/new', name: 'app_tenant_new', methods: ['GET', 'POST'])]
    public function new(Request $request, EntityManagerInterface $entityManager, TenantRepository $tenantRepository): Response
    {
        $tenant = new Tenant();

        if ($request->isMethod('POST')) {
            $email = $request->get('email');
            
            // Vérifier si l'email existe déjà
            if ($tenantRepository->isEmailExists($email)) {
                $this->addFlash('error', 'Cette adresse email est déjà utilisée par un autre locataire.');
                return $this->render('tenant/new.html.twig', ['tenant' => $tenant]);
            }

            $tenant->setFirstName($request->get('first_name'));
            $tenant->setLastName($request->get('last_name'));
            $tenant->setEmail($email);
            $tenant->setPhone($request->get('phone'));
            
            if ($request->get('birth_date')) {
                $tenant->setBirthDate(new \DateTime($request->get('birth_date')));
            }
            
            $tenant->setAddress($request->get('address'));
            $tenant->setCity($request->get('city'));
            $tenant->setPostalCode($request->get('postal_code'));
            $tenant->setProfession($request->get('profession'));
            $tenant->setMonthlyIncome($request->get('monthly_income'));
            $tenant->setEmergencyContactName($request->get('emergency_contact_name'));
            $tenant->setEmergencyContactPhone($request->get('emergency_contact_phone'));
            $tenant->setNotes($request->get('notes'));

            $entityManager->persist($tenant);
            $entityManager->flush();

            $this->addFlash('success', 'Locataire créé avec succès !');
            return $this->redirectToRoute('app_tenant_index');
        }

        return $this->render('tenant/new.html.twig', [
            'tenant' => $tenant,
        ]);
    }

    #[Route('/{id}', name: 'app_tenant_show', methods: ['GET'])]
    public function show(Tenant $tenant): Response
    {
        $currentLease = $tenant->getCurrentLease();
        
        return $this->render('tenant/show.html.twig', [
            'tenant' => $tenant,
            'currentLease' => $currentLease,
        ]);
    }

    #[Route('/{id}/edit', name: 'app_tenant_edit', methods: ['GET', 'POST'])]
    public function edit(Request $request, Tenant $tenant, EntityManagerInterface $entityManager, TenantRepository $tenantRepository): Response
    {
        if ($request->isMethod('POST')) {
            $email = $request->get('email');
            
            // Vérifier si l'email existe déjà (sauf pour ce locataire)
            if ($tenantRepository->isEmailExists($email, $tenant->getId())) {
                $this->addFlash('error', 'Cette adresse email est déjà utilisée par un autre locataire.');
                return $this->render('tenant/edit.html.twig', ['tenant' => $tenant]);
            }

            $tenant->setFirstName($request->get('first_name'));
            $tenant->setLastName($request->get('last_name'));
            $tenant->setEmail($email);
            $tenant->setPhone($request->get('phone'));
            
            if ($request->get('birth_date')) {
                $tenant->setBirthDate(new \DateTime($request->get('birth_date')));
            } else {
                $tenant->setBirthDate(null);
            }
            
            $tenant->setAddress($request->get('address'));
            $tenant->setCity($request->get('city'));
            $tenant->setPostalCode($request->get('postal_code'));
            $tenant->setProfession($request->get('profession'));
            $tenant->setMonthlyIncome($request->get('monthly_income'));
            $tenant->setEmergencyContactName($request->get('emergency_contact_name'));
            $tenant->setEmergencyContactPhone($request->get('emergency_contact_phone'));
            $tenant->setNotes($request->get('notes'));
            $tenant->setUpdatedAt(new \DateTime());

            $entityManager->flush();

            $this->addFlash('success', 'Locataire modifié avec succès !');
            return $this->redirectToRoute('app_tenant_show', ['id' => $tenant->getId()]);
        }

        return $this->render('tenant/edit.html.twig', [
            'tenant' => $tenant,
        ]);
    }

    #[Route('/{id}/delete', name: 'app_tenant_delete', methods: ['POST'])]
    public function delete(Request $request, Tenant $tenant, EntityManagerInterface $entityManager): Response
    {
        if ($this->isCsrfTokenValid('delete'.$tenant->getId(), $request->request->get('_token'))) {
            $entityManager->remove($tenant);
            $entityManager->flush();
            $this->addFlash('success', 'Locataire supprimé avec succès !');
        }

        return $this->redirectToRoute('app_tenant_index');
    }
}