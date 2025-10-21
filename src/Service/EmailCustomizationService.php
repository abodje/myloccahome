<?php

namespace App\Service;

use App\Entity\EmailTemplate;
use App\Entity\Lease;
use App\Entity\Payment;
use App\Entity\Tenant;
use App\Entity\Property;
use App\Repository\EmailTemplateRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mime\Email;
use Twig\Environment;

class EmailCustomizationService
{
    public function __construct(
        private EmailTemplateRepository $emailTemplateRepository,
        private EntityManagerInterface $entityManager,
        private MailerInterface $mailer,
        private Environment $twig,
        private SettingsService $settingsService,
        private CurrencyService $currencyService
    ) {
    }

    /**
     * Envoie un email en utilisant un template personnalisé
     */
    public function sendCustomEmail(string $templateCode, string $toEmail, array $variables = []): bool
    {
        $template = $this->emailTemplateRepository->findByCode($templateCode);

        if (!$template) {
            throw new \Exception("Template email '{$templateCode}' introuvable");
        }

        // Remplacer les variables dans le sujet et le contenu
        $subject = $this->replaceVariables($template->getSubject(), $variables);
        $htmlContent = $this->replaceVariables($template->getHtmlContent(), $variables);

        // Vérifier si les notifications email sont activées
        if (!$this->settingsService->get('email_notifications', true)) {
            return false; // Les notifications email sont désactivées
        }

        // Créer et envoyer l'email
        $fromEmail = $this->settingsService->get('email_from', 'noreply@app.lokapro.tech');
        $fromName = $this->settingsService->get('email_from_name', 'LOKAPRO');

        $email = (new Email())
            ->from($fromEmail, $fromName)
            ->to($toEmail)
            ->subject($subject)
            ->html($htmlContent);

        if ($template->getTextContent()) {
            $textContent = $this->replaceVariables($template->getTextContent(), $variables);
            $email->text($textContent);
        }

        try {
            $this->mailer->send($email);

            // Incrémenter le compteur d'utilisation
            $template->incrementUsageCount();
            $this->entityManager->flush();

            return true;
        } catch (\Exception $e) {
            return false;
        }
    }

    /**
     * Remplace les variables dans un texte
     */
    public function replaceVariables(string $content, array $variables): string
    {
        // Ajouter les variables système par défaut
        $defaultVariables = [
            '{{app_name}}' => $this->settingsService->get('app_name', 'LOKAPRO'),
            '{{company_name}}' => $this->settingsService->get('company_name', 'LOKAPRO Gestion'),
            '{{company_address}}' => $this->settingsService->get('company_address', ''),
            '{{company_phone}}' => $this->settingsService->get('company_phone', ''),
            '{{company_email}}' => $this->settingsService->get('company_email', 'contact@app.lokapro.tech'),
            '{{current_date}}' => (new \DateTime())->format('d/m/Y'),
            '{{current_year}}' => date('Y'),
            '{{currency_symbol}}' => $this->currencyService->getActiveCurrency()->getSymbol(),
        ];

        // Fusionner avec les variables fournies
        $allVariables = array_merge($defaultVariables, $variables);

        // Remplacer toutes les variables
        foreach ($allVariables as $key => $value) {
            // S'assurer que la clé commence et finit par {{}}
            if (!str_starts_with($key, '{{')) {
                $key = '{{' . $key . '}}';
            }
            $content = str_replace($key, (string)$value, $content);
        }

        return $content;
    }

    /**
     * Prépare les variables pour un locataire
     */
    public function prepareTenantVariables(Tenant $tenant): array
    {
        return [
            '{{tenant_first_name}}' => $tenant->getFirstName(),
            '{{tenant_last_name}}' => $tenant->getLastName(),
            '{{tenant_full_name}}' => $tenant->getFullName(),
            '{{tenant_email}}' => $tenant->getEmail(),
            '{{tenant_phone}}' => $tenant->getPhone() ?? '',
            '{{tenant_address}}' => $tenant->getFullAddress() ?? '',
        ];
    }

    /**
     * Prépare les variables pour une propriété
     */
    public function preparePropertyVariables(Property $property): array
    {
        return [
            '{{property_address}}' => $property->getAddress(),
            '{{property_city}}' => $property->getCity(),
            '{{property_postal_code}}' => $property->getPostalCode(),
            '{{property_full_address}}' => $property->getFullAddress(),
            '{{property_type}}' => $property->getPropertyType(),
            '{{property_rooms}}' => (string)$property->getRooms(),
            '{{property_surface}}' => (string)$property->getSurface(),
        ];
    }

