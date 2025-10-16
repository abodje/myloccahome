<?php

namespace App\Command;

use App\Service\EmailService;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(
    name: 'app:test-email-config',
    description: 'Teste la configuration email avec les paramètres configurés',
)]
class TestEmailConfigCommand extends Command
{
    public function __construct(
        private EmailService $emailService
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this
            ->addArgument('email', InputArgument::REQUIRED, 'Adresse email de test')
            ->setHelp('Cette commande teste la configuration email en envoyant un email de test.');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $testEmail = $input->getArgument('email');

        $io->title('🧪 Test de Configuration Email');

        // Vérifier les paramètres
        $settings = $this->emailService->getEmailSettings();

        $io->section('📧 Paramètres actuels :');
        $io->table(
            ['Paramètre', 'Valeur'],
            [
                ['Email expéditeur', $settings['from_email']],
                ['Nom expéditeur', $settings['from_name']],
                ['Notifications activées', $settings['notifications_enabled'] ? '✅ Oui' : '❌ Non'],
                ['Serveur SMTP', $settings['smtp_host'] ?: 'Non configuré'],
                ['Port SMTP', $settings['smtp_port'] ?: 'Non configuré'],
                ['Chiffrement', $settings['smtp_encryption'] ?: 'Aucun'],
            ]
        );

        if (!$settings['notifications_enabled']) {
            $io->error('❌ Les notifications email sont désactivées !');
            $io->note('Activez les notifications dans Administration > Paramètres > Email');
            return Command::FAILURE;
        }

        if (!$settings['smtp_host']) {
            $io->warning('⚠️ Serveur SMTP non configuré !');
            $io->note('Configurez le serveur SMTP dans Administration > Paramètres > Email');
        }

        $io->section('📤 Envoi du test...');

        try {
            $success = $this->emailService->testConfiguration($testEmail);

            if ($success) {
                $io->success('✅ Email de test envoyé avec succès !');
                $io->note("Vérifiez la boîte de réception de {$testEmail}");

                $io->section('🎯 Fonctionnalités testées :');
                $io->listing([
                    'Utilisation du nom d\'expéditeur personnalisé',
                    'Respect des paramètres email_notifications',
                    'Configuration SMTP (si configurée)',
                    'Template HTML avec variables dynamiques',
                ]);

                return Command::SUCCESS;
            } else {
                $io->error('❌ Échec de l\'envoi de l\'email de test');
                $io->note('Vérifiez la configuration SMTP et les logs d\'erreur');
                return Command::FAILURE;
            }
        } catch (\Exception $e) {
            $io->error('❌ Erreur lors du test : ' . $e->getMessage());
            return Command::FAILURE;
        }
    }
}
