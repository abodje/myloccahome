<?php

namespace App\Controller\Admin;

use App\Entity\Organization;
use App\Form\OrganizationType;
use App\Repository\OrganizationRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/admin/organisations')]
#[IsGranted('ROLE_SUPER_ADMIN')]
class OrganizationController extends AbstractController
{
    #[Route('/', name: 'app_admin_organization_index', methods: ['GET'])]
    public function index(OrganizationRepository $organizationRepository): Response
    {
        $organizations = $organizationRepository->findAll();

        // Statistiques
        $stats = [];
        foreach ($organizations as $org) {
            $stats[$org->getId()] = [
                'companies' => $org->getCompanies()->count(),
                'users' => $org->getUsers()->count(),
                'properties' => $org->getProperties()->count(),
            ];
        }

        return $this->render('admin/organization/index.html.twig', [
            'organizations' => $organizations,
            'stats' => $stats,
        ]);
    }

    #[Route('/nouvelle', name: 'app_admin_organization_new', methods: ['GET', 'POST'])]
    public function new(Request $request, EntityManagerInterface $entityManager): Response
    {
        $organization = new Organization();
        $form = $this->createForm(OrganizationType::class, $organization);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $organization->setCreatedAt(new \DateTime());
            $entityManager->persist($organization);
            $entityManager->flush();

            $this->addFlash('success', 'L\'organisation a été créée avec succès.');

            return $this->redirectToRoute('app_admin_organization_show', ['id' => $organization->getId()]);
        }

        return $this->render('admin/organization/new.html.twig', [
            'organization' => $organization,
            'form' => $form,
        ]);
    }

    #[Route('/{id}', name: 'app_admin_organization_show', methods: ['GET'])]
    public function show(Organization $organization): Response
    {
        $stats = [
            'companies' => $organization->getCompanies()->count(),
            'users' => $organization->getUsers()->count(),
            'properties' => $organization->getProperties()->count(),
            'leases' => method_exists($organization, 'getLeases') ? $organization->getLeases()->count() : 0,
            'payments' => method_exists($organization, 'getPayments') ? $organization->getPayments()->count() : 0,
            'documents' => 0, // Documents not directly linked to organization
        ];

        return $this->render('admin/organization/show.html.twig', [
            'organization' => $organization,
            'stats' => $stats,
        ]);
    }

    #[Route('/{id}/modifier', name: 'app_admin_organization_edit', methods: ['GET', 'POST'])]
    public function edit(Request $request, Organization $organization, EntityManagerInterface $entityManager): Response
    {
        $form = $this->createForm(OrganizationType::class, $organization);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $organization->setUpdatedAt(new \DateTime());
            $entityManager->flush();

            $this->addFlash('success', 'L\'organisation a été modifiée avec succès.');

            return $this->redirectToRoute('app_admin_organization_show', ['id' => $organization->getId()]);
        }

        return $this->render('admin/organization/edit.html.twig', [
            'organization' => $organization,
            'form' => $form,
        ]);
    }

    #[Route('/{id}/supprimer', name: 'app_admin_organization_delete', methods: ['POST'])]
    public function delete(Request $request, Organization $organization, EntityManagerInterface $entityManager): Response
    {
        if ($this->isCsrfTokenValid('delete'.$organization->getId(), $request->request->get('_token'))) {
            try {
                $name = $organization->getName();
                $entityManager->remove($organization);
                $entityManager->flush();

                $this->addFlash('success', "L'organisation {$name} a été supprimée avec succès.");
            } catch (\Exception $e) {
                $this->addFlash('error', 'Impossible de supprimer cette organisation. Elle contient peut-être des données.');
            }
        }

        return $this->redirectToRoute('app_admin_organization_index');
    }

    #[Route('/{id}/activer', name: 'app_admin_organization_toggle_active', methods: ['POST'])]
    public function toggleActive(Request $request, Organization $organization, EntityManagerInterface $entityManager): Response
    {
        if ($this->isCsrfTokenValid('toggle'.$organization->getId(), $request->request->get('_token'))) {
            $organization->setIsActive(!$organization->isActive());
            $organization->setUpdatedAt(new \DateTime());
            $entityManager->flush();

            $status = $organization->isActive() ? 'activée' : 'désactivée';
            $this->addFlash('success', "L'organisation a été {$status} avec succès.");
        }

        return $this->redirectToRoute('app_admin_organization_show', ['id' => $organization->getId()]);
    }

    #[Route('/{id}/statistiques', name: 'app_admin_organization_stats', methods: ['GET'])]
    public function statistics(Organization $organization): Response
    {
        // Statistiques détaillées
        $stats = [
            'companies' => [
                'total' => $organization->getCompanies()->count(),
                'active' => $organization->getCompanies()->filter(fn($c) => $c->isActive())->count(),
            ],
            'users' => [
                'total' => $organization->getUsers()->count(),
                'admins' => $organization->getUsers()->filter(fn($u) => in_array('ROLE_ADMIN', $u->getRoles()))->count(),
                'managers' => $organization->getUsers()->filter(fn($u) => in_array('ROLE_MANAGER', $u->getRoles()))->count(),
            ],
            'properties' => [
                'total' => $organization->getProperties()->count(),
                'occupied' => $organization->getProperties()->filter(fn($p) => $p->getStatus() === 'Occupé')->count(),
            ],
            'leases' => [
                'total' => $organization->getLeases()->count(),
                'active' => $organization->getLeases()->filter(fn($l) => $l->getStatus() === 'Actif')->count(),
            ],
        ];

        return $this->render('admin/organization/statistics.html.twig', [
            'organization' => $organization,
            'stats' => $stats,
        ]);
    }
}

