<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use App\Service\DemoEnvironmentService;
use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;

class DemoController extends AbstractController
{
    private DemoEnvironmentService $demoService;
    private EntityManagerInterface $entityManager;

    public function __construct(DemoEnvironmentService $demoService, EntityManagerInterface $entityManager)
    {
        $this->demoService = $demoService;
        $this->entityManager = $entityManager;
    }

    #[Route('/demo/create', name: 'demo_create', methods: ['GET', 'POST'])]
    public function createDemo(Request $request): Response
    {
        $user = $this->getUser();

        if (!$user instanceof User) {
            return $this->redirectToRoute('app_login');
        }

        // Si c'est une requête POST (création)
        if ($request->isMethod('POST')) {
            try {
                $result = $this->demoService->createDemoEnvironment($user);

                if ($result['success']) {
                    $this->addFlash('success', $result['message']);
                    return $this->redirectToRoute('demo_list');
                } else {
                    $this->addFlash('error', $result['message']);
                }
            } catch (\Exception $e) {
                $this->addFlash('error', 'Erreur lors de la création de la démo : ' . $e->getMessage());
            }
        }

        // Affichage du formulaire de création
        return $this->render('demo/create.html.twig', [
            'user' => $user
        ]);
    }

    #[Route('/demo/{subdomain}', name: 'demo_access', methods: ['GET'])]
    public function accessDemo(string $subdomain, Request $request): Response
    {
        // Détecter le sous-domaine depuis l'en-tête Host
        $host = $request->getHost();
        $detectedSubdomain = $this->extractSubdomainFromHost($host);

        if ($detectedSubdomain && $detectedSubdomain !== $subdomain) {
            // Rediriger vers le bon sous-domaine
            $scheme = $request->isSecure() ? 'https' : 'http';
            $url = "{$scheme}://{$detectedSubdomain}.demo.{$this->getBaseDomain($host)}";
            return $this->redirect($url);
        }

        // Charger l'organisation correspondant au sous-domaine
        $organization = $this->entityManager->getRepository(\App\Entity\Organization::class)
            ->findOneBy(['subdomain' => $subdomain]);

        if (!$organization) {
            throw $this->createNotFoundException('Démo non trouvée');
        }

        // Vérifier si la démo est active
        if (!$organization->isActive() || !$organization->isDemo()) {
            throw $this->createNotFoundException('Cette démo n\'est plus disponible');
        }

        // Charger les données de la démo
        $company = $organization->getCompanies()->first();
        $properties = $organization->getProperties();
        $tenants = $organization->getTenants();

        return $this->render('demo/index.html.twig', [
            'organization' => $organization,
            'company' => $company,
            'properties' => $properties,
            'tenants' => $tenants,
            'subdomain' => $subdomain,
            'demo_url' => $request->getUri()
        ]);
    }

    /**
     * Extrait le sous-domaine depuis le host
     */
    private function extractSubdomainFromHost(string $host): ?string
    {
        if (preg_match('/^([^.]+)\.demo\.(.+)$/', $host, $matches)) {
            return $matches[1];
        }
        return null;
    }

    #[Route('/demo/list', name: 'demo_list', methods: ['GET'])]
    public function listDemos(): Response
    {
        $this->denyAccessUnlessGranted('ROLE_ADMIN');

        $demos = $this->demoService->listDemoEnvironments();
        $stats = $this->demoService->getDemoStatistics();

        return $this->render('admin/demo/list.html.twig', [
            'demos' => $demos,
            'stats' => $stats
        ]);
    }

    #[Route('/demo/{subdomain}/delete', name: 'demo_delete', methods: ['DELETE'])]
    public function deleteDemo(string $subdomain): Response
    {
        $this->denyAccessUnlessGranted('ROLE_ADMIN');

        $result = $this->demoService->deleteDemoEnvironment($subdomain);

        if ($result['success']) {
            $this->addFlash('success', $result['message']);
        } else {
            $this->addFlash('error', $result['message']);
        }

        return $this->redirectToRoute('demo_list');
    }

    #[Route('/demo/{subdomain}/extend', name: 'demo_extend', methods: ['POST'])]
    public function extendDemo(string $subdomain, Request $request): Response
    {
        $this->denyAccessUnlessGranted('ROLE_ADMIN');

        $days = (int) $request->request->get('days', 7);
        $result = $this->demoService->extendDemoEnvironment($subdomain, $days);

        if ($result['success']) {
            $this->addFlash('success', $result['message']);
        } else {
            $this->addFlash('error', $result['message']);
        }

        return $this->redirectToRoute('demo_list');
    }

    #[Route('/demo/cleanup', name: 'demo_cleanup', methods: ['POST'])]
    public function cleanupDemos(): Response
    {
        $this->denyAccessUnlessGranted('ROLE_ADMIN');

        $result = $this->demoService->cleanupExpiredDemos();

        if ($result['success']) {
            $this->addFlash('success', $result['message']);
        } else {
            $this->addFlash('error', 'Erreur lors du nettoyage des démos');
        }

        return $this->redirectToRoute('demo_list');
    }

    #[Route('/demo/stats', name: 'demo_stats', methods: ['GET'])]
    public function demoStats(): Response
    {
        $this->denyAccessUnlessGranted('ROLE_ADMIN');

        $stats = $this->demoService->getDemoStatistics();

        return $this->json($stats);
    }

    #[Route('/api/demo/create', name: 'api_demo_create', methods: ['POST'])]
    public function createDemoApi(Request $request): Response
    {
        $user = $this->getUser();

        if (!$user instanceof User) {
            return $this->json(['error' => 'Utilisateur non connecté'], 401);
        }

        try {
            $result = $this->demoService->createDemoEnvironment($user);

            if ($result['success']) {
                return $this->json([
                    'success' => true,
                    'demo_url' => $result['demo_url'],
                    'subdomain' => $result['subdomain'],
                    'message' => $result['message']
                ]);
            } else {
                return $this->json([
                    'success' => false,
                    'error' => $result['error'],
                    'message' => $result['message']
                ], 400);
            }
        } catch (\Exception $e) {
            return $this->json([
                'success' => false,
                'error' => $e->getMessage(),
                'message' => 'Erreur lors de la création de la démo'
            ], 500);
        }
    }

    /**
     * Obtient le domaine de base depuis le host
     */
    private function getBaseDomain(string $host): string
    {
        if (preg_match('/^[^.]+\.demo\.(.+)$/', $host, $matches)) {
            return $matches[1];
        }
        return $host;
    }
}
