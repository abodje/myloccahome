<?php

namespace App\Controller\Admin;

use App\Entity\Company;
use App\Form\CompanyType;
use App\Repository\CompanyRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/admin/societes')]
#[IsGranted('ROLE_SUPER_ADMIN')]
class CompanyController extends AbstractController
{
    #[Route('/', name: 'app_admin_company_index', methods: ['GET'])]
    public function index(CompanyRepository $companyRepository): Response
    {
        $companies = $companyRepository->findAll();

        // Statistiques
        $stats = [];
        foreach ($companies as $company) {
            $stats[$company->getId()] = [
                'users' => $company->getUsers()->count(),
                'properties' => $company->getProperties()->count(),
            ];
        }

        return $this->render('admin/company/index.html.twig', [
            'companies' => $companies,
            'stats' => $stats,
        ]);
    }

    #[Route('/nouvelle', name: 'app_admin_company_new', methods: ['GET', 'POST'])]
    public function new(Request $request, EntityManagerInterface $entityManager): Response
    {
        $company = new Company();
        $form = $this->createForm(CompanyType::class, $company);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $company->setCreatedAt(new \DateTime());
            $entityManager->persist($company);
            $entityManager->flush();

            $this->addFlash('success', 'La société a été créée avec succès.');

            return $this->redirectToRoute('app_admin_company_show', ['id' => $company->getId()]);
        }

        return $this->render('admin/company/new.html.twig', [
            'company' => $company,
            'form' => $form,
        ]);
    }

    #[Route('/{id}', name: 'app_admin_company_show', methods: ['GET'])]
    public function show(Company $company): Response
    {
        $stats = [
            'users' => $company->getUsers()->count(),
            'properties' => $company->getProperties()->count(),
            'leases' => method_exists($company, 'getLeases') ? $company->getLeases()->count() : 0,
            'payments' => method_exists($company, 'getPayments') ? $company->getPayments()->count() : 0,
            'documents' => 0, // Documents not directly linked to company
        ];

        return $this->render('admin/company/show.html.twig', [
            'company' => $company,
            'stats' => $stats,
        ]);
    }

    #[Route('/{id}/modifier', name: 'app_admin_company_edit', methods: ['GET', 'POST'])]
    public function edit(Request $request, Company $company, EntityManagerInterface $entityManager): Response
    {
        $form = $this->createForm(CompanyType::class, $company);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $company->setUpdatedAt(new \DateTime());
            $entityManager->flush();

            $this->addFlash('success', 'La société a été modifiée avec succès.');

            return $this->redirectToRoute('app_admin_company_show', ['id' => $company->getId()]);
        }

        return $this->render('admin/company/edit.html.twig', [
            'company' => $company,
            'form' => $form,
        ]);
    }

    #[Route('/{id}/supprimer', name: 'app_admin_company_delete', methods: ['POST'])]
    public function delete(Request $request, Company $company, EntityManagerInterface $entityManager): Response
    {
        if ($this->isCsrfTokenValid('delete'.$company->getId(), $request->request->get('_token'))) {
            try {
                $name = $company->getName();
                $entityManager->remove($company);
                $entityManager->flush();

                $this->addFlash('success', "La société {$name} a été supprimée avec succès.");
            } catch (\Exception $e) {
                $this->addFlash('error', 'Impossible de supprimer cette société. Elle contient peut-être des données.');
            }
        }

        return $this->redirectToRoute('app_admin_company_index');
    }

    #[Route('/{id}/activer', name: 'app_admin_company_toggle_active', methods: ['POST'])]
    public function toggleActive(Request $request, Company $company, EntityManagerInterface $entityManager): Response
    {
        if ($this->isCsrfTokenValid('toggle'.$company->getId(), $request->request->get('_token'))) {
            $company->setIsActive(!$company->isActive());
            $company->setUpdatedAt(new \DateTime());
            $entityManager->flush();

            $status = $company->isActive() ? 'activée' : 'désactivée';
            $this->addFlash('success', "La société a été {$status} avec succès.");
        }

        return $this->redirectToRoute('app_admin_company_show', ['id' => $company->getId()]);
    }

    #[Route('/{id}/statistiques', name: 'app_admin_company_stats', methods: ['GET'])]
    public function statistics(Company $company): Response
    {
        // Statistiques détaillées
        $stats = [
            'users' => [
                'total' => $company->getUsers()->count(),
                'admins' => $company->getUsers()->filter(fn($u) => in_array('ROLE_ADMIN', $u->getRoles()))->count(),
                'managers' => $company->getUsers()->filter(fn($u) => in_array('ROLE_MANAGER', $u->getRoles()))->count(),
            ],
            'properties' => [
                'total' => $company->getProperties()->count(),
                'occupied' => $company->getProperties()->filter(fn($p) => $p->getStatus() === 'Occupé')->count(),
            ],
            'leases' => [
                'total' => $company->getLeases()->count(),
                'active' => $company->getLeases()->filter(fn($l) => $l->getStatus() === 'Actif')->count(),
            ],
        ];

        return $this->render('admin/company/statistics.html.twig', [
            'company' => $company,
            'stats' => $stats,
        ]);
    }
}

