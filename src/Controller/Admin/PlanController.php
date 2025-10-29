<?php

namespace App\Controller\Admin;

use App\Entity\Plan;
use App\Form\PlanType;
use App\Repository\PlanRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/admin/plans', name: 'app_admin_plan_')]
#[IsGranted('ROLE_ADMIN')]
class PlanController extends AbstractController
{
    public function __construct(
        private EntityManagerInterface $entityManager,
        private PlanRepository $planRepository
    ) {
    }

    #[Route('/', name: 'index', methods: ['GET'])]
    public function index(): Response
    {
        $plans = $this->planRepository->findBy([], ['sortOrder' => 'ASC']);

        return $this->render('admin/plan/index.html.twig', [
            'plans' => $plans,
        ]);
    }

    #[Route('/new', name: 'new', methods: ['GET', 'POST'])]
    public function new(Request $request): Response
    {
        $plan = new Plan();
        $form = $this->createForm(PlanType::class, $plan);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $this->entityManager->persist($plan);
            $this->entityManager->flush();

            $this->addFlash('success', 'Plan créé avec succès.');
            return $this->redirectToRoute('app_admin_plan_index');
        }

        return $this->render('admin/plan/new.html.twig', [
            'plan' => $plan,
            'form' => $form,
        ]);
    }

    #[Route('/{id}', name: 'show', methods: ['GET'], requirements: ['id' => '\d+'])]
    public function show(Plan $plan): Response
    {
        return $this->render('admin/plan/show.html.twig', [
            'plan' => $plan,
        ]);
    }

    #[Route('/{id}/edit', name: 'edit', methods: ['GET', 'POST'], requirements: ['id' => '\d+'])]
    public function edit(Request $request, Plan $plan): Response
    {
        $form = $this->createForm(PlanType::class, $plan);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $this->entityManager->flush();

            $this->addFlash('success', 'Plan modifié avec succès.');
            return $this->redirectToRoute('app_admin_plan_index');
        }

        return $this->render('admin/plan/edit.html.twig', [
            'plan' => $plan,
            'form' => $form,
        ]);
    }

    #[Route('/{id}/delete', name: 'delete', methods: ['POST'], requirements: ['id' => '\d+'])]
    public function delete(Request $request, Plan $plan): Response
    {
        if ($this->isCsrfTokenValid('delete' . $plan->getId(), $request->request->get('_token'))) {
            // Vérifier s'il y a des abonnements actifs pour ce plan
            $activeSubscriptions = $this->entityManager->getRepository(\App\Entity\Subscription::class)
                ->count(['plan' => $plan, 'status' => 'ACTIVE']);

            if ($activeSubscriptions > 0) {
                $this->addFlash('error', 'Impossible de supprimer ce plan car il a des abonnements actifs.');
                return $this->redirectToRoute('app_admin_plan_index');
            }

            $this->entityManager->remove($plan);
            $this->entityManager->flush();

            $this->addFlash('success', 'Plan supprimé avec succès.');
        }

        return $this->redirectToRoute('app_admin_plan_index');
    }

    #[Route('/{id}/toggle-popular', name: 'toggle_popular', methods: ['POST'], requirements: ['id' => '\d+'])]
    public function togglePopular(Plan $plan): Response
    {
        $plan->setIsPopular(!$plan->isPopular());
        $this->entityManager->flush();

        $status = $plan->isPopular() ? 'marqué comme populaire' : 'retiré des plans populaires';
        $this->addFlash('success', "Plan {$status} avec succès.");

        return $this->redirectToRoute('app_admin_plan_index');
    }
}
