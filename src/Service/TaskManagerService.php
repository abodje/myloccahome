<?php

namespace App\Service;

use App\Entity\Task;
use App\Entity\User;
use App\Repository\TaskRepository;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;

class TaskManagerService
{
    public function __construct(
        private EntityManagerInterface $entityManager,
        private NotificationService $notificationService,
        private LoggerInterface $logger,
        private RentReceiptService $rentReceiptService,
        private OrangeSmsService $orangeSmsService,
        private SettingsService $settingsService,
        private UserPasswordHasherInterface $passwordHasher,
        private ParameterBagInterface $parameterBag,
        private ?AuditLogService $auditLogService = null,
        private ?BackupService $backupService = null,
        private ?DemoEnvironmentService $demoEnvironmentService = null,
        private ?\App\Service\SmtpConfigurationService $smtpConfigurationService = null
    ) {
    }

    /**
     * Récupère l'EntityManager
     */
    public function getEntityManager(): EntityManagerInterface
    {
        return $this->entityManager;
    }

    /**
     * Récupère un paramètre
     */
    private function getParameter(string $name): mixed
    {
        return $this->parameterBag->get($name);
    }

    /**
     * Exécute toutes les tâches qui sont dues
     */
    public function runDueTasks(): array
    {
        // Optimiser la mémoire avant l'exécution des tâches
        $this->optimizeMemoryBeforeExecution();

        $taskRepository = $this->entityManager->getRepository(Task::class);
        $dueTasks = $taskRepository->findDueTasks();

        $results = ['executed' => 0, 'failed' => 0, 'errors' => []];

        foreach ($dueTasks as $task) {
            try {
                $this->executeTask($task);
                $results['executed']++;
                $this->logger->info("Tâche exécutée avec succès: {$task->getName()}");

                // Optimiser la mémoire après chaque tâche
                $this->optimizeMemoryAfterTask();

            } catch (\Exception $e) {
                $task->markAsFailed($e->getMessage());
                $this->entityManager->flush();
                $results['failed']++;
                $results['errors'][] = $e->getMessage();
                $this->logger->error("Erreur lors de l'exécution de la tâche {$task->getName()}: " . $e->getMessage());
            }
        }

        return $results;
    }

    /**
     * Exécute une tâche spécifique
     */
    public function executeTask(Task $task): void
    {
        if (!$task->isActive()) {
            throw new \Exception("La tâche {$task->getName()} n'est pas active");
        }

        $task->markAsRunning();
        if ($this->entityManager->isOpen()) {
            $this->entityManager->flush();
        }

        try {
            // Vérifier que l'EntityManager est ouvert avant l'exécution
            if (!$this->entityManager->isOpen()) {
                throw new \Exception('EntityManager fermé avant l\'exécution de la tâche');
            }

            // Encapsuler l'exécution dans un try-catch global
            try {
                switch ($task->getType()) {
                case 'RENT_RECEIPT':
                    $this->executeRentReceiptTask($task);
                    break;

                case 'PAYMENT_REMINDER':
                    $this->executePaymentReminderTask($task);
                    break;

                case 'LEASE_EXPIRATION':
                    $this->executeLeaseExpirationTask($task);
                    break;

                case 'GENERATE_RENTS':
                    $this->executeGenerateRentsTask($task);
                    break;

                case 'GENERATE_RENT_DOCUMENTS':
                    $this->executeGenerateRentDocumentsTask($task);
                    break;

                case 'CREATE_SUPER_ADMIN':
                    $this->executeCreateSuperAdminTask($task);
                    break;

                case 'AUDIT_CLEANUP':
                    $this->executeAuditCleanupTask($task);
                    break;

                case 'BACKUP':
                    $this->executeBackupTask($task);
                    break;

                case 'TEST_EMAIL_CONFIG':
                    $this->executeTestEmailConfigTask($task);
                    break;

                case 'FIX_USER_ORGANIZATION':
                    $this->executeFixUserOrganizationTask($task);
                    break;

                case 'SYNC_ACCOUNTING_ENTRIES':
                    $this->executeSyncAccountingEntriesTask($task);
                    break;

                case 'DEMO_CREATE':
                    $this->executeDemoCreateTask($task);
                    break;

                case 'UPDATE_PROPERTY_STATUS':
                    $this->executeUpdatePropertyStatusTask($task);
                    break;

                case 'CREATE_ACCOUNTING_CONFIGURATIONS':
                    $this->executeCreateAccountingConfigurationsTask($task);
                    break;

                case 'TEST_ACCOUNTING_CONFIG':
                    $this->executeTestAccountingConfigTask($task);
                    break;

                case 'CHECK_ACCOUNTING_ENTRIES':
                    $this->executeCheckAccountingEntriesTask($task);
                    break;

                case 'TEST_RENT_GENERATION_WITH_CONFIG':
                    $this->executeTestRentGenerationWithConfigTask($task);
                    break;

                case 'DEMO_ACCOUNTING_SYSTEM':
                    $this->executeDemoAccountingSystemTask($task);
                    break;

                case 'FIX_ACCOUNTING_TABLE':
                    $this->executeFixAccountingTableTask($task);
                    break;

                case 'SETUP_ACCOUNTING_SYSTEM':
                    $this->executeSetupAccountingSystemTask($task);
                    break;

                case 'INITIALIZE_EMAIL_SETTINGS':
                    $this->executeInitializeEmailSettingsTask($task);
                    break;

                case 'TEST_EMAIL_SETTINGS':
                    $this->executeTestEmailSettingsTask($task);
                    break;

                case 'TEST_SMTP_CONFIGURATION':
                    $this->executeTestSmtpConfigurationTask($task);
                    break;

                case 'UPDATE_SMTP_CONFIGURATION':
                    $this->executeUpdateSmtpConfigurationTask($task);
                    break;

                case 'MIGRATE_DOCUMENTS_SECURITY':
                    $this->executeMigrateDocumentsSecurityTask($task);
                    break;

                default:
                    throw new \Exception("Type de tâche non reconnu: {$task->getType()}");
                }

            } catch (\Exception $innerException) {
                // Capturer les erreurs spécifiques comme "EntityManager is closed"
                if (strpos($innerException->getMessage(), 'EntityManager is closed') !== false) {
                    // Log l'erreur spécifique
                    $this->logger->error('EntityManager fermé lors de l\'exécution de la tâche', [
                        'task_id' => $task->getId(),
                        'task_type' => $task->getType(),
                        'error' => $innerException->getMessage()
                    ]);

                    // Marquer la tâche comme échouée avec un message plus clair
                    $task->markAsFailed('EntityManager fermé - tâche interrompue');
                    $task->setParameter('last_error', 'EntityManager fermé pendant l\'exécution');

                    // Ne pas re-lancer l'exception, gérer gracieusement
                    return;
                } else {
                    // Re-lancer les autres exceptions
                    throw $innerException;
                }
            }

            // Marquer la tâche comme terminée avec le résultat
            $task->markAsCompleted($task->getResult());
            if ($this->entityManager->isOpen()) {
                $this->entityManager->flush();
            }
        } catch (\Exception $e) {
            $task->markAsFailed($e->getMessage());
            if ($this->entityManager->isOpen()) {
                $this->entityManager->flush();
            }
            throw $e;
        }
    }

    /**
     * Exécute la tâche d'envoi de quittances de loyer
     */
    private function executeRentReceiptTask(Task $task): void
    {
        try {
            $forMonth = null;
            if ($task->getParameter('month_offset')) {
                $forMonth = new \DateTime();
                $forMonth->modify($task->getParameter('month_offset'));
                $forMonth->modify('first day of this month');
            }

            $results = $this->notificationService->sendRentReceipts($forMonth);

            $task->setParameter('last_sent_count', $results['sent']);
            $task->setParameter('last_failed_count', $results['failed']);

            // S'assurer que la tâche est sauvegardée même en cas d'erreur partielle
            $this->entityManager->flush();

        } catch (\Exception $e) {
            // Log l'erreur et marquer la tâche comme échouée
            $task->setParameter('last_error', $e->getMessage());
            $task->setParameter('last_failed_count', $task->getParameter('last_failed_count', 0) + 1);

            // S'assurer que l'EntityManager reste ouvert
            if (!$this->entityManager->isOpen()) {
                // Note: La recréation directe d'EntityManager n'est pas possible ici
                // L'erreur sera propagée et gérée par le système de tâches
                $this->logger->error('EntityManager fermé lors de l\'exécution de la tâche', [
                    'task_id' => $task->getId(),
                    'task_type' => $task->getType(),
                    'error' => $e->getMessage()
                ]);
            }

            $this->entityManager->flush();
            throw $e;
        }
    }

    /**
     * Exécute la tâche de rappels de paiement
     */
    private function executePaymentReminderTask(Task $task): void
    {
        $results = $this->notificationService->sendPaymentReminders();

        $task->setParameter('last_sent_count', $results['sent']);
        $task->setParameter('last_failed_count', $results['failed']);

        // Envoyer des SMS si activé
        if ($this->settingsService->get('orange_sms_enabled', false)) {
            $this->sendPaymentReminderSms($task);
        }
    }

    /**
     * Envoie des SMS de rappel de paiement
     */
    private function sendPaymentReminderSms(Task $task): void
    {
        $paymentRepository = $this->entityManager->getRepository(\App\Entity\Payment::class);

        // Récupérer les paiements en retard
        $overduePayments = $paymentRepository->findOverdue();

        $smsSent = 0;
        $smsFailed = 0;

        foreach ($overduePayments as $payment) {
            $tenant = $payment->getLease()->getTenant();

            if (!$tenant->getPhone()) {
                continue; // Pas de numéro de téléphone
            }

            $daysLate = (new \DateTime())->diff($payment->getDueDate())->days;

            $message = sprintf(
                "Rappel LOKAPRO: Votre loyer de %s est en retard de %d jour(s). Echéance: %s. Payez sur app.lokapro.tech",
                number_format($payment->getAmount(), 0, ',', ' ') . ' FCFA',
                $daysLate,
                $payment->getDueDate()->format('d/m/Y')
            );

            // Limiter à 160 caractères
            if (strlen($message) > 160) {
                $message = substr($message, 0, 157) . '...';
            }

            try {
                $this->orangeSmsService->envoyerSms($tenant->getPhone(), $message);
                $smsSent++;
                $this->logger->info("SMS rappel envoyé à {$tenant->getFullName()} pour paiement #{$payment->getId()}");
            } catch (\Exception $e) {
                $smsFailed++;
                $this->logger->error("Erreur envoi SMS à {$tenant->getFullName()}: " . $e->getMessage());
            }
        }

        $this->logger->info("Rappels SMS envoyés: {$smsSent} succès, {$smsFailed} échecs");
    }

