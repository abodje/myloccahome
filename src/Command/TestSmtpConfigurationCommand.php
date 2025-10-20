<?php

namespace App\Command;

use App\Service\SmtpConfigurationService;
use App\Service\NotificationService;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(
    name: 'app:test-smtp-configuration',
    description: 'Teste la configuration SMTP pour l\'envoi d\'emails',
)]
class TestSmtpConfigurationCommand extends Command
{
    private SmtpConfigurationService $smtpConfigurationService;
    private NotificationService $notificationService;

    public function __construct(SmtpConfigurationService $smtpConfigurationService, NotificationService $notificationService)
    {
        $this->smtpConfigurationService = $smtpConfigurationService;
        $this->notificationService = $notificationService;
        parent::__construct();
    }

    protected function configure(): void
    {
        $this
            ->addOption('email', null, InputOption::VALUE_REQUIRED, 'Adresse email de test', 'info@app.lokapro.tech')
            ->setHelp('Cette commande teste la configuration SMTP et envoie un email de test.');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        $io->title('Test de la configuration SMTP');

        // Afficher la configuration SMTP
        $io->section('Configuration SMTP actuelle');
        $smtpConfig = $this->smtpConfigurationService->getSmtpConfiguration();

        $io->definitionList(
            ['Host' => $smtpConfig['host']],
            ['Port' => $smtpConfig['port']],
            ['Username' => $smtpConfig['username']],
            ['Password' => str_repeat('*', strlen($smtpConfig['password']))],
            ['Encryption' => $smtpConfig['encryption']],
            ['Auth Mode' => $smtpConfig['auth_mode']]
        );

        // Afficher le DSN généré
        $io->section('DSN SMTP généré');
        $dsn = $this->smtpConfigurationService->getSmtpDsn();
        $io->writeln('<info>' . $dsn . '</info>');

        // Tester la connexion SMTP
        $io->section('Test de connexion SMTP');
        $connectionTest = $this->smtpConfigurationService->testSmtpConnection();

        if ($connectionTest['success']) {
            $io->success($connectionTest['message']);
        } else {
            $io->error($connectionTest['message']);
            return Command::FAILURE;
        }

        // Envoyer un email de test
        $testEmail = $input->getOption('email');
        $io->section(sprintf('Envoi d\'un email de test à %s', $testEmail));

        $emailSent = $this->notificationService->testEmailConfiguration($testEmail);

        if ($emailSent) {
            $io->success(sprintf('✅ Email de test envoyé avec succès à %s', $testEmail));
            $io->writeln('Vérifiez votre boîte de réception.');
        } else {
            $io->error(sprintf('❌ Échec de l\'envoi de l\'email de test à %s', $testEmail));
            $io->writeln('Vérifiez les logs pour plus de détails.');
            return Command::FAILURE;
        }

        // Résumé
        $io->section('Résumé du test');
        $io->writeln('✅ Configuration SMTP chargée');
        $io->writeln('✅ Connexion SMTP testée');
        $io->writeln('✅ Email de test envoyé');

        $io->success('🎉 Configuration SMTP fonctionnelle !');

        return Command::SUCCESS;
    }
}
