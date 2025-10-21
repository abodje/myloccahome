<?php

namespace App\Controller\Admin;

use App\Entity\ContractConfig;
use App\Entity\Organization;
use App\Entity\Company;
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
        $this->denyAccessUnlessGranted('ROLE_ADMIN');

        /** @var \App\Entity\User $user */
        $user = $this->getUser();
        
        // Déterminer l'organisation et la société selon le rôle
        $organization = $user->getOrganization();
        $company = $user->getCompany();

        if (!$organization) {
            $this->addFlash('error', 'Aucune organisation assignée à votre compte.');
            return $this->render('admin/contract_config/index.html.twig', [
                'config' => $this->contractConfigService->getContractConfig($user),
                'themes' => $this->contractConfigService->getAvailableThemes(),
                'organization' => null,
                'company' => null,
                'configurations' => [],
            ]);
        }

        if ($request->isMethod('POST')) {
            $configData = $request->request->all();

            // Valider la configuration
            $errors = $this->contractConfigService->validateConfig($configData);

            if (empty($errors)) {
                // Sauvegarder la configuration
                if ($this->contractConfigService->updateContractConfig($configData, $user)) {
                    $this->addFlash('success', 'Configuration du contrat sauvegardée !');
                } else {
                    $this->addFlash('error', 'Erreur lors de la sauvegarde de la configuration.');
                }
            } else {
                foreach ($errors as $error) {
                    $this->addFlash('error', $error);
                }
            }
        }

        $config = $this->contractConfigService->getContractConfig($user);
        $themes = $this->contractConfigService->getAvailableThemes();
        
        // Récupérer toutes les configurations de l'organisation pour les super admins
        $configurations = [];
        if (in_array('ROLE_SUPER_ADMIN', $user->getRoles())) {
            $configurations = $this->contractConfigService->getConfigurationsForOrganization($organization);
        }

        return $this->render('admin/contract_config/index.html.twig', [
            'config' => $config,
            'themes' => $themes,
            'organization' => $organization,
            'company' => $company,
            'configurations' => $configurations,
        ]);
    }

    #[Route('/theme/{themeName}', name: 'app_admin_contract_config_theme', methods: ['POST'])]
    public function applyTheme(string $themeName): Response
    {
        $this->denyAccessUnlessGranted('ROLE_ADMIN');

        /** @var \App\Entity\User $user */
        $user = $this->getUser();

        try {
            $config = $this->contractConfigService->applyTheme($themeName, $user);
            $this->addFlash('success', "Thème '{$themeName}' appliqué avec succès !");
        } catch (\InvalidArgumentException $e) {
            $this->addFlash('error', $e->getMessage());
        }

        return $this->redirectToRoute('app_admin_contract_config_index');
    }

    #[Route('/preview', name: 'app_admin_contract_config_preview', methods: ['GET'])]
    public function preview(): Response
    {
        $this->denyAccessUnlessGranted('ROLE_ADMIN');

        /** @var \App\Entity\User $user */
        $user = $this->getUser();
        $config = $this->contractConfigService->getContractConfig($user);

        return $this->render('admin/contract_config/preview.html.twig', [
            'config' => $config,
        ]);
    }

    #[Route('/reset', name: 'app_admin_contract_config_reset', methods: ['POST'])]
    public function reset(): Response
    {
        $this->denyAccessUnlessGranted('ROLE_ADMIN');

        /** @var \App\Entity\User $user */
        $user = $this->getUser();

        // Réinitialiser à la configuration par défaut
        $defaultConfig = $this->contractConfigService->getContractConfig(null); // Configuration par défaut

        // Sauvegarder les valeurs par défaut
        if ($this->contractConfigService->updateContractConfig($defaultConfig, $user)) {
            $this->addFlash('success', 'Configuration réinitialisée aux valeurs par défaut !');
        } else {
            $this->addFlash('error', 'Erreur lors de la réinitialisation de la configuration.');
        }

        return $this->redirectToRoute('app_admin_contract_config_index');
    }

    #[Route('/duplicate/{id}', name: 'app_admin_contract_config_duplicate', methods: ['POST'])]
    public function duplicate(ContractConfig $config): Response
    {
        $this->denyAccessUnlessGranted('ROLE_SUPER_ADMIN');

        /** @var \App\Entity\User $user */
        $user = $this->getUser();
        $organization = $user->getOrganization();
        $company = $user->getCompany();

        if (!$organization) {
            $this->addFlash('error', 'Aucune organisation assignée à votre compte.');
            return $this->redirectToRoute('app_admin_contract_config_index');
        }

        try {
            $newConfig = $this->contractConfigService->duplicateConfiguration($config, $organization, $company);
            $this->addFlash('success', 'Configuration dupliquée avec succès !');
        } catch (\Exception $e) {
            $this->addFlash('error', 'Erreur lors de la duplication : ' . $e->getMessage());
        }

        return $this->redirectToRoute('app_admin_contract_config_index');
    }

    #[Route('/delete/{id}', name: 'app_admin_contract_config_delete', methods: ['POST'])]
    public function delete(ContractConfig $config): Response
    {
        $this->denyAccessUnlessGranted('ROLE_SUPER_ADMIN');

        try {
            $this->contractConfigService->deleteConfiguration($config);
            $this->addFlash('success', 'Configuration supprimée avec succès !');
        } catch (\Exception $e) {
            $this->addFlash('error', 'Erreur lors de la suppression : ' . $e->getMessage());
        }

        return $this->redirectToRoute('app_admin_contract_config_index');
    }
}
