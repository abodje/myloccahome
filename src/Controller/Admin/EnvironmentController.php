<?php

namespace App\Controller\Admin;

use App\Entity\Environment;
use App\Form\EnvironmentType;
use App\Repository\EnvironmentRepository;
use App\Service\EnvironmentManagementService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/admin/environments', name: 'app_admin_environment_')]
#[IsGranted('ROLE_ADMIN')]
class EnvironmentController extends AbstractController
{
    #[Route('/', name: 'index', methods: ['GET'])]
    public function index(EnvironmentRepository $environmentRepository): Response
    {
        $environments = $environmentRepository->findAll();
        $stats = $environmentRepository->getStatistics();

        return $this->render('admin/environment/index.html.twig', [
            'environments' => $environments,
            'stats' => $stats,
        ]);
    }

    #[Route('/new', name: 'new', methods: ['GET', 'POST'])]
    public function new(
        Request $request,
        EntityManagerInterface $entityManager,
        EnvironmentManagementService $environmentService
    ): Response {
        $environment = new Environment();
        $form = $this->createForm(EnvironmentType::class, $environment);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            // Récupérer l'organisation de l'utilisateur connecté
            $user = $this->getUser();
            $organization = $user->getOrganization();

            if (!$organization) {
                $this->addFlash('error', 'Vous devez être associé à une organisation pour créer un environnement.');
                return $this->redirectToRoute('app_admin_environment_new');
            }

            // Créer l'environnement via le service
            $result = $environmentService->createEnvironment(
                $organization,
                $environment->getName(),
                $environment->getType(),
                $environment->getDescription(),
                $environment->getDomain(),
                $environment->getConfiguration() ?? []
            );

            if ($result['success']) {
                $this->addFlash('success', $result['message']);
                return $this->redirectToRoute('app_admin_environment_show', ['id' => $result['environment']->getId()]);
            } else {
                $this->addFlash('error', $result['message']);
            }
        }

        return $this->render('admin/environment/new.html.twig', [
            'environment' => $environment,
            'form' => $form,
        ]);
    }

    #[Route('/{id}', name: 'show', methods: ['GET'])]
    public function show(Environment $environment): Response
    {
        return $this->render('admin/environment/show.html.twig', [
            'environment' => $environment,
        ]);
    }

    #[Route('/{id}/edit', name: 'edit', methods: ['GET', 'POST'])]
    public function edit(
        Request $request,
        Environment $environment,
        EntityManagerInterface $entityManager
    ): Response {
        $form = $this->createForm(EnvironmentType::class, $environment);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $environment->setUpdatedAt(new \DateTime());
            $entityManager->flush();

            $this->addFlash('success', 'Environnement modifié avec succès.');
            return $this->redirectToRoute('app_admin_environment_show', ['id' => $environment->getId()]);
        }

        return $this->render('admin/environment/edit.html.twig', [
            'environment' => $environment,
            'form' => $form,
        ]);
    }

    #[Route('/{id}/deploy', name: 'deploy', methods: ['POST'])]
    public function deploy(
        Environment $environment,
        EnvironmentManagementService $environmentService,
        Request $request
    ): Response {
        $version = $request->request->get('version', '1.0.0');

        $result = $environmentService->deployEnvironment($environment, $version);

        if ($result['success']) {
            $this->addFlash('success', $result['message']);
        } else {
            $this->addFlash('error', $result['message']);
        }

        return $this->redirectToRoute('app_admin_environment_show', ['id' => $environment->getId()]);
    }

    #[Route('/{id}/delete', name: 'delete', methods: ['POST'])]
    public function delete(
        Environment $environment,
        EnvironmentManagementService $environmentService
    ): Response {
        $result = $environmentService->deleteEnvironment($environment);

        if ($result['success']) {
            $this->addFlash('success', $result['message']);
        } else {
            $this->addFlash('error', $result['message']);
        }

        return $this->redirectToRoute('app_admin_environment_index');
    }

    #[Route('/{id}/toggle-status', name: 'toggle_status', methods: ['POST'])]
    public function toggleStatus(
        Environment $environment,
        EntityManagerInterface $entityManager
    ): Response {
        $newStatus = $environment->getStatus() === 'ACTIVE' ? 'INACTIVE' : 'ACTIVE';
        $environment->setStatus($newStatus);
        $environment->setUpdatedAt(new \DateTime());

        $entityManager->flush();

        $this->addFlash('success', 'Statut de l\'environnement modifié.');
        return $this->redirectToRoute('app_admin_environment_show', ['id' => $environment->getId()]);
    }

    #[Route('/{id}/logs', name: 'logs', methods: ['GET'])]
    public function logs(Environment $environment): Response
    {
        return $this->render('admin/environment/logs.html.twig', [
            'environment' => $environment,
        ]);
    }
}