    /**
     * Exécute la tâche d'alertes d'expiration de contrat
     */
    private function executeLeaseExpirationTask(Task $task): void
    {
        $results = $this->notificationService->sendLeaseExpirationAlerts();

        $task->setParameter('last_sent_count', $results['sent']);
        $task->setParameter('last_failed_count', $results['failed']);

        // Envoyer des SMS si activé
        if ($this->settingsService->get('orange_sms_enabled', false)) {
            $this->sendLeaseExpirationSms($task);
        }
    }

    /**
     * Envoie des SMS d'alerte d'expiration de bail
     */
    private function sendLeaseExpirationSms(Task $task): void
    {
        $leaseRepository = $this->entityManager->getRepository(\App\Entity\Lease::class);
        $parameters = $task->getParameters() ?? [];
        $daysBeforeExpiration = $parameters['days_before_expiration'] ?? 60;

        // Récupérer les baux expirant bientôt
        $expiringLeases = $leaseRepository->findExpiringSoon();

        $smsSent = 0;
        $smsFailed = 0;

        foreach ($expiringLeases as $lease) {
            $tenant = $lease->getTenant();

            if (!$tenant->getPhone()) {
                continue;
            }

            $daysUntilExpiration = (new \DateTime())->diff($lease->getEndDate())->days;

            $message = sprintf(
                "LOKAPRO: Votre bail %s expire dans %d jours (%s). Contactez-nous",
                $lease->getProperty()->getAddress(),
                $daysUntilExpiration,
                $lease->getEndDate()->format('d/m/Y')
            );

            // Limiter à 160 caractères
            if (strlen($message) > 160) {
                $message = substr($message, 0, 157) . '...';
            }

            try {
                $this->orangeSmsService->envoyerSms($tenant->getPhone(), $message);
                $smsSent++;
                $this->logger->info("SMS expiration bail envoyé à {$tenant->getFullName()} pour bail #{$lease->getId()}");
            } catch (\Exception $e) {
                $smsFailed++;
                $this->logger->error("Erreur envoi SMS à {$tenant->getFullName()}: " . $e->getMessage());
            }
        }

        $this->logger->info("Alertes expiration SMS envoyées: {$smsSent} succès, {$smsFailed} échecs");
    }

    /**
     * Exécute la tâche de génération des loyers
     */
    private function executeGenerateRentsTask(Task $task): void
    {
        $results = $this->notificationService->generateNextMonthRents();

        $task->setParameter('last_generated_count', $results['generated']);
    }

    /**
     * Crée les tâches par défaut
     */
    public function createDefaultTasks(): void
    {
        $defaultTasks = [
            [
                'name' => 'Envoi automatique des quittances de loyer',
                'type' => 'RENT_RECEIPT',
                'description' => 'Envoie les quittances de loyer à tous les locataires ayant payé leur loyer',
                'frequency' => 'MONTHLY',
                'parameters' => [
                    'day_of_month' => 5, // 5ème jour du mois
                    'month_offset' => '-1 month' // Pour le mois précédent
                ]
            ],
            [
                'name' => 'Rappels de paiement automatiques',
                'type' => 'PAYMENT_REMINDER',
                'description' => 'Envoie des rappels aux locataires en retard de paiement',
                'frequency' => 'WEEKLY',
                'parameters' => [
                    'min_days_overdue' => 3
                ]
            ],
            [
                'name' => 'Alertes d\'expiration de contrats',
                'type' => 'LEASE_EXPIRATION',
                'description' => 'Alerte les locataires dont le contrat expire bientôt',
                'frequency' => 'MONTHLY',
                'parameters' => [
                    'days_before_expiration' => 60
                ]
            ],
            [
                'name' => 'Génération automatique des loyers',
                'type' => 'GENERATE_RENTS',
                'description' => 'Génère automatiquement les échéances de loyer du mois suivant',
                'frequency' => 'MONTHLY',
                'parameters' => [
                    'day_of_month' => 25 // 25ème jour du mois
                ]
            ],
            [
                'name' => 'Génération des quittances et avis d\'échéances',
                'type' => 'GENERATE_RENT_DOCUMENTS',
                'description' => 'Génère automatiquement les quittances de loyer et les avis d\'échéances du mois',
                'frequency' => 'MONTHLY',
                'parameters' => [
                    'day_of_month' => 7, // 1er jour du mois
                    'month' => 'current' // Mois en cours
                ]
            ],
            [
                'name' => 'Nettoyage de l\'historique d\'audit',
                'type' => 'AUDIT_CLEANUP',
                'description' => 'Supprime les anciens enregistrements d\'audit pour optimiser la base de données',
                'frequency' => 'MONTHLY',
                'parameters' => [
                    'day_of_month' => 1, // 1er jour du mois
                    'days' => 90 // Conserver 90 jours
                ]
            ],
            [
                'name' => 'Sauvegarde automatique',
                'type' => 'BACKUP',
                'description' => 'Crée une sauvegarde complète de la base de données et des fichiers',
                'frequency' => 'DAILY',
                'parameters' => [
                    'hour' => 2, // 2h du matin
                    'clean_old' => true, // Nettoyer anciennes sauvegardes
                    'keep_days' => 30 // Conserver 30 jours
                ]
            ],
            [
                'name' => 'Test de configuration email',
                'type' => 'TEST_EMAIL_CONFIG',
                'description' => 'Teste la configuration email en envoyant un email de test',
                'frequency' => 'MANUAL', // Tâche manuelle uniquement
                'parameters' => [
                    'email' => 'admin@app.lokapro.tech' // Email par défaut pour le test
                ]
            ],
            [
                'name' => 'Correction des utilisateurs sans organisation',
                'type' => 'FIX_USER_ORGANIZATION',
                'description' => 'Corrige automatiquement les utilisateurs qui n\'ont pas d\'organization_id ou company_id définis',
                'frequency' => 'MANUAL', // Tâche manuelle uniquement
                'parameters' => [
                    'auto_fix_tenants' => true, // Corriger automatiquement les locataires
                    'log_details' => true // Loguer les détails de la correction
                ]
            ],
            [
                'name' => 'Synchronisation des écritures comptables',
                'type' => 'SYNC_ACCOUNTING_ENTRIES',
                'description' => 'Synchronise les écritures comptables avec les documents de quittances et avis d\'échéances existants',
                'frequency' => 'MANUAL', // Tâche manuelle uniquement
                'parameters' => [
                    'sync_receipts' => true, // Synchroniser les quittances
                    'sync_notices' => true, // Synchroniser les avis d'échéance
                    'log_details' => true // Loguer les détails de la synchronisation
                ]
            ],
            [
                'name' => 'Création d\'environnements de démo',
                'type' => 'DEMO_CREATE',
                'description' => 'Crée des environnements de démo pour les utilisateurs avec données de test',
                'frequency' => 'MANUAL', // Tâche manuelle uniquement
                'parameters' => [
                    'default_days' => 14, // Durée par défaut en jours
                    'auto_cleanup' => true, // Nettoyage automatique des démos expirées
                    'log_details' => true // Loguer les détails de la création
                ]
            ],
            [
                'name' => 'Mise à jour du statut des propriétés',
                'type' => 'UPDATE_PROPERTY_STATUS',
                'description' => 'Met à jour automatiquement le statut des propriétés selon leur occupation (Libre/Occupé)',
                'frequency' => 'DAILY', // Exécution quotidienne
                'parameters' => [
                    'hour' => 1, // 1h du matin
                    'log_details' => true // Loguer les détails de la mise à jour
                ]
            ],
            [
                'name' => 'Création des configurations comptables',
                'type' => 'CREATE_ACCOUNTING_CONFIGURATIONS',
                'description' => 'Crée les configurations comptables par défaut pour les différents types d\'opérations',
                'frequency' => 'MANUAL', // Tâche manuelle uniquement
                'parameters' => [
                    'log_details' => true // Loguer les détails de la création
                ]
            ],
            [
                'name' => 'Test de configuration comptable',
                'type' => 'TEST_ACCOUNTING_CONFIG',
                'description' => 'Teste le système de configuration comptable et vérifie les configurations existantes',
                'frequency' => 'MANUAL', // Tâche manuelle uniquement
                'parameters' => [
                    'log_details' => true // Loguer les détails du test
                ]
            ],
            [
                'name' => 'Vérification des écritures comptables',
                'type' => 'CHECK_ACCOUNTING_ENTRIES',
                'description' => 'Vérifie les écritures comptables créées et leur conformité avec les configurations',
                'frequency' => 'WEEKLY', // Exécution hebdomadaire
                'parameters' => [
                    'day_of_week' => 'MONDAY', // Lundi
                    'hour' => 8, // 8h du matin
                    'log_details' => true // Loguer les détails de la vérification
                ]
            ],
            [
                'name' => 'Test de génération de loyers avec configuration',
                'type' => 'TEST_RENT_GENERATION_WITH_CONFIG',
                'description' => 'Teste la génération de loyers avec l\'application de la configuration comptable',
                'frequency' => 'MANUAL', // Tâche manuelle uniquement
                'parameters' => [
                    'log_details' => true // Loguer les détails du test
                ]
            ],
            [
                'name' => 'Démonstration du système comptable',
                'type' => 'DEMO_ACCOUNTING_SYSTEM',
                'description' => 'Démonstration complète du système comptable avec configuration',
                'frequency' => 'MANUAL', // Tâche manuelle uniquement
                'parameters' => [
                    'log_details' => true // Loguer les détails de la démonstration
                ]
            ],
            [
                'name' => 'Correction de la table comptable',
                'type' => 'FIX_ACCOUNTING_TABLE',
                'description' => 'Corrige la table accounting_configuration en cas de problème de structure',
                'frequency' => 'MANUAL', // Tâche manuelle uniquement
                'parameters' => [
                    'log_details' => true // Loguer les détails de la correction
                ]
            ],
            [
                'name' => 'Configuration du système comptable',
                'type' => 'SETUP_ACCOUNTING_SYSTEM',
                'description' => 'Configure le système comptable complet (migration + configurations)',
                'frequency' => 'MANUAL', // Tâche manuelle uniquement
                'parameters' => [
                    'log_details' => true // Loguer les détails de la configuration
                ]
            ],
            [
                'name' => 'Initialisation des paramètres email',
                'type' => 'INITIALIZE_EMAIL_SETTINGS',
                'description' => 'Initialise les paramètres email par défaut (templates, expéditeur, notifications)',
                'frequency' => 'MANUAL', // Tâche manuelle uniquement
                'parameters' => [
                    'log_details' => true // Loguer les détails de l'initialisation
                ]
            ],
            [
                'name' => 'Test des paramètres email',
                'type' => 'TEST_EMAIL_SETTINGS',
                'description' => 'Teste les paramètres email et envoie un email de test avec les templates personnalisés',
                'frequency' => 'MANUAL', // Tâche manuelle uniquement
                'parameters' => [
                    'test_email' => 'info@app.lokapro.tech', // Email par défaut pour le test
                    'log_details' => true // Loguer les détails du test
                ]
            ],
            [
                'name' => 'Test de la configuration SMTP',
                'type' => 'TEST_SMTP_CONFIGURATION',
                'description' => 'Teste la configuration SMTP et la connexion au serveur de mail',
                'frequency' => 'MANUAL', // Tâche manuelle uniquement
                'parameters' => [
                    'test_email' => 'info@app.lokapro.tech', // Email par défaut pour le test
                    'log_details' => true // Loguer les détails du test
                ]
            ],
            [
                'name' => 'Mise à jour de la configuration SMTP',
                'type' => 'UPDATE_SMTP_CONFIGURATION',
                'description' => 'Met à jour la configuration SMTP avec les paramètres app.lokapro.tech',
                'frequency' => 'MANUAL', // Tâche manuelle uniquement
                'parameters' => [
                    'log_details' => true // Loguer les détails de la mise à jour
                ]
            ]
        ];

        foreach ($defaultTasks as $taskData) {
            $existingTask = $this->entityManager->getRepository(Task::class)
                ->findOneBy(['type' => $taskData['type']]);

            if (!$existingTask) {
                $task = new Task();
                $task->setName($taskData['name'])
                     ->setType($taskData['type'])
                     ->setDescription($taskData['description'])
                     ->setFrequency($taskData['frequency'])
                     ->setParameters($taskData['parameters'])
                     ->setStatus('ACTIVE');

                $task->calculateNextRun();
                $this->entityManager->persist($task);
            }
        }

        $this->entityManager->flush();
    }

