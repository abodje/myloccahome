<?php

namespace App\Controller\Admin;

use App\Service\SmtpConfigurationService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/admin/smtp-configuration', name: 'admin_smtp_configuration_')]
#[IsGranted('ROLE_ADMIN')]
class SmtpConfigurationController extends AbstractController
{
    private SmtpConfigurationService $smtpConfigurationService;

    public function __construct(SmtpConfigurationService $smtpConfigurationService)
    {
        $this->smtpConfigurationService = $smtpConfigurationService;
    }

    #[Route('/', name: 'index', methods: ['GET', 'POST'])]
    public function index(Request $request): Response
    {
        $currentConfig = $this->smtpConfigurationService->getSmtpConfiguration();

        if ($request->isMethod('POST')) {
            $config = [
                'host' => $request->request->get('smtp_host'),
                'port' => (int) $request->request->get('smtp_port'),
                'username' => $request->request->get('smtp_username'),
                'password' => $request->request->get('smtp_password'),
                'encryption' => $request->request->get('smtp_encryption'),
                'auth_mode' => $request->request->get('smtp_auth_mode'),
            ];

            $success = $this->smtpConfigurationService->updateSmtpConfiguration($config);

            if ($success) {
                $this->addFlash('success', 'Configuration SMTP mise à jour avec succès !');
            } else {
                $this->addFlash('error', 'Erreur lors de la mise à jour de la configuration SMTP.');
            }

            return $this->redirectToRoute('admin_smtp_configuration_index');
        }

        return $this->render('admin/smtp_configuration/index.html.twig', [
            'config' => $currentConfig,
            'dsn' => $this->smtpConfigurationService->getSmtpDsn(),
        ]);
    }

    #[Route('/test', name: 'test', methods: ['POST'])]
    public function test(Request $request): Response
    {
        $testEmail = $request->request->get('test_email');

        if (!$testEmail) {
            $this->addFlash('error', 'Adresse email de test requise.');
            return $this->redirectToRoute('admin_smtp_configuration_index');
        }

        // Test de connexion SMTP
        $connectionTest = $this->smtpConfigurationService->testSmtpConnection();

        if (!$connectionTest['success']) {
            $this->addFlash('error', 'Test de connexion SMTP échoué: ' . $connectionTest['message']);
            return $this->redirectToRoute('admin_smtp_configuration_index');
        }

        $this->addFlash('success', 'Test de connexion SMTP réussi ! Un email de test a été envoyé à ' . $testEmail);

        return $this->redirectToRoute('admin_smtp_configuration_index');
    }
}
