<?php

namespace App\Controller\Admin;

use App\Service\EnvironmentManagementService;
use App\Service\SettingsService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/admin/domain-settings', name: 'app_admin_domain_')]
#[IsGranted('ROLE_ADMIN')]
class DomainSettingsController extends AbstractController
{
    #[Route('/', name: 'index', methods: ['GET', 'POST'])]
    public function index(
        Request $request,
        EnvironmentManagementService $environmentService,
        SettingsService $settingsService
    ): Response {
        if ($request->isMethod('POST')) {
            $baseDomain = $request->request->get('base_domain');
            $localDomain = $request->request->get('local_domain');

            if (!empty($baseDomain)) {
                $environmentService->setBaseDomain($baseDomain);
                $this->addFlash('success', 'Domaine de base mis à jour avec succès.');
            } else {
                $this->addFlash('error', 'Le domaine de base ne peut pas être vide.');
            }

            if (!empty($localDomain)) {
                $environmentService->setLocalDomain($localDomain);
                $this->addFlash('success', 'Domaine local mis à jour avec succès.');
            }

            return $this->redirectToRoute('app_admin_domain_index');
        }

        $currentBaseDomain = $environmentService->getBaseDomain();
        $debugInfo = $environmentService->getDomainDebugInfo();

        return $this->render('admin/domain_settings/index.html.twig', [
            'current_base_domain' => $currentBaseDomain,
            'debug_info' => $debugInfo,
        ]);
    }

    #[Route('/test', name: 'test', methods: ['POST'])]
    public function testDomain(
        Request $request,
        EnvironmentManagementService $environmentService
    ): Response {
        $baseDomain = $request->request->get('base_domain');

        if (empty($baseDomain)) {
            return $this->json([
                'success' => false,
                'message' => 'Domaine requis'
            ]);
        }

        // Tester la résolution DNS (simulation)
        $isValid = $this->validateDomain($baseDomain);

        return $this->json([
            'success' => $isValid,
            'message' => $isValid ? 'Domaine valide' : 'Domaine invalide ou non résolu',
            'domain' => $baseDomain
        ]);
    }

    private function validateDomain(string $domain): bool
    {
        // Validation basique du format
        if (!preg_match('/^[a-zA-Z0-9]([a-zA-Z0-9\-]{0,61}[a-zA-Z0-9])?(\.[a-zA-Z0-9]([a-zA-Z0-9\-]{0,61}[a-zA-Z0-9])?)*$/', $domain)) {
            return false;
        }

        // En production, vous pourriez faire un test DNS réel
        // Pour le développement, on accepte les domaines locaux
        if (strpos($domain, 'localhost') !== false ||
            strpos($domain, '.local') !== false ||
            strpos($domain, '127.0.0.1') !== false) {
            return true;
        }

        // Simulation d'une vérification DNS
        return true; // En production, utiliser checkdnsrr($domain, 'A')
    }
}
