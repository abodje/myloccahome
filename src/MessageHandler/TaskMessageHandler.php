<?php

namespace App\MessageHandler;

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
use App\Service\TaskManagerService;
use App\Repository\TaskRepository;
use Psr\Log\LoggerInterface;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;

#[AsMessageHandler]
class TaskMessageHandler
{
    public function __construct(
        private TaskManagerService $taskManagerService,
        private TaskRepository $taskRepository,
        private LoggerInterface $logger
    ) {
    }

    public function __invoke(GenerateRentsMessage $message): void
    {
        $this->logger->info('🔄 Traitement du message GenerateRentsMessage', [
            'forMonth' => $message->forMonth?->format('Y-m'),
            'organizationId' => $message->organizationId,
            'companyId' => $message->companyId
        ]);

        $task = $this->taskRepository->findOneBy(['type' => 'GENERATE_RENTS']);
        if ($task && $task->isActive()) {
            $this->taskManagerService->executeTask($task);
            $this->logger->info('✅ Tâche GENERATE_RENTS exécutée avec succès');
        } else {
            $this->logger->warning('⚠️ Tâche GENERATE_RENTS non trouvée ou inactive');
        }
    }

    #[AsMessageHandler]
    public function handleGenerateRentDocuments(GenerateRentDocumentsMessage $message): void
    {
        $this->logger->info('🔄 Traitement du message GenerateRentDocumentsMessage', [
            'forMonth' => $message->forMonth?->format('Y-m'),
            'organizationId' => $message->organizationId,
            'companyId' => $message->companyId
        ]);

        $task = $this->taskRepository->findOneBy(['type' => 'GENERATE_RENT_DOCUMENTS']);
        if ($task && $task->isActive()) {
            $this->taskManagerService->executeTask($task);
            $this->logger->info('✅ Tâche GENERATE_RENT_DOCUMENTS exécutée avec succès');
        } else {
            $this->logger->warning('⚠️ Tâche GENERATE_RENT_DOCUMENTS non trouvée ou inactive');
        }
    }

    #[AsMessageHandler]
    public function handleSendRentReceipts(SendRentReceiptsMessage $message): void
    {
        $this->logger->info('🔄 Traitement du message SendRentReceiptsMessage', [
            'forMonth' => $message->forMonth?->format('Y-m'),
            'organizationId' => $message->organizationId,
            'companyId' => $message->companyId
        ]);

        $task = $this->taskRepository->findOneBy(['type' => 'RENT_RECEIPT']);
        if ($task && $task->isActive()) {
            $this->taskManagerService->executeTask($task);
            $this->logger->info('✅ Tâche RENT_RECEIPT exécutée avec succès');
        } else {
            $this->logger->warning('⚠️ Tâche RENT_RECEIPT non trouvée ou inactive');
        }
    }

    #[AsMessageHandler]
    public function handleSendPaymentReminders(SendPaymentRemindersMessage $message): void
    {
        $this->logger->info('🔄 Traitement du message SendPaymentRemindersMessage', [
            'organizationId' => $message->organizationId,
            'companyId' => $message->companyId
        ]);

        $task = $this->taskRepository->findOneBy(['type' => 'PAYMENT_REMINDER']);
        if ($task && $task->isActive()) {
            $this->taskManagerService->executeTask($task);
            $this->logger->info('✅ Tâche PAYMENT_REMINDER exécutée avec succès');
        } else {
            $this->logger->warning('⚠️ Tâche PAYMENT_REMINDER non trouvée ou inactive');
        }
    }

    #[AsMessageHandler]
    public function handleSendLeaseExpirationAlerts(SendLeaseExpirationAlertsMessage $message): void
    {
        $this->logger->info('🔄 Traitement du message SendLeaseExpirationAlertsMessage', [
            'organizationId' => $message->organizationId,
            'companyId' => $message->companyId
        ]);

        $task = $this->taskRepository->findOneBy(['type' => 'LEASE_EXPIRATION_ALERT']);
        if ($task && $task->isActive()) {
            $this->taskManagerService->executeTask($task);
            $this->logger->info('✅ Tâche LEASE_EXPIRATION_ALERT exécutée avec succès');
        } else {
            $this->logger->warning('⚠️ Tâche LEASE_EXPIRATION_ALERT non trouvée ou inactive');
        }
    }

