<?php

namespace App\Command;

use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(
    name: 'app:messenger:list-tasks',
    description: 'Liste toutes les tâches disponibles dans le système Messenger.',
)]
class ListMessengerTasksCommand extends Command
{
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $io->title('📋 Tâches disponibles dans le système Messenger');

        $tasks = [
            // Tâches principales
            'GENERATE_RENTS' => [
                'description' => 'Génération automatique des loyers',
                'parameters' => ['--month', '--organization-id', '--company-id'],
                'example' => 'php bin/console app:messenger:dispatch-task --task-type=GENERATE_RENTS --month=2024-02'
            ],
            'GENERATE_RENT_DOCUMENTS' => [
                'description' => 'Génération des quittances et avis d\'échéances',
                'parameters' => ['--month', '--organization-id', '--company-id'],
                'example' => 'php bin/console app:messenger:dispatch-task --task-type=GENERATE_RENT_DOCUMENTS --month=2024-02'
            ],
            'SEND_RENT_RECEIPTS' => [
                'description' => 'Envoi des quittances de loyer',
                'parameters' => ['--month', '--organization-id', '--company-id'],
                'example' => 'php bin/console app:messenger:dispatch-task --task-type=SEND_RENT_RECEIPTS'
            ],
            'SEND_PAYMENT_REMINDERS' => [
                'description' => 'Rappels de paiement',
                'parameters' => ['--organization-id', '--company-id'],
                'example' => 'php bin/console app:messenger:dispatch-task --task-type=SEND_PAYMENT_REMINDERS'
            ],
            'SEND_LEASE_EXPIRATION_ALERTS' => [
                'description' => 'Alertes d\'expiration de contrat',
                'parameters' => ['--organization-id', '--company-id'],
                'example' => 'php bin/console app:messenger:dispatch-task --task-type=SEND_LEASE_EXPIRATION_ALERTS'
            ],
            'SYNC_ACCOUNTING_ENTRIES' => [
                'description' => 'Synchronisation des écritures comptables',
                'parameters' => ['--organization-id', '--company-id'],
                'example' => 'php bin/console app:messenger:dispatch-task --task-type=SYNC_ACCOUNTING_ENTRIES'
            ],
            'UPDATE_PROPERTY_STATUS' => [
                'description' => 'Mise à jour du statut des propriétés',
                'parameters' => ['--organization-id', '--company-id', '--logDetails'],
                'example' => 'php bin/console app:messenger:dispatch-task --task-type=UPDATE_PROPERTY_STATUS --logDetails'
            ],

            // Tâches de maintenance
            'CLEANUP_DEMO_ENVIRONMENTS' => [
                'description' => 'Nettoyage des environnements de démo',
                'parameters' => [],
                'example' => 'php bin/console app:messenger:dispatch-task --task-type=CLEANUP_DEMO_ENVIRONMENTS'
            ],
            'AUDIT_CLEANUP' => [
                'description' => 'Nettoyage de l\'historique d\'audit',
                'parameters' => ['--days'],
                'example' => 'php bin/console app:messenger:dispatch-task --task-type=AUDIT_CLEANUP --days=90'
            ],
            'BACKUP' => [
                'description' => 'Sauvegarde automatique',
                'parameters' => ['--cleanOld', '--keepDays'],
                'example' => 'php bin/console app:messenger:dispatch-task --task-type=BACKUP --cleanOld --keepDays=30'
            ],

            // Tâches d'administration
            'CREATE_SUPER_ADMIN' => [
                'description' => 'Création d\'un super administrateur',
                'parameters' => ['--email', '--firstName', '--lastName', '--password'],
                'example' => 'php bin/console app:messenger:dispatch-task --task-type=CREATE_SUPER_ADMIN --email=admin@test.com --firstName=Admin --lastName=Test --password=password123'
            ],
            'FIX_USER_ORGANIZATION' => [
                'description' => 'Correction des utilisateurs sans organisation',
                'parameters' => ['--autoFixTenants', '--logDetails'],
                'example' => 'php bin/console app:messenger:dispatch-task --task-type=FIX_USER_ORGANIZATION --autoFixTenants --logDetails'
            ],
            'DEMO_CREATE' => [
                'description' => 'Création d\'environnements de démo',
                'parameters' => ['--defaultDays', '--autoCleanup', '--logDetails'],
                'example' => 'php bin/console app:messenger:dispatch-task --task-type=DEMO_CREATE --defaultDays=14 --autoCleanup --logDetails'
            ],

            // Tâches comptables
            'CREATE_ACCOUNTING_CONFIGURATIONS' => [
                'description' => 'Création des configurations comptables',
                'parameters' => ['--logDetails'],
                'example' => 'php bin/console app:messenger:dispatch-task --task-type=CREATE_ACCOUNTING_CONFIGURATIONS --logDetails'
            ],
            'TEST_ACCOUNTING_CONFIG' => [
                'description' => 'Test de configuration comptable',
                'parameters' => ['--logDetails'],
                'example' => 'php bin/console app:messenger:dispatch-task --task-type=TEST_ACCOUNTING_CONFIG --logDetails'
            ],
            'CHECK_ACCOUNTING_ENTRIES' => [
                'description' => 'Vérification des écritures comptables',
                'parameters' => ['--logDetails'],
                'example' => 'php bin/console app:messenger:dispatch-task --task-type=CHECK_ACCOUNTING_ENTRIES --logDetails'
            ],
            'TEST_RENT_GENERATION_WITH_CONFIG' => [
                'description' => 'Test de génération de loyers avec configuration',
                'parameters' => ['--logDetails'],
                'example' => 'php bin/console app:messenger:dispatch-task --task-type=TEST_RENT_GENERATION_WITH_CONFIG --logDetails'
            ],
            'DEMO_ACCOUNTING_SYSTEM' => [
                'description' => 'Démonstration du système comptable',
                'parameters' => ['--logDetails'],
                'example' => 'php bin/console app:messenger:dispatch-task --task-type=DEMO_ACCOUNTING_SYSTEM --logDetails'
            ],
            'FIX_ACCOUNTING_TABLE' => [
                'description' => 'Correction de la table comptable',
                'parameters' => ['--logDetails'],
                'example' => 'php bin/console app:messenger:dispatch-task --task-type=FIX_ACCOUNTING_TABLE --logDetails'
            ],
            'SETUP_ACCOUNTING_SYSTEM' => [
                'description' => 'Configuration du système comptable',
                'parameters' => ['--logDetails'],
                'example' => 'php bin/console app:messenger:dispatch-task --task-type=SETUP_ACCOUNTING_SYSTEM --logDetails'
            ],

            // Tâches email
            'TEST_EMAIL_CONFIG' => [
                'description' => 'Test de configuration email',
                'parameters' => ['--email'],
                'example' => 'php bin/console app:messenger:dispatch-task --task-type=TEST_EMAIL_CONFIG --email=test@example.com'
            ],
            'INITIALIZE_EMAIL_SETTINGS' => [
                'description' => 'Initialisation des paramètres email',
                'parameters' => ['--logDetails'],
                'example' => 'php bin/console app:messenger:dispatch-task --task-type=INITIALIZE_EMAIL_SETTINGS --logDetails'
            ],
            'TEST_EMAIL_SETTINGS' => [
                'description' => 'Test des paramètres email',
                'parameters' => ['--email', '--logDetails'],
                'example' => 'php bin/console app:messenger:dispatch-task --task-type=TEST_EMAIL_SETTINGS --email=test@example.com --logDetails'
            ],
            'TEST_SMTP_CONFIGURATION' => [
                'description' => 'Test de la configuration SMTP',
                'parameters' => ['--email', '--logDetails'],
                'example' => 'php bin/console app:messenger:dispatch-task --task-type=TEST_SMTP_CONFIGURATION --email=test@example.com --logDetails'
            ],
            'UPDATE_SMTP_CONFIGURATION' => [
                'description' => 'Mise à jour de la configuration SMTP',
                'parameters' => ['--logDetails'],
                'example' => 'php bin/console app:messenger:dispatch-task --task-type=UPDATE_SMTP_CONFIGURATION --logDetails'
            ],
        ];