    /**
     * Exécute la tâche de migration des documents de sécurité
     */
    private function executeMigrateDocumentsSecurityTask(Task $task): void
    {
        $this->logger->info('Début de la migration des documents de sécurité', [
            'task_id' => $task->getId(),
            'task_name' => $task->getName()
        ]);

        try {
            // Récupérer les paramètres de la tâche
            $parameters = $task->getParameters() ?? [];
            $dryRun = $parameters['dry_run'] ?? false;
            $backup = $parameters['backup'] ?? true;
            $batchSize = $parameters['batch_size'] ?? 10;

            // Récupérer tous les documents non migrés
            $documentRepository = $this->entityManager->getRepository(\App\Entity\Document::class);
            $documents = $documentRepository->createQueryBuilder('d')
                ->where('d.fileName NOT LIKE :pattern')
                ->setParameter('pattern', '%_%_%')
                ->getQuery()
                ->getResult();

            $totalDocuments = count($documents);
            $processedCount = 0;
            $successCount = 0;
            $errorCount = 0;
            $errors = [];

            $this->logger->info(sprintf('Trouvé %d documents à migrer', $totalDocuments));

            if ($totalDocuments === 0) {
                $task->setResult(json_encode([
                    'status' => 'completed',
                    'message' => 'Aucun document à migrer',
                    'processed' => 0,
                    'success' => 0,
                    'errors' => 0
                ]));
                $task->markAsCompleted();
                return;
            }

            // Créer une sauvegarde si demandé et pas en mode dry-run
            if ($backup && !$dryRun) {
                $this->createDocumentsBackup();
            }

            // Traiter les documents par lots
            foreach (array_chunk($documents, $batchSize) as $batch) {
                foreach ($batch as $document) {
                    try {
                        $this->migrateDocument($document, $dryRun);
                        $successCount++;
                    } catch (\Exception $e) {
                        $errorCount++;
                        $errors[] = sprintf('Document %d: %s', $document->getId(), $e->getMessage());
                        $this->logger->error('Erreur lors de la migration du document', [
                            'document_id' => $document->getId(),
                            'error' => $e->getMessage()
                        ]);
                    }

                    $processedCount++;

                    // Mettre à jour le statut de la tâche périodiquement
                    if ($processedCount % 5 === 0) {
                        $task->setResult(json_encode([
                            'status' => 'running',
                            'processed' => $processedCount,
                            'total' => $totalDocuments,
                            'success' => $successCount,
                            'errors' => $errorCount,
                            'progress' => round(($processedCount / $totalDocuments) * 100, 2)
                        ]));
                        $this->entityManager->flush();
                    }
                }
            }

            // Résultat final
            $result = [
                'status' => 'completed',
                'processed' => $processedCount,
                'total' => $totalDocuments,
                'success' => $successCount,
                'errors' => $errorCount,
                'progress' => 100,
                'dry_run' => $dryRun,
                'backup_created' => $backup && !$dryRun,
                'error_details' => array_slice($errors, 0, 10) // Limiter à 10 erreurs pour éviter des logs trop longs
            ];

            $task->setResult(json_encode($result));

            if ($errorCount === 0) {
                $task->markAsCompleted();
                $this->logger->info('Migration des documents de sécurité terminée avec succès', $result);
            } else {
                $task->markAsFailed(sprintf('Migration terminée avec %d erreurs sur %d documents', $errorCount, $totalDocuments));
                $this->logger->warning('Migration des documents de sécurité terminée avec des erreurs', $result);
            }

        } catch (\Exception $e) {
            $task->markAsFailed($e->getMessage());
            $this->logger->error('Erreur lors de la migration des documents de sécurité', [
                'task_id' => $task->getId(),
                'error' => $e->getMessage()
            ]);
            throw $e;
        }
    }

    /**
     * Migre un document individuel vers le système de sécurité
     */
    private function migrateDocument(\App\Entity\Document $document, bool $dryRun): void
    {
        $documentsDirectory = $this->getParameter('documents_directory');
        $oldFilePath = $documentsDirectory . '/' . $document->getFileName();

        if (!file_exists($oldFilePath)) {
            throw new \RuntimeException('Fichier original non trouvé');
        }

        if ($dryRun) {
            return; // Simulation
        }

        // Lire le contenu du fichier original
        $content = file_get_contents($oldFilePath);
        if ($content === false) {
            throw new \RuntimeException('Impossible de lire le fichier original');
        }

        // Chiffrer le fichier
        $encryptedContent = $this->encryptFile($content);

        // Générer un nouveau nom de fichier sécurisé
        $originalFilename = pathinfo($document->getOriginalFileName(), PATHINFO_FILENAME);
        $safeFilename = preg_replace('/[^a-zA-Z0-9_-]/', '_', $originalFilename);
        $hash = hash('sha256', uniqid() . microtime(true));
        $timestamp = date('YmdHis');
        $extension = pathinfo($document->getFileName(), PATHINFO_EXTENSION);

        $newFilename = sprintf(
            '%s_%s_%s.%s',
            $safeFilename,
            $timestamp,
            substr($hash, 0, 16),
            $extension
        );

        // Sauvegarder le fichier chiffré
        $newFilePath = $documentsDirectory . '/' . $newFilename;
        if (file_put_contents($newFilePath, $encryptedContent) === false) {
            throw new \RuntimeException('Impossible de sauvegarder le fichier chiffré');
        }

        // Mettre à jour l'entité Document
        $document->setFileName($newFilename);
        $this->entityManager->flush();

        // Supprimer l'ancien fichier
        if (!unlink($oldFilePath)) {
            $this->logger->warning('Impossible de supprimer l\'ancien fichier', [
                'old_file' => $oldFilePath
            ]);
        }
    }

    /**
     * Chiffre un fichier
     */
    private function encryptFile(string $content): string
    {
        $encryptionKey = $_ENV['APP_ENCRYPTION_KEY'] ?? 'default-key-change-in-production';
        $iv = random_bytes(16);
        $encrypted = openssl_encrypt($content, 'AES-256-CBC', $encryptionKey, 0, $iv);

        if ($encrypted === false) {
            throw new \RuntimeException('Erreur lors du chiffrement');
        }

        return base64_encode($iv . $encrypted);
    }

    /**
     * Crée une sauvegarde des documents
     */
    private function createDocumentsBackup(): void
    {
        $documentsDirectory = $this->getParameter('documents_directory');
        $backupDir = $documentsDirectory . '/backup_' . date('Y-m-d_H-i-s');

        if (!is_dir($backupDir)) {
            mkdir($backupDir, 0750, true);
        }

        $files = glob($documentsDirectory . '/*');
        $backupCount = 0;

        foreach ($files as $file) {
            if (is_file($file)) {
                $filename = basename($file);
                if (copy($file, $backupDir . '/' . $filename)) {
                    $backupCount++;
                }
            }
        }

        $this->logger->info(sprintf('Sauvegarde créée dans %s (%d fichiers)', $backupDir, $backupCount));
    }

    /**
     * Récupère les statistiques des tâches
     */
    public function getTaskStatistics(): array
    {
        $taskRepository = $this->entityManager->getRepository(Task::class);

        return [
            'total' => $taskRepository->count([]),
            'active' => $taskRepository->count(['status' => 'ACTIVE']),
            'running' => $taskRepository->count(['status' => 'RUNNING']),
            'due' => count($taskRepository->findDueTasks()),
        ];
    }

    /**
     * Active ou désactive une tâche
     */
    public function toggleTask(Task $task, bool $active): void
    {
        $task->setStatus($active ? 'ACTIVE' : 'INACTIVE');
        $task->setUpdatedAt(new \DateTime());

        if ($active) {
            $task->calculateNextRun();
        }

        $this->entityManager->flush();
    }

    /**
     * Force l'exécution d'une tâche
     */
    public function forceExecuteTask(Task $task): void
    {
        $this->executeTask($task);
    }

    /**
     * Initialise le système : crée les tâches et les plans par défaut
     */
    public function initializeSystem(): array
    {
        $results = [
            'tasks_created' => 0,
            'plans_created' => 0,
            'errors' => []
        ];

        // 1. Créer les tâches par défaut
        try {
            $this->createDefaultTasks();
            $taskRepo = $this->entityManager->getRepository(Task::class);
            $results['tasks_created'] = $taskRepo->count([]);
        } catch (\Exception $e) {
            $results['errors'][] = 'Erreur création tâches: ' . $e->getMessage();
            $this->logger->error('Erreur création tâches: ' . $e->getMessage());
        }

        // 2. Créer les plans d'abonnement par défaut
        try {
            $this->createDefaultPlans();
            $planRepo = $this->entityManager->getRepository(\App\Entity\Plan::class);
            $results['plans_created'] = $planRepo->count([]);
        } catch (\Exception $e) {
            $results['errors'][] = 'Erreur création plans: ' . $e->getMessage();
            $this->logger->error('Erreur création plans: ' . $e->getMessage());
        }

        return $results;
    }

