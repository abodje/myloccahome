<?php

namespace App\Command;

use App\Message\GenerateRentsMessage;
use App\Message\GenerateRentDocumentsMessage;
use App\Message\SendRentReceiptsMessage;
use App\Message\SendPaymentRemindersMessage;
use App\Message\SendLeaseExpirationAlertsMessage;
use App\Message\SyncAccountingEntriesMessage;
use App\Message\UpdatePropertyStatusMessage;
use App\Message\CleanupDemoEnvironmentsMessage;
use App\Message\CreateSuperAdminMessage;
use App\Message\AuditCleanupMessage;
use App\Message\BackupMessage;
use App\Message\TestEmailConfigMessage;
use App\Message\FixUserOrganizationMessage;
use App\Message\DemoCreateMessage;
use App\Message\CreateAccountingConfigurationsMessage;
use App\Message\TestAccountingConfigMessage;
use App\Message\CheckAccountingEntriesMessage;
use App\Message\TestRentGenerationWithConfigMessage;
use App\Message\DemoAccountingSystemMessage;
use App\Message\FixAccountingTableMessage;
use App\Message\SetupAccountingSystemMessage;
use App\Message\InitializeEmailSettingsMessage;
use App\Message\TestEmailSettingsMessage;
use App\Message\TestSmtpConfigurationMessage;
use App\Message\UpdateSmtpConfigurationMessage;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\Messenger\MessageBusInterface;

#[AsCommand(
    name: 'app:messenger:dispatch-task',
    description: 'Envoie une tâche spécifique dans la queue Messenger.',
)]
class DispatchTaskCommand extends Command
{
    public function __construct(
        private MessageBusInterface $messageBus
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this
            ->addOption('task-type', 't', InputOption::VALUE_REQUIRED, 'Type de tâche à envoyer')
            ->addOption('month', 'm', InputOption::VALUE_REQUIRED, 'Mois pour les tâches mensuelles (format: Y-m)')
            ->addOption('organization-id', 'o', InputOption::VALUE_REQUIRED, 'ID de l\'organisation')
            ->addOption('company-id', 'c', InputOption::VALUE_REQUIRED, 'ID de la société')
            ->addOption('email', null, InputOption::VALUE_REQUIRED, 'Adresse email (pour certaines tâches)')
            ->addOption('firstName', null, InputOption::VALUE_REQUIRED, 'Prénom (pour CREATE_SUPER_ADMIN)')
            ->addOption('lastName', null, InputOption::VALUE_REQUIRED, 'Nom (pour CREATE_SUPER_ADMIN)')
            ->addOption('password', null, InputOption::VALUE_REQUIRED, 'Mot de passe (pour CREATE_SUPER_ADMIN)')
            ->addOption('days', null, InputOption::VALUE_REQUIRED, 'Nombre de jours (pour AUDIT_CLEANUP)')
            ->addOption('cleanOld', null, InputOption::VALUE_NONE, 'Nettoyer les anciens fichiers (pour BACKUP)')
            ->addOption('keepDays', 'k', InputOption::VALUE_REQUIRED, 'Jours à conserver (pour BACKUP)', 30)
            ->addOption('autoFixTenants', null, InputOption::VALUE_NONE, 'Corriger automatiquement les locataires (pour FIX_USER_ORGANIZATION)')
            ->addOption('logDetails', null, InputOption::VALUE_NONE, 'Loguer les détails')
            ->addOption('defaultDays', null, InputOption::VALUE_REQUIRED, 'Jours par défaut (pour DEMO_CREATE)', 14)
            ->addOption('autoCleanup', null, InputOption::VALUE_NONE, 'Nettoyage automatique (pour DEMO_CREATE)')
            ->setHelp('Cette commande envoie une tâche spécifique dans la queue Messenger pour traitement asynchrone.');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $io->title('📤 Envoi de tâche via Messenger');

        $taskType = $input->getOption('task-type');
        $month = $input->getOption('month');
        $organizationId = $input->getOption('organization-id');
        $companyId = $input->getOption('company-id');

        if (!$taskType) {
            $io->error('❌ Veuillez spécifier le type de tâche avec --task-type');
            $io->writeln('');
            $io->writeln('Types de tâches disponibles :');
            $io->writeln('  - GENERATE_RENTS');
            $io->writeln('  - GENERATE_RENT_DOCUMENTS');
            $io->writeln('  - SEND_RENT_RECEIPTS');
            $io->writeln('  - SEND_PAYMENT_REMINDERS');
            $io->writeln('  - SEND_LEASE_EXPIRATION_ALERTS');
            $io->writeln('  - SYNC_ACCOUNTING_ENTRIES');
            $io->writeln('  - UPDATE_PROPERTY_STATUS');
            $io->writeln('  - CLEANUP_DEMO_ENVIRONMENTS');
            $io->writeln('  - CREATE_SUPER_ADMIN');
            $io->writeln('  - AUDIT_CLEANUP');
            $io->writeln('  - BACKUP');
            $io->writeln('  - TEST_EMAIL_CONFIG');
            $io->writeln('  - FIX_USER_ORGANIZATION');
            $io->writeln('  - DEMO_CREATE');
            $io->writeln('  - CREATE_ACCOUNTING_CONFIGURATIONS');
            $io->writeln('  - TEST_ACCOUNTING_CONFIG');
            $io->writeln('  - CHECK_ACCOUNTING_ENTRIES');
            $io->writeln('  - TEST_RENT_GENERATION_WITH_CONFIG');
            $io->writeln('  - DEMO_ACCOUNTING_SYSTEM');
            $io->writeln('  - FIX_ACCOUNTING_TABLE');
            $io->writeln('  - SETUP_ACCOUNTING_SYSTEM');
            $io->writeln('  - INITIALIZE_EMAIL_SETTINGS');
            $io->writeln('  - TEST_EMAIL_SETTINGS');
            $io->writeln('  - TEST_SMTP_CONFIGURATION');
            $io->writeln('  - UPDATE_SMTP_CONFIGURATION');
            return Command::FAILURE;
        }

        try {
            $message = $this->createMessage($taskType, $month, $organizationId, $companyId, $input);

            if (!$message) {
                $io->error(sprintf('❌ Type de tâche "%s" non reconnu', $taskType));
                return Command::FAILURE;
            }

            $this->messageBus->dispatch($message);

            $io->success(sprintf('✅ Tâche "%s" envoyée avec succès dans la queue Messenger !', $taskType));
            $io->writeln('La tâche sera traitée par le worker Messenger.');

            return Command::SUCCESS;

        } catch (\Exception $e) {
            $io->error('❌ Erreur lors de l\'envoi de la tâche: ' . $e->getMessage());
            return Command::FAILURE;
        }
    }

