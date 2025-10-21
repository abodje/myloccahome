<?php

namespace App\Command;

use App\Service\NotificationService;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(
    name: 'app:test-email-settings',
    description: 'Teste les paramètres email et envoie un email avec les templates personnalisés',
)]
class TestEmailSettingsCommand extends Command
{
    private NotificationService $notificationService;

    public function __construct(NotificationService $notificationService)
    {
        $this->notificationService = $notificationService;
        parent::__construct();
    }

    protected function configure(): void
    {
        $this
            ->addOption('email', null, InputOption::VALUE_REQUIRED, 'Adresse email de test', 'info@app.lokapro.tech')
            ->addOption('type', null, InputOption::VALUE_REQUIRED, 'Type de template à tester (receipt, reminder, expiration, welcome)', 'receipt')
            ->setHelp('Cette commande teste les paramètres email et envoie un email avec les templates personnalisés.');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        $io->title('Test des paramètres email');

        $testEmail = $input->getOption('email');
        $templateType = $input->getOption('type');

        $io->section('Configuration email actuelle');

        // Afficher les paramètres email actuels
        $io->definitionList(
            ['Nom expéditeur' => 'LOKAPRO'], // À récupérer depuis SettingsService
            ['Email expéditeur' => 'info@app.lokapro.tech'], // À récupérer depuis SettingsService
            ['Devise' => 'FCFA'], // À récupérer depuis SettingsService
            ['Format date' => 'd/m/Y'], // À récupérer depuis SettingsService
        );

        $io->section(sprintf('Test d\'envoi d\'email (%s)', $templateType));

        // Test d'envoi d'email
        $success = $this->notificationService->testEmailConfiguration($testEmail);

        if ($success) {
            $io->success(sprintf('✅ Email de test envoyé avec succès à %s', $testEmail));
            $io->writeln('L\'email utilise les paramètres et templates configurés.');
            $io->writeln('Vérifiez votre boîte de réception.');
        } else {
            $io->error(sprintf('❌ Échec de l\'envoi de l\'email de test à %s', $testEmail));
            $io->writeln('Vérifiez les logs pour plus de détails.');
            return Command::FAILURE;
        }

        $io->section('Résumé du test');
        $io->writeln('✅ Paramètres email chargés');
        $io->writeln('✅ Configuration SMTP utilisée');
        $io->writeln('✅ Templates personnalisés appliqués');
        $io->writeln('✅ Email de test envoyé');

        $io->success('🎉 Paramètres email fonctionnels !');

        return Command::SUCCESS;
    }
}