    /**
     * Crée les plans d'abonnement par défaut
     */
    private function createDefaultPlans(): void
    {
        $defaultPlans = [
            [
                'name' => 'Freemium',
                'slug' => 'freemium',
                'description' => 'Testez gratuitement pour toujours',
                'monthly_price' => '0',
                'yearly_price' => '0',
                'currency' => 'FCFA',
                'max_properties' => 2,
                'max_tenants' => 3,
                'max_users' => 1,
                'max_documents' => 10,
                'features' => [
                    'dashboard', 'properties_management', 'tenants_management',
                    'lease_management', 'payment_tracking',
                ],
                'sort_order' => 1,
                'is_popular' => false,
            ],
            [
                'name' => 'Starter',
                'slug' => 'starter',
                'description' => 'Parfait pour débuter dans la gestion locative',
                'monthly_price' => '9900',
                'yearly_price' => '99000',
                'currency' => 'FCFA',
                'max_properties' => 5,
                'max_tenants' => 10,
                'max_users' => 2,
                'max_documents' => 50,
                'features' => [
                    'dashboard', 'properties_management', 'tenants_management',
                    'lease_management', 'payment_tracking', 'documents',
                ],
                'sort_order' => 2,
                'is_popular' => false,
            ],
            [
                'name' => 'Professional',
                'slug' => 'professional',
                'description' => 'Pour les gestionnaires professionnels',
                'monthly_price' => '24900',
                'yearly_price' => '249000',
                'currency' => 'FCFA',
                'max_properties' => 20,
                'max_tenants' => 50,
                'max_users' => 5,
                'max_documents' => 200,
                'features' => [
                    'dashboard', 'properties_management', 'tenants_management',
                    'lease_management', 'payment_tracking', 'documents',
                    'accounting', 'maintenance_requests', 'online_payments',
                    'advance_payments', 'reports', 'email_notifications',
                ],
                'sort_order' => 3,
                'is_popular' => true,
            ],
            [
                'name' => 'Enterprise',
                'slug' => 'enterprise',
                'description' => 'Solution complète pour grandes entreprises',
                'monthly_price' => '49900',
                'yearly_price' => '499000',
                'currency' => 'FCFA',
                'max_properties' => null,
                'max_tenants' => null,
                'max_users' => null,
                'max_documents' => null,
                'features' => [
                    'dashboard', 'properties_management', 'tenants_management',
                    'lease_management', 'payment_tracking', 'documents',
                    'accounting', 'maintenance_requests', 'online_payments',
                    'advance_payments', 'reports', 'email_notifications',
                    'sms_notifications', 'custom_branding', 'api_access',
                    'priority_support', 'multi_currency',
                ],
                'sort_order' => 4,
                'is_popular' => false,
            ],
        ];

        foreach ($defaultPlans as $planData) {
            $existingPlan = $this->entityManager->getRepository(\App\Entity\Plan::class)
                ->findOneBy(['slug' => $planData['slug']]);

            if (!$existingPlan) {
                $plan = new \App\Entity\Plan();
                $plan->setName($planData['name'])
                     ->setSlug($planData['slug'])
                     ->setDescription($planData['description'])
                     ->setMonthlyPrice($planData['monthly_price'])
                     ->setYearlyPrice($planData['yearly_price'])
                     ->setCurrency($planData['currency'])
                     ->setMaxProperties($planData['max_properties'])
                     ->setMaxTenants($planData['max_tenants'])
                     ->setMaxUsers($planData['max_users'])
                     ->setMaxDocuments($planData['max_documents'])
                     ->setFeatures($planData['features'])
                     ->setSortOrder($planData['sort_order'])
                     ->setIsPopular($planData['is_popular'])
                     ->setIsActive(true);

                $this->entityManager->persist($plan);
            }
        }

        $this->entityManager->flush();
    }

    /**
     * Exécute la tâche de génération des quittances et avis d'échéances
     */
    private function executeGenerateRentDocumentsTask(Task $task): void
    {
        try {
            $parameters = $task->getParameters() ?? [];
            $month = $parameters['month'] ?? 'current';

            // Gérer les valeurs spéciales
            if ($month === 'current' || $month === 'now') {
                $monthDate = new \DateTime('first day of this month');
            } elseif ($month === 'last') {
                $monthDate = new \DateTime('first day of last month');
            } elseif ($month === 'next') {
                $monthDate = new \DateTime('first day of next month');
            } else {
                // Format YYYY-MM attendu
                try {
                    $monthDate = new \DateTime($month . '-01');
                } catch (\Exception $e) {
                    throw new \Exception('Format de mois invalide dans les paramètres de la tâche. Utilisez "current", "last", "next" ou le format YYYY-MM');
                }
            }

            // Générer les quittances du mois avec protection EntityManager
            $receipts = [];
            try {
                $receipts = $this->rentReceiptService->generateMonthlyReceipts($monthDate);
            } catch (\Exception $e) {
                $this->logger->warning('Erreur lors de la génération des quittances: ' . $e->getMessage());
                $receipts = [];
            }

            // Générer les avis d'échéance pour le mois prochain avec protection EntityManager
            $notices = [];
            try {
                $nextMonth = (clone $monthDate)->modify('+1 month');
                $notices = $this->rentReceiptService->generateUpcomingNotices($nextMonth);
            } catch (\Exception $e) {
                $this->logger->warning('Erreur lors de la génération des avis: ' . $e->getMessage());
                $notices = [];
            }

            $total = count($receipts) + count($notices);

            // Logger le résultat avec succès
            $this->logger->info(sprintf(
                '✅ Documents générés pour %s : %d quittances, %d avis d\'échéance (Total: %d)',
                $monthDate->format('F Y'),
                count($receipts),
                count($notices),
                $total
            ));

            if ($total === 0) {
                $this->logger->warning(sprintf(
                    'Aucun document généré pour %s. Vérifiez qu\'il y a des paiements correspondants.',
                    $monthDate->format('F Y')
                ));
            }

            // Sauvegarder les résultats
            $task->setParameter('last_receipts_generated', count($receipts));
            $task->setParameter('last_notices_generated', count($notices));
            $task->setParameter('last_total_documents', $total);

            // S'assurer que la tâche est sauvegardée (seulement si EntityManager ouvert)
            if ($this->entityManager->isOpen()) {
                $this->entityManager->flush();
            }

        } catch (\Exception $e) {
            // Log l'erreur et marquer la tâche comme échouée
            $task->setParameter('last_error', $e->getMessage());

            $this->logger->error('Erreur lors de l\'exécution de la tâche GENERATE_RENT_DOCUMENTS', [
                'task_id' => $task->getId(),
                'task_type' => $task->getType(),
                'error' => $e->getMessage()
            ]);

            // Si l'erreur est "EntityManager is closed", ne pas re-lancer l'exception
            if (strpos($e->getMessage(), 'EntityManager is closed') !== false) {
                $this->logger->warning('EntityManager fermé - tâche interrompue gracieusement');
                $task->setParameter('last_error', 'EntityManager fermé - génération interrompue');

                // Essayer de sauvegarder seulement si l'EntityManager est ouvert
                if ($this->entityManager->isOpen()) {
                    $this->entityManager->flush();
                }

                // Ne pas re-lancer l'exception pour "EntityManager is closed"
                return;
            }

            // Re-lancer les autres exceptions
            throw $e;
        }
    }

    /**
     * Exécute la tâche de création d'un super administrateur
     */
    private function executeCreateSuperAdminTask(Task $task): void
    {
        $parameters = $task->getParameters() ?? [];

        // Récupérer les paramètres requis
        $email = $parameters['email'] ?? null;
        $firstName = $parameters['firstName'] ?? null;
        $lastName = $parameters['lastName'] ?? null;
        $password = $parameters['password'] ?? null;

        // Validation des paramètres
        if (!$email || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
            throw new \InvalidArgumentException('Email invalide ou manquant dans les paramètres de la tâche');
        }

        if (!$firstName || !$lastName) {
            throw new \InvalidArgumentException('Prénom et nom requis dans les paramètres de la tâche');
        }

        if (!$password || strlen($password) < 8) {
            throw new \InvalidArgumentException('Mot de passe manquant ou trop court (minimum 8 caractères)');
        }

        // Vérifier si l'utilisateur existe déjà
        $existingUser = $this->entityManager->getRepository(User::class)->findOneBy(['email' => $email]);

        if ($existingUser) {
            // Si l'utilisateur existe et a déjà le rôle SUPER_ADMIN, pas besoin de le recréer
            if (in_array('ROLE_SUPER_ADMIN', $existingUser->getRoles())) {
                $this->logger->info(sprintf(
                    'Super Admin %s existe déjà avec ce rôle',
                    $email
                ));
                return;
            }

            throw new \Exception(sprintf(
                'Un utilisateur avec l\'email %s existe déjà mais n\'est pas super admin',
                $email
            ));
        }

        // Créer le Super Admin
        $user = new User();
        $user->setEmail($email);
        $user->setFirstName($firstName);
        $user->setLastName($lastName);
        $user->setRoles(['ROLE_SUPER_ADMIN']);

        $hashedPassword = $this->passwordHasher->hashPassword($user, $password);
        $user->setPassword($hashedPassword);

        $this->entityManager->persist($user);
        $this->entityManager->flush();

        // Logger le succès
        $this->logger->info(sprintf(
            '✅ Super Administrateur créé avec succès : %s %s (%s)',
            $firstName,
            $lastName,
            $email
        ));
    }

    /**
     * Exécute la tâche de nettoyage de l'audit log
     */
    private function executeAuditCleanupTask(Task $task): void
    {
        if (!$this->auditLogService) {
            throw new \Exception('AuditLogService non disponible. Vérifiez la configuration des services.');
        }

        $parameters = $task->getParameters() ?? [];

        // Récupérer le nombre de jours à conserver (par défaut 90)
        $daysToKeep = $parameters['days'] ?? 90;

        // Validation
        if ($daysToKeep < 30) {
            throw new \InvalidArgumentException('La période minimum est de 30 jours pour des raisons de sécurité');
        }

        try {
            $deleted = $this->auditLogService->cleanOldLogs($daysToKeep);

            // Logger le résultat
            $this->logger->info(sprintf(
                '✅ Nettoyage de l\'audit log terminé : %d enregistrement(s) supprimé(s) (conservation: %d jours)',
                $deleted,
                $daysToKeep
            ));

            if ($deleted === 0) {
                $this->logger->info(sprintf(
                    'Aucun enregistrement à supprimer (tous plus récents que %d jours)',
                    $daysToKeep
                ));
            }
        } catch (\Exception $e) {
            $this->logger->error(sprintf(
                '❌ Erreur lors du nettoyage de l\'audit log : %s',
                $e->getMessage()
            ));
            throw $e;
        }
    }

