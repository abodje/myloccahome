<?php

namespace App\Command;

use App\Service\SettingsService;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(
    name: 'app:initialize-email-settings',
    description: 'Initialise les paramètres email par défaut',
)]
class InitializeEmailSettingsCommand extends Command
{
    private SettingsService $settingsService;

    public function __construct(SettingsService $settingsService)
    {
        $this->settingsService = $settingsService;
        parent::__construct();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        $io->title('Initialisation des paramètres email par défaut');

        // Paramètres d'expéditeur
        $this->settingsService->set('email_sender_name', 'LOKAPRO');
        $this->settingsService->set('email_from_address', 'info@app.lokapro.tech');
        $this->settingsService->set('email_signature', 'LOKAPRO - Votre partenaire immobilier');

        // Configuration des notifications
        $this->settingsService->set('email_auto_notifications', true);
        $this->settingsService->set('email_reminder_days_before', 5);
        $this->settingsService->set('email_reminder_frequency', 'daily');
        $this->settingsService->set('email_send_time', '09:00');

        // Paramètres de contenu
        $this->settingsService->set('email_default_language', 'fr');
        $this->settingsService->set('email_date_format', 'd/m/Y');
        $this->settingsService->set('email_currency', 'FCFA');

        // Templates par défaut
        $this->settingsService->set('email_template_receipt', $this->getDefaultReceiptTemplate());
        $this->settingsService->set('email_template_reminder', $this->getDefaultReminderTemplate());
        $this->settingsService->set('email_template_expiration', $this->getDefaultExpirationTemplate());
        $this->settingsService->set('email_template_welcome', $this->getDefaultWelcomeTemplate());

        // Paramètres de pièces jointes
        $this->settingsService->set('email_attachment_max_size', 10);
        $this->settingsService->set('email_compress_images', true);

        $io->success('✅ Paramètres email initialisés avec succès !');
        $io->writeln('Vous pouvez maintenant personnaliser ces paramètres via l\'interface d\'administration.');
        $io->writeln('URL: /admin/parametres/email');

        return Command::SUCCESS;
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