        $io->section('🚀 Tâches principales');
        $this->displayTaskCategory($io, $tasks, [
            'GENERATE_RENTS', 'GENERATE_RENT_DOCUMENTS', 'SEND_RENT_RECEIPTS',
            'SEND_PAYMENT_REMINDERS', 'SEND_LEASE_EXPIRATION_ALERTS',
            'SYNC_ACCOUNTING_ENTRIES', 'UPDATE_PROPERTY_STATUS'
        ]);

        $io->section('🧹 Tâches de maintenance');
        $this->displayTaskCategory($io, $tasks, [
            'CLEANUP_DEMO_ENVIRONMENTS', 'AUDIT_CLEANUP', 'BACKUP'
        ]);

        $io->section('👤 Tâches d\'administration');
        $this->displayTaskCategory($io, $tasks, [
            'CREATE_SUPER_ADMIN', 'FIX_USER_ORGANIZATION', 'DEMO_CREATE'
        ]);

        $io->section('💰 Tâches comptables');
        $this->displayTaskCategory($io, $tasks, [
            'CREATE_ACCOUNTING_CONFIGURATIONS', 'TEST_ACCOUNTING_CONFIG',
            'CHECK_ACCOUNTING_ENTRIES', 'TEST_RENT_GENERATION_WITH_CONFIG',
            'DEMO_ACCOUNTING_SYSTEM', 'FIX_ACCOUNTING_TABLE', 'SETUP_ACCOUNTING_SYSTEM'
        ]);

        $io->section('📧 Tâches email');
        $this->displayTaskCategory($io, $tasks, [
            'TEST_EMAIL_CONFIG', 'INITIALIZE_EMAIL_SETTINGS', 'TEST_EMAIL_SETTINGS',
            'TEST_SMTP_CONFIGURATION', 'UPDATE_SMTP_CONFIGURATION'
        ]);

        $io->section('📋 Utilisation');
        $io->writeln('Pour envoyer une tâche :');
        $io->writeln('<info>php bin/console app:messenger:dispatch-task --task-type=TASK_TYPE [options]</info>');
        $io->newLine();
        $io->writeln('Pour voir l\'aide complète :');
        $io->writeln('<info>php bin/console app:messenger:dispatch-task --help</info>');

        return Command::SUCCESS;
    }

    private function displayTaskCategory(SymfonyStyle $io, array $tasks, array $taskTypes): void
    {
        foreach ($taskTypes as $taskType) {
            if (isset($tasks[$taskType])) {
                $task = $tasks[$taskType];
                $io->writeln(sprintf('<comment>%s</comment>', $taskType));
                $io->writeln(sprintf('  %s', $task['description']));

                if (!empty($task['parameters'])) {
                    $io->writeln(sprintf('  Paramètres: %s', implode(', ', $task['parameters'])));
                }

                $io->writeln(sprintf('  Exemple: %s', $task['example']));
                $io->newLine();
            }
        }
    }
}