    /**
     * Prépare les variables pour un bail
     */
    public function prepareLeaseVariables(Lease $lease): array
    {
        return [
            '{{lease_id}}' => (string)$lease->getId(),
            '{{lease_start_date}}' => $lease->getStartDate()->format('d/m/Y'),
            '{{lease_end_date}}' => $lease->getEndDate() ? $lease->getEndDate()->format('d/m/Y') : 'Indéterminée',
            '{{lease_monthly_rent}}' => $this->currencyService->formatAmount((float)$lease->getMonthlyRent()),
            '{{lease_charges}}' => $lease->getCharges() ? $this->currencyService->formatAmount((float)$lease->getCharges()) : '0',
            '{{lease_deposit}}' => $this->currencyService->formatAmount((float)$lease->getDeposit()),
            '{{lease_rent_due_day}}' => (string)($lease->getRentDueDay() ?? 1),
            '{{lease_status}}' => $lease->getStatus(),
        ];
    }

    /**
     * Prépare les variables pour un paiement
     */
    public function preparePaymentVariables(Payment $payment): array
    {
        return [
            '{{payment_id}}' => str_pad((string)$payment->getId(), 6, '0', STR_PAD_LEFT),
            '{{payment_amount}}' => $this->currencyService->formatAmount((float)$payment->getAmount()),
            '{{payment_due_date}}' => $payment->getDueDate()->format('d/m/Y'),
            '{{payment_paid_date}}' => $payment->getPaidDate() ? $payment->getPaidDate()->format('d/m/Y') : '-',
            '{{payment_type}}' => $payment->getType(),
            '{{payment_status}}' => $payment->getStatus(),
            '{{payment_method}}' => $payment->getPaymentMethod() ?? '-',
            '{{payment_reference}}' => $payment->getReference() ?? '-',
        ];
    }

    /**
     * Initialise les templates par défaut
     */
    public function initializeDefaultTemplates(): void
    {
        $defaultTemplates = $this->getDefaultTemplates();

        foreach ($defaultTemplates as $templateData) {
            $existing = $this->emailTemplateRepository->findByCode($templateData['code']);

            if (!$existing) {
                $template = new EmailTemplate();
                $template->setCode($templateData['code'])
                         ->setName($templateData['name'])
                         ->setSubject($templateData['subject'])
                         ->setHtmlContent($templateData['html'])
                         ->setDescription($templateData['description'])
                         ->setAvailableVariables($templateData['variables'])
                         ->setIsSystem(true)
                         ->setIsActive(true);

                $this->entityManager->persist($template);
            }
        }

        $this->entityManager->flush();
    }

    /**
     * Retourne les templates par défaut
     */
    private function getDefaultTemplates(): array
    {
        return [
            [
                'code' => 'RENT_RECEIPT',
                'name' => 'Quittance de loyer',
                'subject' => 'Quittance de loyer - {{month}}',
                'description' => 'Email envoyé avec la quittance de loyer mensuelle',
                'variables' => [
                    'tenant_first_name', 'tenant_last_name', 'tenant_full_name',
                    'property_full_address', 'lease_monthly_rent', 'month', 'total_amount',
                    'app_name', 'company_name', 'currency_symbol'
                ],
                'html' => $this->getDefaultRentReceiptHtml(),
            ],
            [
                'code' => 'PAYMENT_REMINDER',
                'name' => 'Rappel de paiement',
                'subject' => 'Rappel - Paiement en retard',
                'description' => 'Email de rappel envoyé aux locataires en retard de paiement',
                'variables' => [
                    'tenant_first_name', 'tenant_full_name', 'property_full_address',
                    'payment_amount', 'payment_due_date', 'days_overdue',
                    'app_name', 'company_name', 'company_phone'
                ],
                'html' => $this->getDefaultPaymentReminderHtml(),
            ],
            [
                'code' => 'LEASE_EXPIRATION',
                'name' => 'Expiration de contrat',
                'subject' => 'Votre contrat expire dans {{days_until_expiration}} jours',
                'description' => 'Email d\'alerte envoyé avant l\'expiration d\'un contrat',
                'variables' => [
                    'tenant_first_name', 'tenant_full_name', 'property_full_address',
                    'lease_end_date', 'days_until_expiration',
                    'app_name', 'company_name', 'company_email'
                ],
                'html' => $this->getDefaultLeaseExpirationHtml(),
            ],
            [
                'code' => 'WELCOME',
                'name' => 'Bienvenue nouveau locataire',
                'subject' => 'Bienvenue chez {{app_name}} !',
                'description' => 'Email de bienvenue envoyé aux nouveaux locataires',
                'variables' => [
                    'tenant_first_name', 'tenant_full_name', 'property_full_address',
                    'lease_start_date', 'app_name', 'company_name'
                ],
                'html' => $this->getDefaultWelcomeHtml(),
            ],
        ];
    }