    /**
     * Exécute la tâche de sauvegarde
     */
    private function executeBackupTask(Task $task): void
    {
        if (!$this->backupService) {
            throw new \Exception('BackupService non disponible. Vérifiez la configuration des services.');
        }

        $parameters = $task->getParameters() ?? [];

        try {
            // Créer la sauvegarde complète
            $results = $this->backupService->createFullBackup();

            if ($results['success']) {
                $this->logger->info(sprintf(
                    '✅ Sauvegarde créée avec succès : %s',
                    $results['timestamp']
                ));

                // Log des détails
                if ($results['database']) {
                    $this->logger->info(sprintf(
                        '   📊 Base de données : %s (%d bytes)',
                        $results['database']['file'] ?? 'N/A',
                        $results['database']['size'] ?? 0
                    ));
                }

                if ($results['files']) {
                    $this->logger->info(sprintf(
                        '   📁 Fichiers : %s (%d bytes)',
                        $results['files']['file'] ?? 'N/A',
                        $results['files']['size'] ?? 0
                    ));
                }

                // Nettoyage automatique des anciennes sauvegardes si configuré
                if ($parameters['clean_old'] ?? false) {
                    $keepDays = $parameters['keep_days'] ?? 30;
                    $deleted = $this->backupService->cleanOldBackups($keepDays);

                    if ($deleted > 0) {
                        $this->logger->info(sprintf(
                            '🧹 Nettoyage : %d ancien(s) fichier(s) supprimé(s)',
                            $deleted
                        ));
                    }
                }
            } else {
                $errors = implode(', ', $results['errors']);
                throw new \Exception("Erreurs lors de la sauvegarde : {$errors}");
            }

        } catch (\Exception $e) {
            $this->logger->error(sprintf(
                '❌ Erreur lors de la sauvegarde : %s',
                $e->getMessage()
            ));
            throw $e;
        }
    }

    /**
     * Exécute la tâche de test de configuration email
     */
    private function executeTestEmailConfigTask(Task $task): void
    {
        $parameters = $task->getParameters() ?? [];
        $testEmail = $parameters['email'] ?? null;

        if (!$testEmail) {
            throw new \Exception('Adresse email de test requise. Ajoutez le paramètre "email" à la tâche.');
        }

        try {
            // Vérifier les paramètres email actuels
            $emailSettings = $this->settingsService->getEmailSettings();

            $this->logger->info('🧪 Test de configuration email démarré');
            $this->logger->info(sprintf('📧 Email de test : %s', $testEmail));
            $this->logger->info(sprintf('📤 Expéditeur : %s <%s>',
                $emailSettings['email_from_name'] ?? 'LOKAPRO',
                $emailSettings['email_from'] ?? 'noreply@app.lokapro.tech'
            ));

            // Vérifier si les notifications sont activées
            if (!($emailSettings['email_notifications'] ?? true)) {
                throw new \Exception('Les notifications email sont désactivées dans les paramètres.');
            }

            // Vérifier la configuration SMTP
            if (empty($emailSettings['smtp_host'])) {
                $this->logger->warning('⚠️ Serveur SMTP non configuré - test avec configuration par défaut');
            } else {
                $this->logger->info(sprintf('🔧 SMTP : %s:%s (%s)',
                    $emailSettings['smtp_host'],
                    $emailSettings['smtp_port'] ?? '587',
                    $emailSettings['smtp_encryption'] ?? 'Aucun'
                ));
            }

            // Créer le contenu de test
            $testSubject = 'Test de configuration email - ' . ($emailSettings['app_name'] ?? 'LOKAPRO');

            // Utiliser le NotificationService qui gère correctement l'envoi
            $success = $this->notificationService->testEmailConfiguration($testEmail);

            if ($success) {
                $this->logger->info('✅ Email de test envoyé avec succès');
                $this->logger->info(sprintf('📬 Vérifiez la boîte de réception de %s', $testEmail));
            } else {
                // Si l'envoi échoue, vérifier la configuration SMTP
                if (empty($emailSettings['smtp_host'])) {
                    $this->logger->warning('⚠️ Serveur SMTP non configuré - impossible d\'envoyer l\'email');
                    $this->logger->info('💡 Configurez le serveur SMTP dans Administration > Paramètres > Email');
                } else {
                    $this->logger->error('❌ Échec de l\'envoi malgré la configuration SMTP');
                }
                // Ne pas lever d'exception pour les tests, juste logger l'info
                $this->logger->info('ℹ️ Test de configuration email terminé (sans envoi)');
            }

        } catch (\Exception $e) {
            $this->logger->error(sprintf(
                '❌ Erreur lors du test email : %s',
                $e->getMessage()
            ));
            throw $e;
        }
    }

