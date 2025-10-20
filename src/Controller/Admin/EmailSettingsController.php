<?php

namespace App\Controller\Admin;

use App\Service\SettingsService;
use App\Service\NotificationService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/admin/parametres/email', name: 'admin_email_settings_')]
#[IsGranted('ROLE_ADMIN')]
class EmailSettingsController extends AbstractController
{
    private SettingsService $settingsService;
    private NotificationService $notificationService;

    public function __construct(SettingsService $settingsService, NotificationService $notificationService)
    {
        $this->settingsService = $settingsService;
        $this->notificationService = $notificationService;
    }

    #[Route('/', name: 'index', methods: ['GET', 'POST'])]
    public function index(Request $request): Response
    {
        if ($request->isMethod('POST')) {
            $this->handleSettingsUpdate($request);
            return $this->redirectToRoute('admin_email_settings_index');
        }

        $settings = $this->getEmailSettings();

        return $this->render('admin/email_settings/index.html.twig', [
            'settings' => $settings,
        ]);
    }

    #[Route('/test-email', name: 'test_email', methods: ['POST'])]
    public function testEmail(Request $request): Response
    {
        $testEmail = $request->request->get('test_email');

        if (!$testEmail) {
            $this->addFlash('error', 'Adresse email de test requise.');
            return $this->redirectToRoute('admin_email_settings_index');
        }

        $success = $this->notificationService->testEmailConfiguration($testEmail);

        if ($success) {
            $this->addFlash('success', 'Email de test envoyé avec succès à ' . $testEmail);
        } else {
            $this->addFlash('error', 'Erreur lors de l\'envoi de l\'email de test.');
        }

        return $this->redirectToRoute('admin_email_settings_index');
    }

    #[Route('/preview-template', name: 'preview_template', methods: ['POST'])]
    public function previewTemplate(Request $request): Response
    {
        $templateType = $request->request->get('template_type');
        $templateContent = $request->request->get('template_content');

        // Générer un aperçu du template avec des données fictives
        $previewData = $this->getPreviewData($templateType);

        // Remplacer les variables dans le template
        $previewContent = $this->replaceTemplateVariables($templateContent, $previewData);

        return $this->render('admin/email_settings/preview.html.twig', [
            'template_content' => $previewContent,
            'template_type' => $templateType,
        ]);
    }

    private function handleSettingsUpdate(Request $request): void
    {
        $settingsToUpdate = [
            // Paramètres d'expéditeur
            'email_sender_name' => $request->request->get('email_sender_name'),
            'email_from_address' => $request->request->get('email_from_address'),
            'email_signature' => $request->request->get('email_signature'),

            // Configuration des notifications
            'email_auto_notifications' => $request->request->has('email_auto_notifications'),
            'email_reminder_days_before' => (int) $request->request->get('email_reminder_days_before'),
            'email_reminder_frequency' => $request->request->get('email_reminder_frequency'),
            'email_send_time' => $request->request->get('email_send_time'),

            // Paramètres de contenu
            'email_default_language' => $request->request->get('email_default_language'),
            'email_date_format' => $request->request->get('email_date_format'),
            'email_currency' => $request->request->get('email_currency'),

            // Templates
            'email_template_receipt' => $request->request->get('email_template_receipt'),
            'email_template_reminder' => $request->request->get('email_template_reminder'),
            'email_template_expiration' => $request->request->get('email_template_expiration'),
            'email_template_welcome' => $request->request->get('email_template_welcome'),

            // Paramètres de pièces jointes
            'email_attachment_max_size' => (int) $request->request->get('email_attachment_max_size'),
            'email_compress_images' => $request->request->has('email_compress_images'),
        ];

        foreach ($settingsToUpdate as $key => $value) {
            $this->settingsService->set($key, $value);
        }

        $this->addFlash('success', 'Paramètres email mis à jour avec succès !');
    }

    private function getEmailSettings(): array
    {
        return [
            // Paramètres d'expéditeur
            'email_sender_name' => $this->settingsService->get('email_sender_name', 'MYLOCCA'),
            'email_from_address' => $this->settingsService->get('email_from_address', 'noreply@mylocca.com'),
            'email_signature' => $this->settingsService->get('email_signature', 'MYLOCCA - Votre partenaire immobilier'),

            // Configuration des notifications
            'email_auto_notifications' => $this->settingsService->get('email_auto_notifications', true),
            'email_reminder_days_before' => $this->settingsService->get('email_reminder_days_before', 5),
            'email_reminder_frequency' => $this->settingsService->get('email_reminder_frequency', 'daily'),
            'email_send_time' => $this->settingsService->get('email_send_time', '09:00'),

            // Paramètres de contenu
            'email_default_language' => $this->settingsService->get('email_default_language', 'fr'),
            'email_date_format' => $this->settingsService->get('email_date_format', 'd/m/Y'),
            'email_currency' => $this->settingsService->get('email_currency', 'FCFA'),

            // Templates
            'email_template_receipt' => $this->settingsService->get('email_template_receipt', $this->getDefaultReceiptTemplate()),
            'email_template_reminder' => $this->settingsService->get('email_template_reminder', $this->getDefaultReminderTemplate()),
            'email_template_expiration' => $this->settingsService->get('email_template_expiration', $this->getDefaultExpirationTemplate()),
            'email_template_welcome' => $this->settingsService->get('email_template_welcome', $this->getDefaultWelcomeTemplate()),

            // Paramètres de pièces jointes
            'email_attachment_max_size' => $this->settingsService->get('email_attachment_max_size', 10),
            'email_compress_images' => $this->settingsService->get('email_compress_images', true),
        ];
    }

