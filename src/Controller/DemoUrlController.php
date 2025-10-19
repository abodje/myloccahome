<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use App\Service\DemoEnvironmentService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\RedirectResponse;

/**
 * Contrôleur pour gérer les URLs de démo avec paramètres
 * Format: votre-domaine.com/demo/{demoCode}
 */
#[Route('/demo')]
class DemoUrlController extends AbstractController
{
    private DemoEnvironmentService $demoService;
    private EntityManagerInterface $entityManager;

    public function __construct(DemoEnvironmentService $demoService, EntityManagerInterface $entityManager)
    {
        $this->demoService = $demoService;
        $this->entityManager = $entityManager;
    }

    /**
     * Route principale pour accéder aux démos via URL avec paramètre
     */
    #[Route('/{demoCode}', name: 'demo_access', methods: ['GET'], requirements: ['demoCode' => '[a-zA-Z0-9]{8,}'])]
    public function accessDemo(string $demoCode, Request $request): Response
    {
        try {
            // Vérifier si la démo existe et est active
            $demo = $this->demoService->getDemoByCode($demoCode);

            if (!$demo) {
                throw new \Exception('Démo introuvable ou expirée');
            }

            // Vérifier si la démo est encore active
            if (!$this->demoService->isDemoActive($demo)) {
                throw new \Exception('Cette démo a expiré');
            }

            // Connexion automatique à l'environnement de démo
            $user = $demo['user'];
            $this->demoService->loginToDemoEnvironment($user, $demoCode);

            // Rediriger vers le dashboard de la démo
            return $this->redirectToRoute('app_dashboard');

        } catch (\Exception $e) {
            // Afficher une page d'erreur pour la démo
            return $this->render('demo/error.html.twig', [
                'demoCode' => $demoCode,
                'error' => $e->getMessage()
            ]);
        }
    }

    /**
     * Page d'information sur une démo (accessible sans connexion)
     */
    #[Route('/{demoCode}/info', name: 'demo_info', methods: ['GET'], requirements: ['demoCode' => '[a-zA-Z0-9]{8,}'])]
    public function demoInfo(string $demoCode): Response
    {
        try {
            $demo = $this->demoService->getDemoByCode($demoCode);

            if (!$demo) {
                throw new \Exception('Démo introuvable');
            }

            return $this->render('demo/info.html.twig', [
                'demo' => $demo,
                'demoCode' => $demoCode,
                'isActive' => $this->demoService->isDemoActive($demo)
            ]);

        } catch (\Exception $e) {
            return $this->render('demo/error.html.twig', [
                'demoCode' => $demoCode,
                'error' => $e->getMessage()
            ]);
        }
    }

    /**
     * Page de création d'une nouvelle démo
     */
    #[Route('/create', name: 'demo_create_url', methods: ['GET', 'POST'])]
    public function createDemo(Request $request): Response
    {
        $user = $this->getUser();

        if (!$user) {
            return $this->redirectToRoute('app_login');
        }

        if ($request->isMethod('POST')) {
            try {
                $result = $this->demoService->createDemoEnvironmentWithUrl($user);

                if ($result['success']) {
                    $this->addFlash('success', $result['message']);
                    return $this->redirectToRoute('demo_info', ['demoCode' => $result['demo_code']]);
                } else {
                    $this->addFlash('error', $result['message']);
                }
            } catch (\Exception $e) {
                $this->addFlash('error', 'Erreur lors de la création de la démo : ' . $e->getMessage());
            }
        }

        return $this->render('demo/create_url.html.twig', [
            'user' => $user
        ]);
    }

    /**
     * Liste des démos de l'utilisateur connecté
     */
    #[Route('/my-demos', name: 'demo_my_demos', methods: ['GET'])]
    public function myDemos(): Response
    {
        $user = $this->getUser();

        if (!$user) {
            return $this->redirectToRoute('app_login');
        }

        $demos = $this->demoService->getUserDemos($user);

        return $this->render('demo/my_demos.html.twig', [
            'demos' => $demos,
            'user' => $user
        ]);
    }

    /**
     * Prolonger une démo
     */
    #[Route('/{demoCode}/extend', name: 'demo_extend_url', methods: ['POST'], requirements: ['demoCode' => '[a-zA-Z0-9]{8,}'])]
    public function extendDemo(string $demoCode, Request $request): Response
    {
        $user = $this->getUser();

        if (!$user) {
            return $this->redirectToRoute('app_login');
        }

        try {
            $days = (int) $request->request->get('days', 7);
            $result = $this->demoService->extendDemo($demoCode, $days);

            if ($result['success']) {
                $this->addFlash('success', $result['message']);
            } else {
                $this->addFlash('error', $result['message']);
            }
        } catch (\Exception $e) {
            $this->addFlash('error', 'Erreur lors de la prolongation : ' . $e->getMessage());
        }

        return $this->redirectToRoute('demo_my_demos');
    }

    /**
     * Supprimer une démo
     */
    #[Route('/{demoCode}/delete', name: 'demo_delete_url', methods: ['POST', 'DELETE'], requirements: ['demoCode' => '[a-zA-Z0-9]{8,}'])]
    public function deleteDemo(string $demoCode): Response
    {
        $user = $this->getUser();

        if (!$user) {
            return $this->redirectToRoute('app_login');
        }

        try {
            $result = $this->demoService->deleteDemo($demoCode);

            if ($result['success']) {
                $this->addFlash('success', $result['message']);
            } else {
                $this->addFlash('error', $result['message']);
            }
        } catch (\Exception $e) {
            $this->addFlash('error', 'Erreur lors de la suppression : ' . $e->getMessage());
        }

        return $this->redirectToRoute('demo_my_demos');
    }
}
