<?php

namespace App\Controller\Admin;

use App\Entity\AccountingConfiguration;
use App\Form\AccountingConfigurationType;
use App\Service\AccountingConfigService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/admin/accounting-config', name: 'app_admin_accounting_config_')]
#[IsGranted('ROLE_ADMIN')]
class AccountingConfigController extends AbstractController
{
    private AccountingConfigService $configService;
    private EntityManagerInterface $entityManager;

    public function __construct(AccountingConfigService $configService, EntityManagerInterface $entityManager)
    {
        $this->configService = $configService;
        $this->entityManager = $entityManager;
    }

    #[Route('/', name: 'index', methods: ['GET'])]
    public function index(): Response
    {
        $configurations = $this->configService->getAllActiveConfigurations();

        // Grouper par catégorie
        $groupedConfigs = [];
        foreach ($configurations as $config) {
            $groupedConfigs[$config->getCategory()][] = $config;
        }

        return $this->render('admin/accounting_config/index.html.twig', [
            'configurations' => $configurations,
            'groupedConfigs' => $groupedConfigs,
            'categories' => $this->configService->getAvailableCategories(),
        ]);
    }

    #[Route('/new', name: 'new', methods: ['GET', 'POST'])]
    public function new(Request $request): Response
    {
        $configuration = new AccountingConfiguration();
        $form = $this->createForm(AccountingConfigurationType::class, $configuration, [
            'operationTypes' => $this->configService->getAvailableOperationTypes(),
            'categories' => $this->configService->getAvailableCategories(),
            'entryTypes' => $this->configService->getAvailableEntryTypes(),
        ]);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $errors = $this->configService->validateConfiguration($configuration);

            if (empty($errors)) {
                $this->configService->saveConfiguration($configuration);
                $this->addFlash('success', 'Configuration comptable créée avec succès.');
                return $this->redirectToRoute('app_admin_accounting_config_show', ['id' => $configuration->getId()]);
            } else {
                foreach ($errors as $error) {
                    $this->addFlash('error', $error);
                }
            }
        }

        return $this->render('admin/accounting_config/new.html.twig', [
            'configuration' => $configuration,
            'form' => $form,
            'operationTypes' => $this->configService->getAvailableOperationTypes(),
            'categories' => $this->configService->getAvailableCategories(),
            'entryTypes' => $this->configService->getAvailableEntryTypes(),
        ]);
    }

    #[Route('/{id}', name: 'show', methods: ['GET'])]
    public function show(AccountingConfiguration $configuration): Response
    {
        return $this->render('admin/accounting_config/show.html.twig', [
            'configuration' => $configuration,
        ]);
    }

    #[Route('/{id}/edit', name: 'edit', methods: ['GET', 'POST'])]
    public function edit(Request $request, AccountingConfiguration $configuration): Response
    {
        $form = $this->createForm(AccountingConfigurationType::class, $configuration, [
            'operationTypes' => $this->configService->getAvailableOperationTypes(),
            'categories' => $this->configService->getAvailableCategories(),
            'entryTypes' => $this->configService->getAvailableEntryTypes(),
        ]);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $errors = $this->configService->validateConfiguration($configuration);

            if (empty($errors)) {
                $this->configService->saveConfiguration($configuration);
                $this->addFlash('success', 'Configuration comptable mise à jour avec succès.');
                return $this->redirectToRoute('app_admin_accounting_config_show', ['id' => $configuration->getId()]);
            } else {
                foreach ($errors as $error) {
                    $this->addFlash('error', $error);
                }
            }
        }

        return $this->render('admin/accounting_config/edit.html.twig', [
            'configuration' => $configuration,
            'form' => $form,
            'operationTypes' => $this->configService->getAvailableOperationTypes(),
            'categories' => $this->configService->getAvailableCategories(),
            'entryTypes' => $this->configService->getAvailableEntryTypes(),
        ]);
    }

    #[Route('/{id}/delete', name: 'delete', methods: ['POST'])]
    public function delete(Request $request, AccountingConfiguration $configuration): Response
    {
        if ($this->isCsrfTokenValid('delete'.$configuration->getId(), $request->request->get('_token'))) {
            $this->configService->deleteConfiguration($configuration);
            $this->addFlash('success', 'Configuration comptable supprimée avec succès.');
        }

        return $this->redirectToRoute('app_admin_accounting_config_index');
    }

    #[Route('/{id}/toggle-status', name: 'toggle_status', methods: ['POST'])]
    public function toggleStatus(Request $request, AccountingConfiguration $configuration): Response
    {
        if ($this->isCsrfTokenValid('toggle'.$configuration->getId(), $request->request->get('_token'))) {
            if ($configuration->isActive()) {
                $this->configService->deactivateConfiguration($configuration);
                $this->addFlash('success', 'Configuration comptable désactivée.');
            } else {
                $this->configService->activateConfiguration($configuration);
                $this->addFlash('success', 'Configuration comptable activée.');
            }
        }

        return $this->redirectToRoute('app_admin_accounting_config_index');
    }

    #[Route('/create-defaults', name: 'create_defaults', methods: ['POST'])]
    public function createDefaults(Request $request): Response
    {
        if ($this->isCsrfTokenValid('create_defaults', $request->request->get('_token'))) {
            $this->configService->createDefaultConfigurations();
            $this->addFlash('success', 'Configurations comptables par défaut créées avec succès.');
        }

        return $this->redirectToRoute('app_admin_accounting_config_index');
    }
}