    #[AsMessageHandler]
    public function handleSyncAccountingEntries(SyncAccountingEntriesMessage $message): void
    {
        $this->logger->info('🔄 Traitement du message SyncAccountingEntriesMessage', [
            'organizationId' => $message->organizationId,
            'companyId' => $message->companyId
        ]);

        $task = $this->taskRepository->findOneBy(['type' => 'SYNC_ACCOUNTING_ENTRIES']);
        if ($task && $task->isActive()) {
            $this->taskManagerService->executeTask($task);
            $this->logger->info('✅ Tâche SYNC_ACCOUNTING_ENTRIES exécutée avec succès');
        } else {
            $this->logger->warning('⚠️ Tâche SYNC_ACCOUNTING_ENTRIES non trouvée ou inactive');
        }
    }

    #[AsMessageHandler]
    public function handleUpdatePropertyStatus(UpdatePropertyStatusMessage $message): void
    {
        $this->logger->info('🔄 Traitement du message UpdatePropertyStatusMessage', [
            'organizationId' => $message->organizationId,
            'companyId' => $message->companyId
        ]);

        $task = $this->taskRepository->findOneBy(['type' => 'UPDATE_PROPERTY_STATUS']);
        if ($task && $task->isActive()) {
            $this->taskManagerService->executeTask($task);
            $this->logger->info('✅ Tâche UPDATE_PROPERTY_STATUS exécutée avec succès');
        } else {
            $this->logger->warning('⚠️ Tâche UPDATE_PROPERTY_STATUS non trouvée ou inactive');
        }
    }

    #[AsMessageHandler]
    public function handleCleanupDemoEnvironments(CleanupDemoEnvironmentsMessage $message): void
    {
        $this->logger->info('🔄 Traitement du message CleanupDemoEnvironmentsMessage');

        $task = $this->taskRepository->findOneBy(['type' => 'DEMO_CLEANUP']);
        if ($task && $task->isActive()) {
            $this->taskManagerService->executeTask($task);
            $this->logger->info('✅ Tâche DEMO_CLEANUP exécutée avec succès');
        } else {
            $this->logger->warning('⚠️ Tâche DEMO_CLEANUP non trouvée ou inactive');
        }
    }

    #[AsMessageHandler]
    public function handleCreateSuperAdmin(CreateSuperAdminMessage $message): void
    {
        $this->logger->info('🔄 Traitement du message CreateSuperAdminMessage', [
            'email' => $message->email,
            'firstName' => $message->firstName,
            'lastName' => $message->lastName
        ]);

        $task = $this->taskRepository->findOneBy(['type' => 'CREATE_SUPER_ADMIN']);
        if ($task && $task->isActive()) {
            // Définir les paramètres pour la création du super admin
            $task->setParameter('email', $message->email);
            $task->setParameter('firstName', $message->firstName);
            $task->setParameter('lastName', $message->lastName);
            $task->setParameter('password', $message->password);

            $this->taskManagerService->executeTask($task);
            $this->logger->info('✅ Tâche CREATE_SUPER_ADMIN exécutée avec succès');
        } else {
            $this->logger->warning('⚠️ Tâche CREATE_SUPER_ADMIN non trouvée ou inactive');
        }
    }

    #[AsMessageHandler]
    public function handleAuditCleanup(AuditCleanupMessage $message): void
    {
        $this->logger->info('🔄 Traitement du message AuditCleanupMessage', [
            'daysToKeep' => $message->daysToKeep
        ]);

        $task = $this->taskRepository->findOneBy(['type' => 'AUDIT_CLEANUP']);
        if ($task && $task->isActive()) {
            $task->setParameter('days', $message->daysToKeep);
            $this->taskManagerService->executeTask($task);
            $this->logger->info('✅ Tâche AUDIT_CLEANUP exécutée avec succès');
        } else {
            $this->logger->warning('⚠️ Tâche AUDIT_CLEANUP non trouvée ou inactive');
        }
    }

