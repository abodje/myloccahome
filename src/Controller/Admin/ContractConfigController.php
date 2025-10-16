<?php

namespace App\Controller\Admin;

use App\Service\ContractConfigService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

#[Route('/admin/contract-config')]
class ContractConfigController extends AbstractController
{
    public function __construct(
        private ContractConfigService $contractConfigService
    ) {
    }

    #[Route('', name: 'app_admin_contract_config_index', methods: ['GET', 'POST'])]
    public function index(Request $request): Response
    {
        if ($request->isMethod('POST')) {
            $configData = $request->request->all();

            // Valider la configuration
            $errors = $this->contractConfigService->validateConfig($configData);

            if (empty($errors)) {
                // Sauvegarder les paramètres importants
                foreach ($configData as $key => $value) {
                    if (str_starts_with($key, 'contract_') && !empty($value)) {
                        $this->contractConfigService->setConfigValue($key, $value);
                    }
                }

                $this->addFlash('success', 'Configuration du contrat sauvegardée !');
            } else {
                foreach ($errors as $error) {
                    $this->addFlash('error', $error);
                }
            }
        }

        $config = $this->contractConfigService->getContractConfig();
        $themes = $this->contractConfigService->getAvailableThemes();

        return $this->render('admin/contract_config/index.html.twig', [
            'config' => $config,
            'themes' => $themes,
        ]);
    }

    #[Route('/theme/{themeName}', name: 'app_admin_contract_config_theme', methods: ['POST'])]
    public function applyTheme(string $themeName): Response
    {
        try {
            $config = $this->contractConfigService->applyTheme($themeName);

            // Sauvegarder les couleurs du thème
            $this->contractConfigService->setConfigValue('contract_primary_color', $config['contract_primary_color']);
            $this->contractConfigService->setConfigValue('contract_info_bg_color', $config['contract_info_bg_color']);
            $this->contractConfigService->setConfigValue('contract_highlight_color', $config['contract_highlight_color']);

            $this->addFlash('success', "Thème '{$themeName}' appliqué avec succès !");
        } catch (\InvalidArgumentException $e) {
            $this->addFlash('error', $e->getMessage());
        }

        return $this->redirectToRoute('app_admin_contract_config_index');
    }

    #[Route('/preview', name: 'app_admin_contract_config_preview', methods: ['GET'])]
    public function preview(): Response
    {
        $config = $this->contractConfigService->getContractConfig();

        return $this->render('admin/contract_config/preview.html.twig', [
            'config' => $config,
        ]);
    }

    #[Route('/reset', name: 'app_admin_contract_config_reset', methods: ['POST'])]
    public function reset(): Response
    {
        // Réinitialiser à la configuration par défaut
        $defaultConfig = $this->contractConfigService->getContractConfig();

        // Sauvegarder les valeurs par défaut
        foreach ($defaultConfig as $key => $value) {
            $this->contractConfigService->setConfigValue($key, $value);
        }

        $this->addFlash('success', 'Configuration réinitialisée aux valeurs par défaut !');

        return $this->redirectToRoute('app_admin_contract_config_index');
    }
}
