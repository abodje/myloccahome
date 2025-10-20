<?php

namespace App\Service;

use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mailer\Transport;
use Symfony\Component\Mime\Email;

/**
 * Service de configuration et gestion SMTP
 */
class SmtpConfigurationService
{
    private array $smtpConfig;
    private ?MailerInterface $customMailer = null;

    public function __construct(
        private ParameterBagInterface $parameterBag,
        private SettingsService $settingsService
    ) {
        $this->smtpConfig = $this->loadSmtpConfiguration();
    }

    /**
     * Charge la configuration SMTP depuis les paramètres
     */
    private function loadSmtpConfiguration(): array
    {
        // Configuration par défaut
        $defaultConfig = [
            'host' => $this->parameterBag->get('smtp.host') ?? 'app.lokapro.tech',
            'port' => $this->parameterBag->get('smtp.port') ?? 465,
            'username' => $this->parameterBag->get('smtp.username') ?? 'info@app.lokapro.tech',
            'password' => $this->parameterBag->get('smtp.password') ?? 'q+Dy-riz8EBi;oL]',
            'encryption' => $this->parameterBag->get('smtp.encryption') ?? 'ssl',
            'auth_mode' => $this->parameterBag->get('smtp.auth_mode') ?? 'login',
        ];

        // Vérifier si des paramètres SMTP sont stockés en base
        $customSmtpHost = $this->settingsService->get('smtp_host');
        if ($customSmtpHost) {
            return [
                'host' => $customSmtpHost,
                'port' => $this->settingsService->get('smtp_port', 465),
                'username' => $this->settingsService->get('smtp_username'),
                'password' => $this->settingsService->get('smtp_password'),
                'encryption' => $this->settingsService->get('smtp_encryption', 'ssl'),
                'auth_mode' => $this->settingsService->get('smtp_auth_mode', 'login'),
            ];
        }

        return $defaultConfig;
    }

    /**
     * Génère le DSN SMTP
     */
    public function getSmtpDsn(): string
    {
        $config = $this->smtpConfig;

        // Encoder les caractères spéciaux dans l'URL
        $username = urlencode($config['username']);
        $password = urlencode($config['password']);

        return sprintf(
            'smtp://%s:%s@%s:%d?encryption=%s&auth_mode=%s',
            $username,
            $password,
            $config['host'],
            $config['port'],
            $config['encryption'],
            $config['auth_mode']
        );
    }

    /**
     * Crée un transport SMTP personnalisé
     */
    public function createCustomTransport(): \Symfony\Component\Mailer\Transport\TransportInterface
    {
        $dsn = $this->getSmtpDsn();
        return Transport::fromDsn($dsn);
    }

    /**
     * Crée un mailer personnalisé avec la configuration SMTP
     */
    public function createCustomMailer(): MailerInterface
    {
        if ($this->customMailer === null) {
            $transport = $this->createCustomTransport();
            $this->customMailer = new \Symfony\Component\Mailer\Mailer($transport);
        }

        return $this->customMailer;
    }

    /**
     * Configure un email avec les paramètres SMTP par défaut
     */
    public function configureEmail(Email $email): Email
    {
        $defaultSender = $this->parameterBag->get('mailer.default_sender') ?? 'info@app.lokapro.tech';
        $defaultSenderName = $this->parameterBag->get('mailer.default_sender_name') ?? 'MYLOCCA';

        // Si l'expéditeur n'est pas défini, utiliser le défaut
        if (!$email->getFrom()) {
            $email->from($defaultSender, $defaultSenderName);
        }

        return $email;
    }

    /**
     * Teste la connexion SMTP
     */
    public function testSmtpConnection(): array
    {
        try {
            $transport = $this->createCustomTransport();

            // Créer un email de test
            $testEmail = new Email();
            $testEmail->from($this->smtpConfig['username'])
                     ->to($this->smtpConfig['username']) // Envoyer à soi-même
                     ->subject('Test de connexion SMTP')
                     ->text('Ceci est un test de connexion SMTP depuis MYLOCCA.');

            // Tenter d'envoyer (sans vraiment envoyer)
            $transport->send($testEmail);

            return [
                'success' => true,
                'message' => 'Connexion SMTP réussie',
                'config' => $this->smtpConfig
            ];
        } catch (\Exception $e) {
            return [
                'success' => false,
                'message' => 'Erreur de connexion SMTP: ' . $e->getMessage(),
                'config' => $this->smtpConfig
            ];
        }
    }

    /**
     * Met à jour la configuration SMTP
     */
    public function updateSmtpConfiguration(array $config): bool
    {
        try {
            $this->settingsService->set('smtp_host', $config['host']);
            $this->settingsService->set('smtp_port', $config['port']);
            $this->settingsService->set('smtp_username', $config['username']);
            $this->settingsService->set('smtp_password', $config['password']);
            $this->settingsService->set('smtp_encryption', $config['encryption']);
            $this->settingsService->set('smtp_auth_mode', $config['auth_mode']);

            // Recharger la configuration
            $this->smtpConfig = $this->loadSmtpConfiguration();

            return true;
        } catch (\Exception $e) {
            return false;
        }
    }

    /**
     * Retourne la configuration SMTP actuelle
     */
    public function getSmtpConfiguration(): array
    {
        return $this->smtpConfig;
    }
}