    private function getPreviewData(string $templateType): array
    {
        return [
            'locataire_nom' => 'Jean Dupont',
            'locataire_prenom' => 'Jean',
            'propriete_adresse' => '123 Rue de la Paix, 75001 Paris',
            'loyer_montant' => '1,200 FCFA',
            'mois' => 'Janvier',
            'annee' => '2024',
            'date_echeance' => '05/02/2024',
            'date_aujourdhui' => date('d/m/Y'),
            'societe_nom' => 'MYLOCCA',
            'societe_contact' => 'contact@mylocca.com',
            'contrat_debut' => '01/01/2024',
            'contrat_fin' => '31/12/2024',
        ];
    }

    private function replaceTemplateVariables(string $template, array $data): string
    {
        foreach ($data as $key => $value) {
            $template = str_replace('{{ ' . $key . ' }}', $value, $template);
        }
        return $template;
    }

    private function getDefaultReceiptTemplate(): string
    {
        return <<<HTML
<div style="font-family: Arial, sans-serif; max-width: 600px; margin: 0 auto;">
    <div style="background-color: #f8f9fa; padding: 20px; border-radius: 8px;">
        <h2 style="color: #2c3e50; text-align: center;">Quittance de loyer</h2>
        <p>Bonjour {{ locataire_prenom }} {{ locataire_nom }},</p>
        <p>Veuillez trouver ci-joint votre quittance de loyer pour le mois de {{ mois }} {{ annee }}.</p>

        <div style="background-color: white; padding: 15px; border-radius: 5px; margin: 15px 0;">
            <p><strong>Propriété :</strong> {{ propriete_adresse }}</p>
            <p><strong>Montant du loyer :</strong> {{ loyer_montant }}</p>
            <p><strong>Période :</strong> {{ mois }} {{ annee }}</p>
        </div>

        <p>Cordialement,</p>
        <p><strong>{{ societe_nom }}</strong><br>
        {{ societe_contact }}</p>

        <hr style="border: none; border-top: 1px solid #eee; margin: 20px 0;">
        <p style="font-size: 12px; color: #666; text-align: center;">
            {{ email_signature }}
        </p>
    </div>
</div>
HTML;
    }

    private function getDefaultReminderTemplate(): string
    {
        return <<<HTML
<div style="font-family: Arial, sans-serif; max-width: 600px; margin: 0 auto;">
    <div style="background-color: #fff3cd; padding: 20px; border-radius: 8px; border-left: 4px solid #ffc107;">
        <h2 style="color: #856404;">Rappel de paiement</h2>
        <p>Bonjour {{ locataire_prenom }} {{ locataire_nom }},</p>
        <p>Nous vous rappelons que votre loyer pour {{ mois }} {{ annee }} est dû le {{ date_echeance }}.</p>

        <div style="background-color: white; padding: 15px; border-radius: 5px; margin: 15px 0;">
            <p><strong>Propriété :</strong> {{ propriete_adresse }}</p>
            <p><strong>Montant du loyer :</strong> {{ loyer_montant }}</p>
            <p><strong>Date d'échéance :</strong> {{ date_echeance }}</p>
        </div>

        <p>Merci de procéder au paiement dans les plus brefs délais.</p>
        <p>Cordialement,</p>
        <p><strong>{{ societe_nom }}</strong></p>
    </div>
</div>
HTML;
    }

    private function getDefaultExpirationTemplate(): string
    {
        return <<<HTML
<div style="font-family: Arial, sans-serif; max-width: 600px; margin: 0 auto;">
    <div style="background-color: #f8d7da; padding: 20px; border-radius: 8px; border-left: 4px solid #dc3545;">
        <h2 style="color: #721c24;">Alerte - Expiration de contrat</h2>
        <p>Bonjour {{ locataire_prenom }} {{ locataire_nom }},</p>
        <p>Votre contrat de location arrive à expiration le {{ contrat_fin }}.</p>

        <div style="background-color: white; padding: 15px; border-radius: 5px; margin: 15px 0;">
            <p><strong>Propriété :</strong> {{ propriete_adresse }}</p>
            <p><strong>Date de fin :</strong> {{ contrat_fin }}</p>
        </div>

        <p>Veuillez nous contacter pour renouveler votre contrat ou planifier la remise des clés.</p>
        <p>Cordialement,</p>
        <p><strong>{{ societe_nom }}</strong></p>
    </div>
</div>
HTML;
    }

    private function getDefaultWelcomeTemplate(): string
    {
        return <<<HTML
<div style="font-family: Arial, sans-serif; max-width: 600px; margin: 0 auto;">
    <div style="background-color: #d1ecf1; padding: 20px; border-radius: 8px; border-left: 4px solid #17a2b8;">
        <h2 style="color: #0c5460;">Bienvenue chez {{ societe_nom }}</h2>
        <p>Bonjour {{ locataire_prenom }} {{ locataire_nom }},</p>
        <p>Nous sommes ravis de vous accueillir dans votre nouveau logement !</p>

        <div style="background-color: white; padding: 15px; border-radius: 5px; margin: 15px 0;">
            <p><strong>Propriété :</strong> {{ propriete_adresse }}</p>
            <p><strong>Début du contrat :</strong> {{ contrat_debut }}</p>
            <p><strong>Fin du contrat :</strong> {{ contrat_fin }}</p>
        </div>

        <p>Votre compte client a été créé avec succès. Vous pouvez maintenant accéder à votre espace personnel.</p>
        <p>Cordialement,</p>
        <p><strong>{{ societe_nom }}</strong></p>
    </div>
</div>
HTML;
    }
}