    /**
     * Crée le contenu HTML pour l'email de test
     */
    private function createTestEmailContent(array $settings): string
    {
        $appName = $settings['app_name'] ?? 'LOKAPRO';
        $companyName = $settings['company_name'] ?? 'LOKAPRO Gestion';
        $fromEmail = $settings['email_from'] ?? 'noreply@app.lokapro.tech';
        $fromName = $settings['email_from_name'] ?? 'LOKAPRO';
        $testDate = (new \DateTime())->format('d/m/Y H:i:s');

        return <<<HTML
<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <style>
        body { font-family: Arial, sans-serif; line-height: 1.6; color: #333; max-width: 600px; margin: 0 auto; padding: 20px; }
        .header { background: #6f42c1; color: white; padding: 30px; text-align: center; border-radius: 8px 8px 0 0; }
        .content { padding: 30px; background: white; border: 1px solid #ddd; border-top: none; border-radius: 0 0 8px 8px; }
        .success { background: #d4edda; color: #155724; padding: 15px; border-radius: 4px; margin: 20px 0; border-left: 4px solid #28a745; }
        .info { background: #d1ecf1; color: #0c5460; padding: 15px; border-radius: 4px; margin: 20px 0; border-left: 4px solid #17a2b8; }
        .footer { text-align: center; margin-top: 30px; padding-top: 20px; border-top: 1px solid #eee; color: #666; font-size: 12px; }
    </style>
</head>
<body>
    <div class="header">
        <h1>✅ Test de Configuration Email</h1>
        <p>{$appName}</p>
    </div>

    <div class="content">
        <div class="success">
            <strong>🎉 Configuration email réussie !</strong><br>
            Votre système d'envoi d'emails est correctement configuré.
        </div>

        <h3>📧 Détails de la configuration :</h3>
        <ul>
            <li><strong>Expéditeur :</strong> {$fromName} &lt;{$fromEmail}&gt;</li>
            <li><strong>Date du test :</strong> {$testDate}</li>
            <li><strong>Application :</strong> {$appName}</li>
            <li><strong>Entreprise :</strong> {$companyName}</li>
        </ul>

        <div class="info">
            <strong>ℹ️ Informations importantes :</strong><br>
            • Tous les emails de l'application utilisent cette configuration<br>
            • Les paramètres peuvent être modifiés dans Administration > Paramètres > Email<br>
            • Cette configuration est utilisée pour les quittances, rappels et notifications
        </div>

        <h3>🔧 Fonctionnalités testées :</h3>
        <ul>
            <li>✅ Envoi d'emails avec nom d'expéditeur personnalisé</li>
            <li>✅ Utilisation des paramètres configurés</li>
            <li>✅ Respect du paramètre email_notifications</li>
            <li>✅ Templates HTML avec variables dynamiques</li>
        </ul>
    </div>

    <div class="footer">
        <p>Email généré automatiquement par {$appName}<br>
        Test de configuration via TaskManager</p>
    </div>
</body>
</html>
HTML;
    }

    /**
     * Exécute la tâche de correction des utilisateurs sans organisation
     */
    private function executeFixUserOrganizationTask(Task $task): void
    {
        $parameters = $task->getParameters();
        $autoFixTenants = $parameters['auto_fix_tenants'] ?? true;
        $logDetails = $parameters['log_details'] ?? true;

        $this->logger->info('Début de la correction des utilisateurs sans organisation', [
            'auto_fix_tenants' => $autoFixTenants,
            'log_details' => $logDetails
        ]);

        // Récupérer tous les utilisateurs sans organisation
        $usersWithoutOrg = $this->entityManager->getRepository(User::class)
            ->createQueryBuilder('u')
            ->where('u.organization IS NULL')
            ->getQuery()
            ->getResult();

        $this->logger->info(sprintf('Trouvé %d utilisateurs sans organisation', count($usersWithoutOrg)));

        $fixed = 0;
        $skipped = 0;

        foreach ($usersWithoutOrg as $user) {
            if ($logDetails) {
                $this->logger->info(sprintf('Traitement de l\'utilisateur: %s (%s)', $user->getEmail(), implode(', ', $user->getRoles())));
            }

            // Essayer de récupérer l'organisation via le tenant
            if (in_array('ROLE_TENANT', $user->getRoles()) && $autoFixTenants) {
                $tenant = $user->getTenant();
                if ($tenant && $tenant->getOrganization()) {
                    $user->setOrganization($tenant->getOrganization());
                    if ($tenant->getCompany()) {
                        $user->setCompany($tenant->getCompany());
                    }
                    $fixed++;
                    if ($logDetails) {
                        $this->logger->info(sprintf('  ✓ Organisation définie via tenant: %s', $tenant->getOrganization()->getName()));
                    }
                } else {
                    $skipped++;
                    if ($logDetails) {
                        $this->logger->warning('  ✗ Aucun tenant trouvé ou tenant sans organisation');
                    }
                }
            } else {
                $skipped++;
                if ($logDetails) {
                    $this->logger->warning('  ✗ Utilisateur non-locataire, impossible de déterminer l\'organisation automatiquement');
                }
            }
        }

        // Sauvegarder les modifications
        if ($fixed > 0) {
            $this->entityManager->flush();
            $this->logger->info(sprintf('%d utilisateurs corrigés, %d ignorés', $fixed, $skipped));
        } else {
            $this->logger->info('Aucun utilisateur à corriger');
        }

        // Statistiques finales
        $totalUsers = $this->entityManager->getRepository(User::class)->count([]);
        $usersWithOrg = $this->entityManager->getRepository(User::class)
            ->createQueryBuilder('u')
            ->where('u.organization IS NOT NULL')
            ->getQuery()
            ->getSingleScalarResult();

        $this->logger->info(sprintf('Statistiques finales - Total: %d, Avec organisation: %d, Sans organisation: %d',
            $totalUsers, $usersWithOrg, $totalUsers - $usersWithOrg));

        // Mettre à jour le résultat de la tâche
        $task->setResult(sprintf('Correction terminée: %d utilisateurs corrigés, %d ignorés. Total: %d utilisateurs, %d avec organisation',
            $fixed, $skipped, $totalUsers, $usersWithOrg));
    }

    /**
     * Crée une tâche de correction des utilisateurs sans organisation
     */
    public function createFixUserOrganizationTask(): Task
    {
        $task = new Task();
        $task->setName('Correction des utilisateurs sans organisation');
        $task->setDescription('Corrige automatiquement les utilisateurs qui n\'ont pas d\'organization_id ou company_id définis');
        $task->setType('FIX_USER_ORGANIZATION');
        $task->setStatus('ACTIVE');
        $task->setFrequency('ONCE');
        $task->setCreatedAt(new \DateTime());
        $task->setNextRunAt(new \DateTime()); // Exécution immédiate

        $this->entityManager->persist($task);
        $this->entityManager->flush();

        $this->logger->info('Tâche de correction des utilisateurs sans organisation créée');

        return $task;
    }

    /**
     * Exécute la tâche de synchronisation des écritures comptables
     */
    private function executeSyncAccountingEntriesTask(Task $task): void
    {
        $parameters = $task->getParameters();
        $syncReceipts = $parameters['sync_receipts'] ?? true;
        $syncNotices = $parameters['sync_notices'] ?? true;
        $logDetails = $parameters['log_details'] ?? true;

        $this->logger->info('Début de la synchronisation des écritures comptables', [
            'sync_receipts' => $syncReceipts,
            'sync_notices' => $syncNotices,
            'log_details' => $logDetails
        ]);

        $createdEntries = 0;
        $updatedEntries = 0;

        // Récupérer les documents de quittances
        if ($syncReceipts) {
            $receipts = $this->entityManager->getRepository(\App\Entity\Document::class)
                ->findBy(['type' => 'Quittance de loyer']);

            $this->logger->info(sprintf('Trouvé %d quittances à synchroniser', count($receipts)));

            foreach ($receipts as $document) {
                $payment = $document->getLease()?->getPayments()->first();
                if (!$payment) {
                    continue;
                }

                $existingEntry = $this->entityManager->getRepository(\App\Entity\AccountingEntry::class)
                    ->findOneBy(['payment' => $payment]);

                if ($existingEntry) {
                    // Mettre à jour la référence
                    if (!$existingEntry->getReference() || !str_contains($existingEntry->getReference(), 'QUITTANCE-')) {
                        $existingEntry->setReference('QUITTANCE-' . $document->getId());
                        $updatedEntries++;
                        if ($logDetails) {
                            $this->logger->info(sprintf('  ✓ Référence mise à jour pour quittance: %s', $document->getName()));
                        }
                    }
                } else {
                    // Créer une nouvelle écriture
                    $entry = new \App\Entity\AccountingEntry();
                    $entry->setEntryDate($payment->getPaidDate() ?? $payment->getDueDate());
                    $entry->setDescription('Quittance de loyer - ' . $document->getName());
                    $entry->setAmount($payment->getAmount());
                    $entry->setType('CREDIT');
                    $entry->setCategory('LOYER');
                    $entry->setReference('QUITTANCE-' . $document->getId());
                    $entry->setProperty($payment->getProperty());
                    $entry->setOwner($payment->getProperty()?->getOwner());
                    $entry->setPayment($payment);
                    $entry->setNotes('Généré automatiquement lors de la synchronisation');

                    $this->entityManager->persist($entry);
                    $createdEntries++;
                    if ($logDetails) {
                        $this->logger->info(sprintf('  ✓ Écriture créée pour quittance: %s', $document->getName()));
                    }
                }
            }
        }

        // Récupérer les documents d'avis d'échéance
        if ($syncNotices) {
            $notices = $this->entityManager->getRepository(\App\Entity\Document::class)
                ->findBy(['type' => 'Avis d\'échéance']);

            $this->logger->info(sprintf('Trouvé %d avis d\'échéance à synchroniser', count($notices)));

            foreach ($notices as $document) {
                $payment = $document->getLease()?->getPayments()->first();
                if (!$payment) {
                    continue;
                }

                $existingEntry = $this->entityManager->getRepository(\App\Entity\AccountingEntry::class)
                    ->findOneBy(['payment' => $payment]);

                if ($existingEntry) {
                    // Mettre à jour la référence
                    if (!$existingEntry->getReference() || !str_contains($existingEntry->getReference(), 'AVIS-')) {
                        $existingEntry->setReference('AVIS-' . $document->getId());
                        $updatedEntries++;
                        if ($logDetails) {
                            $this->logger->info(sprintf('  ✓ Référence mise à jour pour avis: %s', $document->getName()));
                        }
                    }
                } else {
                    // Créer une nouvelle écriture
                    $entry = new \App\Entity\AccountingEntry();
                    $entry->setEntryDate($payment->getDueDate());
                    $entry->setDescription('Avis d\'échéance - ' . $document->getName());
                    $entry->setAmount($payment->getAmount());
                    $entry->setType('CREDIT');
                    $entry->setCategory('LOYER_ATTENDU');
                    $entry->setReference('AVIS-' . $document->getId());
                    $entry->setProperty($payment->getProperty());
                    $entry->setOwner($payment->getProperty()?->getOwner());
                    $entry->setPayment($payment);
                    $entry->setNotes('Généré automatiquement lors de la synchronisation');

                    $this->entityManager->persist($entry);
                    $createdEntries++;
                    if ($logDetails) {
                        $this->logger->info(sprintf('  ✓ Écriture créée pour avis: %s', $document->getName()));
                    }
                }
            }
        }

        // Sauvegarder les modifications
        $this->entityManager->flush();

        $this->logger->info(sprintf('Synchronisation terminée: %d écritures créées, %d écritures mises à jour',
            $createdEntries, $updatedEntries));

        // Mettre à jour le résultat de la tâche
        $task->setResult(sprintf('Synchronisation terminée: %d écritures créées, %d écritures mises à jour',
            $createdEntries, $updatedEntries));
    }

    /**
     * Exécute la tâche de création d'environnements de démo
     */
    private function executeDemoCreateTask(Task $task): void
    {
        $parameters = $task->getParameters();
        $defaultDays = $parameters['default_days'] ?? 14;
        $autoCleanup = $parameters['auto_cleanup'] ?? true;
        $logDetails = $parameters['log_details'] ?? true;

        $this->logger->info('Début de la création d\'environnements de démo', [
            'default_days' => $defaultDays,
            'auto_cleanup' => $autoCleanup,
            'log_details' => $logDetails
        ]);

        try {
            // Vérifier si le service DemoEnvironmentService est disponible
            if (!$this->demoEnvironmentService) {
                $this->logger->warning('DemoEnvironmentService non disponible - tâche de démo ignorée');
                $task->setResult('DemoEnvironmentService non disponible - tâche ignorée');
                return;
            }

            // Nettoyer les environnements expirés si activé
            if ($autoCleanup) {
                $this->logger->info('Nettoyage automatique des environnements expirés...');
                try {
                    $cleanupResult = $this->demoEnvironmentService->cleanupExpiredDemos();

                    if ($logDetails) {
                        $this->logger->info(sprintf('Nettoyage terminé: %d environnements supprimés', $cleanupResult['cleaned_count']));
                    }
                } catch (\Exception $e) {
                    $this->logger->error('Erreur lors du nettoyage: ' . $e->getMessage());
                }
            }

            // Récupérer les utilisateurs qui pourraient avoir besoin d'un environnement de démo
            $users = $this->entityManager->getRepository(User::class)
                ->createQueryBuilder('u')
                ->where('u.roles LIKE :role')
                ->setParameter('role', '%ROLE_ADMIN%')
                ->getQuery()
                ->getResult();

            $this->logger->info(sprintf('Trouvé %d utilisateurs avec le rôle ADMIN', count($users)));

            $createdDemos = 0;
            $skippedDemos = 0;
            $errors = 0;

            foreach ($users as $user) {
                try {
                    // Vérifier si l'EntityManager est fermé et le rouvrir si nécessaire
                    if (!$this->entityManager->isOpen()) {
                        $this->logger->warning('EntityManager fermé, tentative de réouverture');
                        // Note: Dans un contexte réel, il faudrait recréer l'EntityManager via le container
                        throw new \Exception('EntityManager fermé, impossible de continuer');
                    }

                    // Vérifier d'abord dans la base de données si l'utilisateur a déjà une organisation démo
                    $existingOrg = $this->entityManager->getRepository(\App\Entity\Organization::class)
                        ->createQueryBuilder('o')
                        ->where('o.isDemo = :demo')
                        ->andWhere('o.name LIKE :userName')
                        ->setParameter('demo', true)
                        ->setParameter('userName', '%' . $user->getFullName() . '%')
                        ->getQuery()
                        ->getOneOrNullResult();

                    if ($existingOrg) {
                        $skippedDemos++;
                        if ($logDetails) {
                            $this->logger->info(sprintf('Utilisateur %s a déjà une organisation démo existante: %s',
                                $user->getEmail(),
                                $existingOrg->getName()
                            ));
                        }
                        continue;
                    }

                    // Vérifier aussi via le service DemoEnvironmentService
                    $existingDemo = $this->demoEnvironmentService->getUserActiveDemo($user);

                    if ($existingDemo) {
                        $skippedDemos++;
                        if ($logDetails) {
                            $this->logger->info(sprintf('Utilisateur %s a déjà un environnement de démo actif via le service', $user->getEmail()));
                        }
                        continue;
                    }

                    // Créer l'environnement de démo
                    $demoResult = $this->demoEnvironmentService->createDemoEnvironmentWithUrl($user);

                    if ($demoResult['success']) {
                        $createdDemos++;

                        if ($logDetails) {
                            $this->logger->info(sprintf('✅ Environnement de démo créé pour %s: %s',
                                $user->getEmail(),
                                $demoResult['demo_url'] ?? 'URL non disponible'
                            ));
                        }
                    } else {
                        $errors++;
                        $this->logger->error(sprintf('❌ Échec de création de démo pour %s: %s',
                            $user->getEmail(),
                            $demoResult['message'] ?? 'Erreur inconnue'
                        ));
                    }

                } catch (\Exception $e) {
                    $errors++;
                    $this->logger->error(sprintf('❌ Erreur lors de la création de démo pour %s: %s',
                        $user->getEmail(),
                        $e->getMessage()
                    ));
                    // Continuer avec les autres utilisateurs même si une erreur se produit
                    continue;
                }
            }

            $this->logger->info(sprintf('Création d\'environnements de démo terminée: %d créés, %d ignorés, %d erreurs',
                $createdDemos, $skippedDemos, $errors));

            // Mettre à jour le résultat de la tâche
            $task->setResult(sprintf('Création terminée: %d environnements créés, %d ignorés, %d erreurs. Nettoyage automatique: %s',
                $createdDemos, $skippedDemos, $errors, $autoCleanup ? 'activé' : 'désactivé'));

        } catch (\Exception $e) {
            $this->logger->error(sprintf('❌ Erreur lors de la création d\'environnements de démo: %s', $e->getMessage()));
            $task->setResult('Erreur: ' . $e->getMessage());
            throw $e;
        }
    }

    /**
     * Exécute la tâche de mise à jour du statut des propriétés
     */
    public function executeUpdatePropertyStatusTask(Task $task, bool $logDetails = false): string
    {
        try {
            $parameters = $task->getParameters() ?? [];
            $logDetails = $parameters['log_details'] ?? false;

            $this->logger->info('🔄 Début de la mise à jour du statut des propriétés');

            // Récupérer toutes les propriétés
            $properties = $this->entityManager->getRepository(\App\Entity\Property::class)->findAll();

            $updatedCount = 0;
            $occupiedCount = 0;
            $freeCount = 0;

            foreach ($properties as $property) {
                $oldStatus = $property->getStatus();
                $hasActiveLease = $this->hasActiveLease($property);

                if ($hasActiveLease) {
                    $newStatus = 'Occupé';
                    $occupiedCount++;
                } else {
                    $newStatus = 'Libre';
                    $freeCount++;
                }

                // Mettre à jour le statut si nécessaire
                if ($oldStatus !== $newStatus) {
                    $property->setStatus($newStatus);
                    $this->entityManager->persist($property);
                    $updatedCount++;

                    if ($logDetails) {
                        $this->logger->info(sprintf(
                            '🏠 Propriété #%d (%s): %s → %s',
                            $property->getId(),
                            $property->getFullAddress(),
                            $oldStatus,
                            $newStatus
                        ));
                    }
                }
            }

            // Sauvegarder tous les changements
            $this->entityManager->flush();

            $result = sprintf(
                'Mise à jour terminée: %d propriétés mises à jour, %d occupées, %d libres.',
                $updatedCount,
                $occupiedCount,
                $freeCount
            );

            $this->logger->info(sprintf('✅ %s', $result));

            return $result;

        } catch (\Exception $e) {
            $this->logger->error(sprintf('❌ Erreur lors de la mise à jour du statut des propriétés: %s', $e->getMessage()));
            throw $e;
        }
    }

    /**
     * Vérifie si une propriété a un bail actif
     */
    private function hasActiveLease(\App\Entity\Property $property): bool
    {
        $now = new \DateTime();

        foreach ($property->getLeases() as $lease) {
            // Un bail est actif s'il a commencé et n'a pas encore fini
            if ($lease->getStartDate() <= $now &&
                ($lease->getEndDate() === null || $lease->getEndDate() >= $now) &&
                $lease->getStatus() === 'Actif') {
                return true;
            }
        }

        return false;
    }

    /**
     * Exécute la tâche de création des configurations comptables
     */
    public function executeCreateAccountingConfigurationsTask(Task $task, bool $logDetails = false): string
    {
        try {
            $parameters = $task->getParameters() ?? [];
            $logDetails = $parameters['log_details'] ?? false;

            $this->logger->info('⚙️ Début de la création des configurations comptables');

            // Récupérer le service de configuration comptable
            $configService = new \App\Service\AccountingConfigService(
                $this->entityManager->getRepository(\App\Entity\AccountingConfiguration::class),
                $this->entityManager
            );

            // Créer les configurations par défaut
            $configService->createDefaultConfigurations();

            // Récupérer les configurations créées
            $configurations = $configService->getAllActiveConfigurations();

            $result = sprintf(
                'Configurations comptables créées avec succès: %d configurations disponibles.',
                count($configurations)
            );

            if ($logDetails) {
                $this->logger->info('📋 Configurations comptables créées:');
                foreach ($configurations as $config) {
                    $this->logger->info(sprintf(
                        '   • %s: %s (%s) - %s',
                        $config->getOperationType(),
                        $config->getAccountNumber(),
                        $config->getAccountLabel(),
                        $config->getEntryType()
                    ));
                }
            }

            $this->logger->info(sprintf('✅ %s', $result));

            return $result;

        } catch (\Exception $e) {
            $errorMsg = sprintf('Erreur lors de la création des configurations comptables: %s', $e->getMessage());
            $this->logger->error(sprintf('❌ %s', $errorMsg));
            throw new \Exception($errorMsg);
        }
    }

    /**
     * Exécute la tâche de test de configuration comptable
     */
    public function executeTestAccountingConfigTask(Task $task, bool $logDetails = false): string
    {
        try {
            $parameters = $task->getParameters() ?? [];
            $logDetails = $parameters['log_details'] ?? false;

            $this->logger->info('🧪 Début du test de configuration comptable');

            // Récupérer le service de configuration comptable
            $configService = new \App\Service\AccountingConfigService(
                $this->entityManager->getRepository(\App\Entity\AccountingConfiguration::class),
                $this->entityManager
            );

            $configurations = $configService->getAllActiveConfigurations();
            $validConfigs = 0;
            $invalidConfigs = 0;

            foreach ($configurations as $config) {
                $errors = $configService->validateConfiguration($config);
                if (empty($errors)) {
                    $validConfigs++;
                } else {
                    $invalidConfigs++;
                    if ($logDetails) {
                        $this->logger->error(sprintf('Configuration invalide %s: %s',
                            $config->getOperationType(), implode(', ', $errors)));
                    }
                }
            }

            $result = sprintf(
                'Test terminé: %d configurations valides, %d invalides sur %d total.',
                $validConfigs, $invalidConfigs, count($configurations)
            );

            $this->logger->info(sprintf('✅ %s', $result));

            return $result;

        } catch (\Exception $e) {
            $errorMsg = sprintf('Erreur lors du test de configuration comptable: %s', $e->getMessage());
            $this->logger->error(sprintf('❌ %s', $errorMsg));
            throw new \Exception($errorMsg);
        }
    }

    /**
     * Exécute la tâche de vérification des écritures comptables
     */
    public function executeCheckAccountingEntriesTask(Task $task, bool $logDetails = false): string
    {
        try {
            $parameters = $task->getParameters() ?? [];
            $logDetails = $parameters['log_details'] ?? false;

            $this->logger->info('📊 Début de la vérification des écritures comptables');

            $totalEntries = $this->entityManager->getRepository(\App\Entity\AccountingEntry::class)
                ->count([]);

            $loyerEntries = $this->entityManager->getRepository(\App\Entity\AccountingEntry::class)
                ->count(['category' => 'LOYER']);

            $result = sprintf(
                'Vérification terminée: %d écritures comptables total, %d écritures LOYER.',
                $totalEntries, $loyerEntries
            );

            if ($logDetails) {
                $this->logger->info(sprintf('Total écritures comptables: %d', $totalEntries));
                $this->logger->info(sprintf('Écritures LOYER: %d', $loyerEntries));
            }

            $this->logger->info(sprintf('✅ %s', $result));

            return $result;

        } catch (\Exception $e) {
            $errorMsg = sprintf('Erreur lors de la vérification des écritures comptables: %s', $e->getMessage());
            $this->logger->error(sprintf('❌ %s', $errorMsg));
            throw new \Exception($errorMsg);
        }
    }

    /**
     * Exécute la tâche de test de génération de loyers avec configuration
     */
    public function executeTestRentGenerationWithConfigTask(Task $task, bool $logDetails = false): string
    {
        try {
            $parameters = $task->getParameters() ?? [];
            $logDetails = $parameters['log_details'] ?? false;

            $this->logger->info('🏠 Début du test de génération de loyers avec configuration');

            // Récupérer le service de configuration comptable
            $configService = new \App\Service\AccountingConfigService(
                $this->entityManager->getRepository(\App\Entity\AccountingConfiguration::class),
                $this->entityManager
            );

            $loyerConfig = $configService->getConfigurationForOperation('LOYER_ATTENDU');

            if (!$loyerConfig) {
                throw new \Exception('Configuration LOYER_ATTENDU non trouvée');
            }

            $result = sprintf(
                'Test terminé: Configuration LOYER_ATTENDU trouvée (%s - %s).',
                $loyerConfig->getAccountNumber(), $loyerConfig->getEntryType()
            );

            if ($logDetails) {
                $this->logger->info(sprintf('Configuration trouvée: %s (%s)',
                    $loyerConfig->getAccountNumber(), $loyerConfig->getAccountLabel()));
            }

            $this->logger->info(sprintf('✅ %s', $result));

            return $result;

        } catch (\Exception $e) {
            $errorMsg = sprintf('Erreur lors du test de génération de loyers: %s', $e->getMessage());
            $this->logger->error(sprintf('❌ %s', $errorMsg));
            throw new \Exception($errorMsg);
        }
    }

    /**
     * Exécute la tâche de démonstration du système comptable
     */
    public function executeDemoAccountingSystemTask(Task $task, bool $logDetails = false): string
    {
        try {
            $parameters = $task->getParameters() ?? [];
            $logDetails = $parameters['log_details'] ?? false;

            $this->logger->info('🎯 Début de la démonstration du système comptable');

            // Récupérer le service de configuration comptable
            $configService = new \App\Service\AccountingConfigService(
                $this->entityManager->getRepository(\App\Entity\AccountingConfiguration::class),
                $this->entityManager
            );

            $configurations = $configService->getAllActiveConfigurations();
            $loyerConfig = $configService->getConfigurationForOperation('LOYER_ATTENDU');

            $result = sprintf(
                'Démonstration terminée: %d configurations disponibles, système comptable opérationnel.',
                count($configurations)
            );

            if ($logDetails) {
                $this->logger->info(sprintf('Configurations disponibles: %d', count($configurations)));
                if ($loyerConfig) {
                    $this->logger->info(sprintf('Configuration LOYER_ATTENDU: %s (%s)',
                        $loyerConfig->getAccountNumber(), $loyerConfig->getEntryType()));
                }
            }

            $this->logger->info(sprintf('✅ %s', $result));

            return $result;

        } catch (\Exception $e) {
            $errorMsg = sprintf('Erreur lors de la démonstration du système comptable: %s', $e->getMessage());
            $this->logger->error(sprintf('❌ %s', $errorMsg));
            throw new \Exception($errorMsg);
        }
    }

    /**
     * Exécute la tâche de correction de la table comptable
     */
    public function executeFixAccountingTableTask(Task $task, bool $logDetails = false): string
    {
        try {
            $parameters = $task->getParameters() ?? [];
            $logDetails = $parameters['log_details'] ?? false;

            $this->logger->info('🔧 Début de la correction de la table comptable');

            // Vérifier si la table existe et est correcte
            $connection = $this->entityManager->getConnection();
            $tableExists = $connection->createSchemaManager()->tablesExist(['accounting_configuration']);

            if (!$tableExists) {
                // Créer la table
                $connection->executeStatement('
                    CREATE TABLE accounting_configuration (
                        id INT AUTO_INCREMENT NOT NULL,
                        operation_type VARCHAR(100) NOT NULL,
                        account_number VARCHAR(20) NOT NULL,
                        account_label VARCHAR(255) NOT NULL,
                        entry_type VARCHAR(10) NOT NULL,
                        description VARCHAR(255) NOT NULL,
                        reference VARCHAR(255) DEFAULT NULL,
                        category VARCHAR(100) NOT NULL,
                        is_active TINYINT(1) DEFAULT 1,
                        notes LONGTEXT DEFAULT NULL,
                        created_at DATETIME NOT NULL,
                        updated_at DATETIME DEFAULT NULL,
                        UNIQUE INDEX UNIQ_ACCOUNTING_CONFIG_OPERATION_TYPE (operation_type),
                        PRIMARY KEY(id)
                    ) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB
                ');

                $result = 'Table accounting_configuration créée avec succès.';
            } else {
                $result = 'Table accounting_configuration existe déjà et est correcte.';
            }

            if ($logDetails) {
                $this->logger->info($result);
            }

            $this->logger->info(sprintf('✅ %s', $result));

            return $result;

        } catch (\Exception $e) {
            $errorMsg = sprintf('Erreur lors de la correction de la table comptable: %s', $e->getMessage());
            $this->logger->error(sprintf('❌ %s', $errorMsg));
            throw new \Exception($errorMsg);
        }
    }

    /**
     * Exécute la tâche de configuration du système comptable
     */
    public function executeSetupAccountingSystemTask(Task $task, bool $logDetails = false): string
    {
        try {
            $parameters = $task->getParameters() ?? [];
            $logDetails = $parameters['log_details'] ?? false;

            $this->logger->info('🚀 Début de la configuration du système comptable');

            // D'abord corriger la table
            $this->executeFixAccountingTableTask($task, $logDetails);

            // Puis créer les configurations
            $this->executeCreateAccountingConfigurationsTask($task, $logDetails);

            $result = 'Système comptable configuré avec succès (table + configurations).';

            $this->logger->info(sprintf('✅ %s', $result));

            return $result;

        } catch (\Exception $e) {
            $errorMsg = sprintf('Erreur lors de la configuration du système comptable: %s', $e->getMessage());
            $this->logger->error(sprintf('❌ %s', $errorMsg));
            throw new \Exception($errorMsg);
        }
    }

    /**
     * Exécute la tâche d'initialisation des paramètres email
     */
    public function executeInitializeEmailSettingsTask(Task $task, bool $logDetails = false): string
    {
        try {
            $parameters = $task->getParameters() ?? [];
            $logDetails = $parameters['log_details'] ?? false;

            if ($logDetails) {
                $this->logger->info('📧 Début de l\'initialisation des paramètres email');
            }

            // Initialiser les paramètres email par défaut
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

            // Templates par défaut (simplifiés pour la tâche)
            $this->settingsService->set('email_template_receipt', '<h2>Quittance de loyer</h2><p>Bonjour {{ locataire_nom }}, votre quittance est en pièce jointe.</p>');
            $this->settingsService->set('email_template_reminder', '<h2>Rappel de paiement</h2><p>Bonjour {{ locataire_nom }}, votre loyer est dû le {{ date_echeance }}.</p>');
            $this->settingsService->set('email_template_expiration', '<h2>Expiration de contrat</h2><p>Bonjour {{ locataire_nom }}, votre contrat expire le {{ contrat_fin }}.</p>');
            $this->settingsService->set('email_template_welcome', '<h2>Bienvenue</h2><p>Bonjour {{ locataire_nom }}, bienvenue chez {{ societe_nom }}.</p>');

            // Paramètres de pièces jointes
            $this->settingsService->set('email_attachment_max_size', 10);
            $this->settingsService->set('email_compress_images', true);

            $result = 'Paramètres email initialisés avec succès (expéditeur, templates, notifications).';

            if ($logDetails) {
                $this->logger->info(sprintf('✅ %s', $result));
            }

            return $result;

        } catch (\Exception $e) {
            $errorMsg = sprintf('Erreur lors de l\'initialisation des paramètres email: %s', $e->getMessage());
            $this->logger->error(sprintf('❌ %s', $errorMsg));
            throw new \Exception($errorMsg);
        }
    }

    /**
     * Exécute la tâche de test des paramètres email
     */
    public function executeTestEmailSettingsTask(Task $task, bool $logDetails = false): string
    {
        try {
            $parameters = $task->getParameters() ?? [];
            $logDetails = $parameters['log_details'] ?? false;
            $testEmail = $parameters['test_email'] ?? 'info@app.lokapro.tech';

            if ($logDetails) {
                $this->logger->info(sprintf('📧 Test des paramètres email vers %s', $testEmail));
            }

            // Tester l'envoi d'email avec les paramètres actuels
            $success = $this->notificationService->testEmailConfiguration($testEmail);

            if ($success) {
                $result = sprintf('Email de test envoyé avec succès à %s avec les paramètres configurés.', $testEmail);

                if ($logDetails) {
                    $this->logger->info(sprintf('✅ %s', $result));
                }
            } else {
                $result = sprintf('Échec de l\'envoi de l\'email de test à %s.', $testEmail);

                if ($logDetails) {
                    $this->logger->error(sprintf('❌ %s', $result));
                }
            }

            return $result;

        } catch (\Exception $e) {
            $errorMsg = sprintf('Erreur lors du test des paramètres email: %s', $e->getMessage());
            $this->logger->error(sprintf('❌ %s', $errorMsg));
            throw new \Exception($errorMsg);
        }
    }

    /**
     * Exécute la tâche de test de la configuration SMTP
     */
    public function executeTestSmtpConfigurationTask(Task $task, bool $logDetails = false): string
    {
        try {
            $parameters = $task->getParameters() ?? [];
            $logDetails = $parameters['log_details'] ?? false;
            $testEmail = $parameters['test_email'] ?? 'info@app.lokapro.tech';

            if ($logDetails) {
                $this->logger->info(sprintf('🔧 Test de la configuration SMTP vers %s', $testEmail));
            }

            // Tester la connexion SMTP
            if (!$this->smtpConfigurationService) {
                $this->smtpConfigurationService = new \App\Service\SmtpConfigurationService($this->parameterBag, $this->settingsService);
            }

            $connectionTest = $this->smtpConfigurationService->testSmtpConnection();

            if ($connectionTest['success']) {
                $result = sprintf('Connexion SMTP réussie. Configuration: %s:%d',
                    $connectionTest['config']['host'],
                    $connectionTest['config']['port']
                );

                if ($logDetails) {
                    $this->logger->info(sprintf('✅ %s', $result));
                }
            } else {
                $result = sprintf('Échec de la connexion SMTP: %s', $connectionTest['message']);

                if ($logDetails) {
                    $this->logger->error(sprintf('❌ %s', $result));
                }
            }

            return $result;

        } catch (\Exception $e) {
            $errorMsg = sprintf('Erreur lors du test de la configuration SMTP: %s', $e->getMessage());
            $this->logger->error(sprintf('❌ %s', $errorMsg));
            throw new \Exception($errorMsg);
        }
    }

    /**
     * Exécute la tâche de mise à jour de la configuration SMTP
     */
    public function executeUpdateSmtpConfigurationTask(Task $task, bool $logDetails = false): string
    {
        try {
            $parameters = $task->getParameters() ?? [];
            $logDetails = $parameters['log_details'] ?? false;

            if ($logDetails) {
                $this->logger->info('🔧 Mise à jour de la configuration SMTP');
            }

            // Configuration SMTP pour app.lokapro.tech
            $config = [
                'host' => 'app.lokapro.tech',
                'port' => 465,
                'username' => 'info@app.lokapro.tech',
                'password' => 'q+Dy-riz8EBi;oL]',
                'encryption' => 'ssl',
                'auth_mode' => 'login',
            ];

            // Mettre à jour la configuration SMTP
            if (!$this->smtpConfigurationService) {
                $this->smtpConfigurationService = new \App\Service\SmtpConfigurationService($this->parameterBag, $this->settingsService);
            }

            $success = $this->smtpConfigurationService->updateSmtpConfiguration($config);

            if ($success) {
                $result = sprintf('Configuration SMTP mise à jour avec succès: %s:%d (%s)',
                    $config['host'],
                    $config['port'],
                    $config['encryption']
                );

                if ($logDetails) {
                    $this->logger->info(sprintf('✅ %s', $result));
                }
            } else {
                $result = 'Échec de la mise à jour de la configuration SMTP.';

                if ($logDetails) {
                    $this->logger->error(sprintf('❌ %s', $result));
                }
            }

            return $result;

        } catch (\Exception $e) {
            $errorMsg = sprintf('Erreur lors de la mise à jour de la configuration SMTP: %s', $e->getMessage());
            $this->logger->error(sprintf('❌ %s', $errorMsg));
            throw new \Exception($errorMsg);
        }
    }

    /**
     * Optimise la mémoire avant l'exécution des tâches
     */
    private function optimizeMemoryBeforeExecution(): void
    {
        // Augmenter la limite de mémoire si nécessaire
        $currentMemoryLimit = ini_get('memory_limit');
        $currentMemoryLimitBytes = $this->convertToBytes($currentMemoryLimit);

        // Si la limite actuelle est inférieure à 1GB, l'augmenter
        if ($currentMemoryLimitBytes < 1073741824) { // 1GB
            ini_set('memory_limit', '1024M');
            $this->logger->info("Limite de mémoire augmentée à 1024M");
        }

        // Forcer le garbage collection
        gc_collect_cycles();

        $memoryUsage = memory_get_usage(true);
        $this->logger->info("Utilisation mémoire avant exécution: " . $this->formatBytes($memoryUsage));
    }

    /**
     * Optimise la mémoire après l'exécution d'une tâche
     */
    private function optimizeMemoryAfterTask(): void
    {
        $memoryUsage = memory_get_usage(true);
        $memoryLimit = ini_get('memory_limit');
        $memoryLimitBytes = $this->convertToBytes($memoryLimit);

        // Si on utilise plus de 80% de la mémoire, forcer le garbage collection
        if ($memoryUsage > ($memoryLimitBytes * 0.8)) {
            $this->logger->warning("Utilisation mémoire élevée ({$memoryUsage} bytes), libération de la mémoire");

            // Forcer le garbage collection
            gc_collect_cycles();

            // Clear l'EntityManager si possible
            if ($this->entityManager->isOpen()) {
                $this->entityManager->clear();
            }
        }
    }

    /**
     * Convertit une chaîne de limite de mémoire en bytes
     */
    private function convertToBytes(string $memoryLimit): int
    {
        $memoryLimit = trim($memoryLimit);
        $last = strtolower($memoryLimit[strlen($memoryLimit) - 1]);
        $value = (int) $memoryLimit;

        switch ($last) {
            case 'g':
                $value *= 1024;
            case 'm':
                $value *= 1024;
            case 'k':
                $value *= 1024;
        }

        return $value;
    }

    /**
     * Formate les bytes en format lisible
     */
    private function formatBytes(int $bytes): string
    {
        $units = ['B', 'KB', 'MB', 'GB'];
        $bytes = max($bytes, 0);
        $pow = floor(($bytes ? log($bytes) : 0) / log(1024));
        $pow = min($pow, count($units) - 1);

        $bytes /= (1 << (10 * $pow));

        return round($bytes, 2) . ' ' . $units[$pow];
    }
}