    #[AsMessageHandler]
    public function handleBackup(BackupMessage $message): void
    {
        $this->logger->info('🔄 Traitement du message BackupMessage', [
            'cleanOld' => $message->cleanOld,
            'keepDays' => $message->keepDays
        ]);

        $task = $this->taskRepository->findOneBy(['type' => 'BACKUP']);
        if ($task && $task->isActive()) {
            $task->setParameter('clean_old', $message->cleanOld);
            $task->setParameter('keep_days', $message->keepDays);
            $this->taskManagerService->executeTask($task);
            $this->logger->info('✅ Tâche BACKUP exécutée avec succès');
        } else {
            $this->logger->warning('⚠️ Tâche BACKUP non trouvée ou inactive');
        }
    }

    #[AsMessageHandler]
    public function handleTestEmailConfig(TestEmailConfigMessage $message): void
    {
        $this->logger->info('🔄 Traitement du message TestEmailConfigMessage', [
            'testEmail' => $message->testEmail
        ]);

        $task = $this->taskRepository->findOneBy(['type' => 'TEST_EMAIL_CONFIG']);
        if ($task && $task->isActive()) {
            $task->setParameter('email', $message->testEmail);
            $this->taskManagerService->executeTask($task);
            $this->logger->info('✅ Tâche TEST_EMAIL_CONFIG exécutée avec succès');
        } else {
            $this->logger->warning('⚠️ Tâche TEST_EMAIL_CONFIG non trouvée ou inactive');
        }
    }

    #[AsMessageHandler]
    public function handleFixUserOrganization(FixUserOrganizationMessage $message): void
    {
        $this->logger->info('🔄 Traitement du message FixUserOrganizationMessage', [
            'autoFixTenants' => $message->autoFixTenants,
            'logDetails' => $message->logDetails
        ]);

        $task = $this->taskRepository->findOneBy(['type' => 'FIX_USER_ORGANIZATION']);
        if ($task && $task->isActive()) {
            $task->setParameter('auto_fix_tenants', $message->autoFixTenants);
            $task->setParameter('log_details', $message->logDetails);
            $this->taskManagerService->executeTask($task);
            $this->logger->info('✅ Tâche FIX_USER_ORGANIZATION exécutée avec succès');
        } else {
            $this->logger->warning('⚠️ Tâche FIX_USER_ORGANIZATION non trouvée ou inactive');
        }
    }

    #[AsMessageHandler]
    public function handleDemoCreate(DemoCreateMessage $message): void
    {
        $this->logger->info('🔄 Traitement du message DemoCreateMessage', [
            'defaultDays' => $message->defaultDays,
            'autoCleanup' => $message->autoCleanup,
            'logDetails' => $message->logDetails
        ]);

        $task = $this->taskRepository->findOneBy(['type' => 'DEMO_CREATE']);
        if ($task && $task->isActive()) {
            $task->setParameter('default_days', $message->defaultDays);
            $task->setParameter('auto_cleanup', $message->autoCleanup);
            $task->setParameter('log_details', $message->logDetails);
            $this->taskManagerService->executeTask($task);
            $this->logger->info('✅ Tâche DEMO_CREATE exécutée avec succès');
        } else {
            $this->logger->warning('⚠️ Tâche DEMO_CREATE non trouvée ou inactive');
        }
    }

    #[AsMessageHandler]
    public function handleCreateAccountingConfigurations(CreateAccountingConfigurationsMessage $message): void
    {
        $this->logger->info('🔄 Traitement du message CreateAccountingConfigurationsMessage', [
            'logDetails' => $message->logDetails
        ]);

        $task = $this->taskRepository->findOneBy(['type' => 'CREATE_ACCOUNTING_CONFIGURATIONS']);
        if ($task && $task->isActive()) {
            $task->setParameter('log_details', $message->logDetails);
            $this->taskManagerService->executeTask($task);
            $this->logger->info('✅ Tâche CREATE_ACCOUNTING_CONFIGURATIONS exécutée avec succès');
        } else {
            $this->logger->warning('⚠️ Tâche CREATE_ACCOUNTING_CONFIGURATIONS non trouvée ou inactive');
        }
    }