    private function createMessage(string $taskType, ?string $month, ?int $organizationId, ?int $companyId, InputInterface $input): ?object
    {
        $monthDate = $month ? new \DateTime($month . '-01') : null;
        $email = $input->getOption('email');
        $firstName = $input->getOption('firstName');
        $lastName = $input->getOption('lastName');
        $password = $input->getOption('password');
        $days = $input->getOption('days');
        $cleanOld = $input->getOption('cleanOld');
        $keepDays = (int) $input->getOption('keepDays');
        $autoFixTenants = $input->getOption('autoFixTenants');
        $logDetails = $input->getOption('logDetails');
        $defaultDays = (int) $input->getOption('defaultDays');
        $autoCleanup = $input->getOption('autoCleanup');

        return match ($taskType) {
            'GENERATE_RENTS' => new GenerateRentsMessage($monthDate, $organizationId, $companyId),
            'GENERATE_RENT_DOCUMENTS' => new GenerateRentDocumentsMessage($monthDate, $organizationId, $companyId),
            'SEND_RENT_RECEIPTS' => new SendRentReceiptsMessage($monthDate, $organizationId, $companyId),
            'SEND_PAYMENT_REMINDERS' => new SendPaymentRemindersMessage($organizationId, $companyId),
            'SEND_LEASE_EXPIRATION_ALERTS' => new SendLeaseExpirationAlertsMessage($organizationId, $companyId),
            'SYNC_ACCOUNTING_ENTRIES' => new SyncAccountingEntriesMessage($organizationId, $companyId),
            'UPDATE_PROPERTY_STATUS' => new UpdatePropertyStatusMessage($organizationId, $companyId),
            'CLEANUP_DEMO_ENVIRONMENTS' => new CleanupDemoEnvironmentsMessage(),
            'CREATE_SUPER_ADMIN' => new CreateSuperAdminMessage($email, $firstName, $lastName, $password),
            'AUDIT_CLEANUP' => new AuditCleanupMessage($days ? (int) $days : 90),
            'BACKUP' => new BackupMessage($cleanOld, $keepDays),
            'TEST_EMAIL_CONFIG' => new TestEmailConfigMessage($email ?: 'info@app.lokapro.tech'),
            'FIX_USER_ORGANIZATION' => new FixUserOrganizationMessage($autoFixTenants, $logDetails),
            'DEMO_CREATE' => new DemoCreateMessage($defaultDays, $autoCleanup, $logDetails),
            'CREATE_ACCOUNTING_CONFIGURATIONS' => new CreateAccountingConfigurationsMessage($logDetails),
            'TEST_ACCOUNTING_CONFIG' => new TestAccountingConfigMessage($logDetails),
            'CHECK_ACCOUNTING_ENTRIES' => new CheckAccountingEntriesMessage($logDetails),
            'TEST_RENT_GENERATION_WITH_CONFIG' => new TestRentGenerationWithConfigMessage($logDetails),
            'DEMO_ACCOUNTING_SYSTEM' => new DemoAccountingSystemMessage($logDetails),
            'FIX_ACCOUNTING_TABLE' => new FixAccountingTableMessage($logDetails),
            'SETUP_ACCOUNTING_SYSTEM' => new SetupAccountingSystemMessage($logDetails),
            'INITIALIZE_EMAIL_SETTINGS' => new InitializeEmailSettingsMessage($logDetails),
            'TEST_EMAIL_SETTINGS' => new TestEmailSettingsMessage($email ?: 'info@app.lokapro.tech', $logDetails),
            'TEST_SMTP_CONFIGURATION' => new TestSmtpConfigurationMessage($email ?: 'info@app.lokapro.tech', $logDetails),
            'UPDATE_SMTP_CONFIGURATION' => new UpdateSmtpConfigurationMessage($logDetails),
            default => null,
        };
    }
}
