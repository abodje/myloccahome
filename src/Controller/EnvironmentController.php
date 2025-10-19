<?php

namespace App\Controller;

use App\Repository\EnvironmentRepository;
use App\Service\EnvironmentManagementService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

class EnvironmentController extends AbstractController
{
    #[Route('/env/{envCode}', name: 'app_environment_redirect', requirements: ['envCode' => '[a-zA-Z0-9\-]+'])]
    public function redirectToEnvironment(
        string $envCode,
        EnvironmentRepository $environmentRepository,
        EnvironmentManagementService $environmentService
    ): Response {
        // Chercher l'environnement par son code
        $environment = $environmentRepository->findBySubdomain($envCode);

        if (!$environment) {
            throw $this->createNotFoundException('Environnement non trouvé');
        }

        if (!$environment->isActive()) {
            throw $this->createNotFoundException('Environnement inactif');
        }

        // Stocker l'environnement en session
        $request = $this->container->get('request_stack')->getCurrentRequest();
        $request->getSession()->set('current_environment', $envCode);
        $request->getSession()->set('current_environment_id', $environment->getId());

        // Rediriger vers le dashboard avec le contexte de l'environnement
        return $this->redirectToRoute('app_dashboard');
    }

    #[Route('/env/{envCode}/dashboard', name: 'app_environment_dashboard', requirements: ['envCode' => '[a-zA-Z0-9\-]+'])]
    public function environmentDashboard(
        string $envCode,
        EnvironmentRepository $environmentRepository
    ): Response {
        // Chercher l'environnement par son code
        $environment = $environmentRepository->findBySubdomain($envCode);

        if (!$environment) {
            throw $this->createNotFoundException('Environnement non trouvé');
        }

        if (!$environment->isActive()) {
            throw $this->createNotFoundException('Environnement inactif');
        }

        // Vérifier si l'utilisateur a accès à cet environnement
        $user = $this->getUser();
        if (!$user || $user->getOrganization() !== $environment->getOrganization()) {
            throw $this->createAccessDeniedException('Accès non autorisé à cet environnement');
        }

        return $this->render('environment/dashboard.html.twig', [
            'environment' => $environment,
        ]);
    }

    #[Route('/env/{envCode}/switch', name: 'app_environment_switch', methods: ['POST'], requirements: ['envCode' => '[a-zA-Z0-9\-]+'])]
    public function switchEnvironment(
        string $envCode,
        EnvironmentRepository $environmentRepository,
        Request $request
    ): Response {
        // Chercher l'environnement par son code
        $environment = $environmentRepository->findBySubdomain($envCode);

        if (!$environment) {
            return $this->json(['success' => false, 'message' => 'Environnement non trouvé']);
        }

        if (!$environment->isActive()) {
            return $this->json(['success' => false, 'message' => 'Environnement inactif']);
        }

        // Vérifier si l'utilisateur a accès à cet environnement
        $user = $this->getUser();
        if (!$user || $user->getOrganization() !== $environment->getOrganization()) {
            return $this->json(['success' => false, 'message' => 'Accès non autorisé']);
        }

        // Stocker l'environnement en session
        $request->getSession()->set('current_environment', $envCode);
        $request->getSession()->set('current_environment_id', $environment->getId());

        return $this->json([
            'success' => true,
            'message' => 'Environnement changé avec succès',
            'environment' => [
                'name' => $environment->getName(),
                'code' => $environment->getSubdomain(),
                'url' => $environment->getUrl()
            ]
        ]);
    }
}