    #[AsMessageHandler]
    public function handleTestAccountingConfig(TestAccountingConfigMessage $message): void
    {
        $this->logger->info('🔄 Traitement du message TestAccountingConfigMessage', [
            'logDetails' => $message->logDetails
        ]);

        $task = $this->taskRepository->findOneBy(['type' => 'TEST_ACCOUNTING_CONFIG']);
        if ($task && $task->isActive()) {
            $task->setParameter('log_details', $message->logDetails);
            $this->taskManagerService->executeTask($task);
            $this->logger->info('✅ Tâche TEST_ACCOUNTING_CONFIG exécutée avec succès');
        } else {
            $this->logger->warning('⚠️ Tâche TEST_ACCOUNTING_CONFIG non trouvée ou inactive');
        }
    }

    #[AsMessageHandler]
    public function handleCheckAccountingEntries(CheckAccountingEntriesMessage $message): void
    {
        $this->logger->info('🔄 Traitement du message CheckAccountingEntriesMessage', [
            'logDetails' => $message->logDetails
        ]);

        $task = $this->taskRepository->findOneBy(['type' => 'CHECK_ACCOUNTING_ENTRIES']);
        if ($task && $task->isActive()) {
            $task->setParameter('log_details', $message->logDetails);
            $this->taskManagerService->executeTask($task);
            $this->logger->info('✅ Tâche CHECK_ACCOUNTING_ENTRIES exécutée avec succès');
        } else {
            $this->logger->warning('⚠️ Tâche CHECK_ACCOUNTING_ENTRIES non trouvée ou inactive');
        }
    }

    #[AsMessageHandler]
    public function handleTestRentGenerationWithConfig(TestRentGenerationWithConfigMessage $message): void
    {
        $this->logger->info('🔄 Traitement du message TestRentGenerationWithConfigMessage', [
            'logDetails' => $message->logDetails
        ]);

        $task = $this->taskRepository->findOneBy(['type' => 'TEST_RENT_GENERATION_WITH_CONFIG']);
        if ($task && $task->isActive()) {
            $task->setParameter('log_details', $message->logDetails);
            $this->taskManagerService->executeTask($task);
            $this->logger->info('✅ Tâche TEST_RENT_GENERATION_WITH_CONFIG exécutée avec succès');
        } else {
            $this->logger->warning('⚠️ Tâche TEST_RENT_GENERATION_WITH_CONFIG non trouvée ou inactive');
        }
    }

    #[AsMessageHandler]
    public function handleDemoAccountingSystem(DemoAccountingSystemMessage $message): void
    {
        $this->logger->info('🔄 Traitement du message DemoAccountingSystemMessage', [
            'logDetails' => $message->logDetails
        ]);

        $task = $this->taskRepository->findOneBy(['type' => 'DEMO_ACCOUNTING_SYSTEM']);
        if ($task && $task->isActive()) {
            $task->setParameter('log_details', $message->logDetails);
            $this->taskManagerService->executeTask($task);
            $this->logger->info('✅ Tâche DEMO_ACCOUNTING_SYSTEM exécutée avec succès');
        } else {
            $this->logger->warning('⚠️ Tâche DEMO_ACCOUNTING_SYSTEM non trouvée ou inactive');
        }
    }

    #[AsMessageHandler]
    public function handleFixAccountingTable(FixAccountingTableMessage $message): void
    {
        $this->logger->info('🔄 Traitement du message FixAccountingTableMessage', [
            'logDetails' => $message->logDetails
        ]);

        $task = $this->taskRepository->findOneBy(['type' => 'FIX_ACCOUNTING_TABLE']);
        if ($task && $task->isActive()) {
            $task->setParameter('log_details', $message->logDetails);
            $this->taskManagerService->executeTask($task);
            $this->logger->info('✅ Tâche FIX_ACCOUNTING_TABLE exécutée avec succès');
        } else {
            $this->logger->warning('⚠️ Tâche FIX_ACCOUNTING_TABLE non trouvée ou inactive');
        }
    }