    private function getDefaultRentReceiptHtml(): string
    {
        return <<<HTML
<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <style>
        body { font-family: Arial, sans-serif; line-height: 1.6; color: #333; }
        .container { max-width: 600px; margin: 0 auto; padding: 20px; }
        .header { background: #6f42c1; color: white; padding: 30px; text-align: center; }
        .content { padding: 30px; background: white; }
        .amount { font-size: 24px; font-weight: bold; color: #6f42c1; }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>{{app_name}}</h1>
            <p>Quittance de loyer</p>
        </div>
        <div class="content">
            <p>Bonjour {{tenant_first_name}},</p>
            <p>Nous vous adressons votre quittance de loyer pour le mois de <strong>{{month}}</strong>.</p>
            <p>Propriété : <strong>{{property_full_address}}</strong></p>
            <p class="amount">Montant payé : {{total_amount}}</p>
            <p>Merci pour votre confiance.</p>
            <p>Cordialement,<br>{{company_name}}</p>
        </div>
    </div>
</body>
</html>
HTML;
    }

    private function getDefaultPaymentReminderHtml(): string
    {
        return <<<HTML
<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <style>
        body { font-family: Arial, sans-serif; line-height: 1.6; color: #333; }
        .container { max-width: 600px; margin: 0 auto; padding: 20px; }
        .header { background: #dc3545; color: white; padding: 30px; text-align: center; }
        .content { padding: 30px; background: white; }
        .alert { background: #fff3cd; padding: 15px; border-left: 4px solid #ffc107; margin: 20px 0; }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>{{app_name}}</h1>
            <p>Rappel de paiement</p>
        </div>
        <div class="content">
            <p>Bonjour {{tenant_first_name}},</p>
            <div class="alert">
                <strong>⚠️ Votre loyer est en retard de {{days_overdue}} jour(s)</strong>
            </div>
            <p>Nous vous informons que le paiement suivant n'a pas encore été reçu :</p>
            <ul>
                <li>Propriété : <strong>{{property_full_address}}</strong></li>
                <li>Montant : <strong>{{payment_amount}}</strong></li>
                <li>Date d'échéance : <strong>{{payment_due_date}}</strong></li>
            </ul>
            <p>Merci de régulariser votre situation dans les plus brefs délais.</p>
            <p>Contact : {{company_phone}}</p>
            <p>Cordialement,<br>{{company_name}}</p>
        </div>
    </div>
</body>
</html>
HTML;
    }

    private function getDefaultLeaseExpirationHtml(): string
    {
        return <<<HTML
<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <style>
        body { font-family: Arial, sans-serif; line-height: 1.6; color: #333; }
        .container { max-width: 600px; margin: 0 auto; padding: 20px; }
        .header { background: #ffc107; color: #212529; padding: 30px; text-align: center; }
        .content { padding: 30px; background: white; }
        .countdown { font-size: 32px; font-weight: bold; color: #ffc107; text-align: center; }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>{{app_name}}</h1>
            <p>Information importante</p>
        </div>
        <div class="content">
            <p>Bonjour {{tenant_first_name}},</p>
            <p>Votre contrat de location arrive à échéance :</p>
            <div class="countdown">{{days_until_expiration}} jours</div>
            <p>Propriété : <strong>{{property_full_address}}</strong></p>
            <p>Date de fin : <strong>{{lease_end_date}}</strong></p>
            <p>Contactez-nous rapidement pour discuter du renouvellement.</p>
            <p>Email : {{company_email}}</p>
            <p>Cordialement,<br>{{company_name}}</p>
        </div>
    </div>
</body>
</html>
HTML;
    }

    private function getDefaultWelcomeHtml(): string
    {
        return <<<HTML
<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <style>
        body { font-family: Arial, sans-serif; line-height: 1.6; color: #333; }
        .container { max-width: 600px; margin: 0 auto; padding: 20px; }
        .header { background: #198754; color: white; padding: 30px; text-align: center; }
        .content { padding: 30px; background: white; }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>Bienvenue chez {{app_name}} !</h1>
        </div>
        <div class="content">
            <p>Bonjour {{tenant_full_name}},</p>
            <p>Nous sommes ravis de vous accueillir en tant que nouveau locataire !</p>
            <p>Votre logement : <strong>{{property_full_address}}</strong></p>
            <p>Date d'entrée : <strong>{{lease_start_date}}</strong></p>
            <p>N'hésitez pas à nous contacter pour toute question.</p>
            <p>Bienvenue chez vous !</p>
            <p>Cordialement,<br>L'équipe {{company_name}}</p>
        </div>
    </div>
</body>
</html>
HTML;
    }

    /**
     * Récupère la liste complète des variables disponibles
     */
    public function getAllAvailableVariables(): array
    {
        return [
            'Système' => [
                '{{app_name}}' => 'Nom de l\'application',
                '{{company_name}}' => 'Nom de l\'entreprise',
                '{{company_address}}' => 'Adresse de l\'entreprise',
                '{{company_phone}}' => 'Téléphone de l\'entreprise',
                '{{company_email}}' => 'Email de l\'entreprise',
                '{{current_date}}' => 'Date actuelle (jj/mm/aaaa)',
                '{{current_year}}' => 'Année actuelle',
                '{{currency_symbol}}' => 'Symbole de la devise',
            ],
            'Locataire' => [
                '{{tenant_first_name}}' => 'Prénom du locataire',
                '{{tenant_last_name}}' => 'Nom du locataire',
                '{{tenant_full_name}}' => 'Nom complet du locataire',
                '{{tenant_email}}' => 'Email du locataire',
                '{{tenant_phone}}' => 'Téléphone du locataire',
                '{{tenant_address}}' => 'Adresse complète du locataire',
            ],
            'Propriété' => [
                '{{property_address}}' => 'Adresse de la propriété',
                '{{property_city}}' => 'Ville',
                '{{property_postal_code}}' => 'Code postal',
                '{{property_full_address}}' => 'Adresse complète',
                '{{property_type}}' => 'Type de bien',
                '{{property_rooms}}' => 'Nombre de pièces',
                '{{property_surface}}' => 'Surface en m²',
            ],
            'Bail' => [
                '{{lease_id}}' => 'Numéro du bail',
                '{{lease_start_date}}' => 'Date de début',
                '{{lease_end_date}}' => 'Date de fin',
                '{{lease_monthly_rent}}' => 'Loyer mensuel',
                '{{lease_charges}}' => 'Charges',
                '{{lease_deposit}}' => 'Dépôt de garantie',
                '{{lease_rent_due_day}}' => 'Jour d\'échéance',
                '{{lease_status}}' => 'Statut du bail',
            ],
            'Paiement' => [
                '{{payment_id}}' => 'Numéro du paiement',
                '{{payment_amount}}' => 'Montant',
                '{{payment_due_date}}' => 'Date d\'échéance',
                '{{payment_paid_date}}' => 'Date de paiement',
                '{{payment_type}}' => 'Type de paiement',
                '{{payment_status}}' => 'Statut',
                '{{payment_method}}' => 'Mode de paiement',
                '{{payment_reference}}' => 'Référence',
            ],
        ];
    }

    /**
     * Prévisualise un template avec des données de test
     */
    public function previewTemplate(EmailTemplate $template): string
    {
        $testVariables = [
            '{{tenant_first_name}}' => 'Jean',
            '{{tenant_last_name}}' => 'Dupont',
            '{{tenant_full_name}}' => 'Jean Dupont',
            '{{tenant_email}}' => 'jean.dupont@example.com',
            '{{tenant_phone}}' => '06 12 34 56 78',
            '{{property_full_address}}' => '15 rue de la République, 69001 Lyon',
            '{{lease_monthly_rent}}' => '1 200,00 €',
            '{{payment_amount}}' => '1 200,00 €',
            '{{payment_due_date}}' => '01/10/2025',
            '{{month}}' => 'Octobre 2025',
            '{{days_overdue}}' => '5',
            '{{days_until_expiration}}' => '60',
        ];

        return $this->replaceVariables($template->getHtmlContent(), $testVariables);
    }
}