    #[AsMessageHandler]
    public function handleSetupAccountingSystem(SetupAccountingSystemMessage $message): void
    {
        $this->logger->info('🔄 Traitement du message SetupAccountingSystemMessage', [
            'logDetails' => $message->logDetails
        ]);

        $task = $this->taskRepository->findOneBy(['type' => 'SETUP_ACCOUNTING_SYSTEM']);
        if ($task && $task->isActive()) {
            $task->setParameter('log_details', $message->logDetails);
            $this->taskManagerService->executeTask($task);
            $this->logger->info('✅ Tâche SETUP_ACCOUNTING_SYSTEM exécutée avec succès');
        } else {
            $this->logger->warning('⚠️ Tâche SETUP_ACCOUNTING_SYSTEM non trouvée ou inactive');
        }
    }

    #[AsMessageHandler]
    public function handleInitializeEmailSettings(InitializeEmailSettingsMessage $message): void
    {
        $this->logger->info('🔄 Traitement du message InitializeEmailSettingsMessage', [
            'logDetails' => $message->logDetails
        ]);

        $task = $this->taskRepository->findOneBy(['type' => 'INITIALIZE_EMAIL_SETTINGS']);
        if ($task && $task->isActive()) {
            $task->setParameter('log_details', $message->logDetails);
            $this->taskManagerService->executeTask($task);
            $this->logger->info('✅ Tâche INITIALIZE_EMAIL_SETTINGS exécutée avec succès');
        } else {
            $this->logger->warning('⚠️ Tâche INITIALIZE_EMAIL_SETTINGS non trouvée ou inactive');
        }
    }

    #[AsMessageHandler]
    public function handleTestEmailSettings(TestEmailSettingsMessage $message): void
    {
        $this->logger->info('🔄 Traitement du message TestEmailSettingsMessage', [
            'testEmail' => $message->testEmail,
            'logDetails' => $message->logDetails
        ]);

        $task = $this->taskRepository->findOneBy(['type' => 'TEST_EMAIL_SETTINGS']);
        if ($task && $task->isActive()) {
            $task->setParameter('test_email', $message->testEmail);
            $task->setParameter('log_details', $message->logDetails);
            $this->taskManagerService->executeTask($task);
            $this->logger->info('✅ Tâche TEST_EMAIL_SETTINGS exécutée avec succès');
        } else {
            $this->logger->warning('⚠️ Tâche TEST_EMAIL_SETTINGS non trouvée ou inactive');
        }
    }

    #[AsMessageHandler]
    public function handleTestSmtpConfiguration(TestSmtpConfigurationMessage $message): void
    {
        $this->logger->info('🔄 Traitement du message TestSmtpConfigurationMessage', [
            'testEmail' => $message->testEmail,
            'logDetails' => $message->logDetails
        ]);

        $task = $this->taskRepository->findOneBy(['type' => 'TEST_SMTP_CONFIGURATION']);
        if ($task && $task->isActive()) {
            $task->setParameter('test_email', $message->testEmail);
            $task->setParameter('log_details', $message->logDetails);
            $this->taskManagerService->executeTask($task);
            $this->logger->info('✅ Tâche TEST_SMTP_CONFIGURATION exécutée avec succès');
        } else {
            $this->logger->warning('⚠️ Tâche TEST_SMTP_CONFIGURATION non trouvée ou inactive');
        }
    }

    #[AsMessageHandler]
    public function handleUpdateSmtpConfiguration(UpdateSmtpConfigurationMessage $message): void
    {
        $this->logger->info('🔄 Traitement du message UpdateSmtpConfigurationMessage', [
            'logDetails' => $message->logDetails
        ]);

        $task = $this->taskRepository->findOneBy(['type' => 'UPDATE_SMTP_CONFIGURATION']);
        if ($task && $task->isActive()) {
            $task->setParameter('log_details', $message->logDetails);
            $this->taskManagerService->executeTask($task);
            $this->logger->info('✅ Tâche UPDATE_SMTP_CONFIGURATION exécutée avec succès');
        } else {
            $this->logger->warning('⚠️ Tâche UPDATE_SMTP_CONFIGURATION non trouvée ou inactive');
        }
    }
}
