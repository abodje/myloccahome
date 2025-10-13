<?php

namespace App\Service;

use App\Entity\Task;
use App\Repository\TaskRepository;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;

class TaskManagerService
{
    public function __construct(
        private EntityManagerInterface $entityManager,
        private NotificationService $notificationService,
        private LoggerInterface $logger,
        private RentReceiptService $rentReceiptService,
        private OrangeSmsService $orangeSmsService,
        private SettingsService $settingsService
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
     * Exécute toutes les tâches qui sont dues
     */
    public function runDueTasks(): array
    {
        $taskRepository = $this->entityManager->getRepository(Task::class);
        $dueTasks = $taskRepository->findDueTasks();

        $results = ['executed' => 0, 'failed' => 0, 'errors' => []];

        foreach ($dueTasks as $task) {
            try {
                $this->executeTask($task);
                $results['executed']++;
                $this->logger->info("Tâche exécutée avec succès: {$task->getName()}");
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
        $this->entityManager->flush();

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

                default:
                    throw new \Exception("Type de tâche non reconnu: {$task->getType()}");
            }

            $task->markAsCompleted();
            $this->entityManager->flush();
        } catch (\Exception $e) {
            $task->markAsFailed($e->getMessage());
            $this->entityManager->flush();
            throw $e;
        }
    }

    /**
     * Exécute la tâche d'envoi de quittances de loyer
     */
    private function executeRentReceiptTask(Task $task): void
    {
        $forMonth = null;
        if ($task->getParameter('month_offset')) {
            $forMonth = new \DateTime();
            $forMonth->modify($task->getParameter('month_offset'));
            $forMonth->modify('first day of this month');
        }

        $results = $this->notificationService->sendRentReceipts($forMonth);

        $task->setParameter('last_sent_count', $results['sent']);
        $task->setParameter('last_failed_count', $results['failed']);
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
                "Rappel MYLOCCA: Votre loyer de %s est en retard de %d jour(s). Echéance: %s. Payez sur mylocca.com",
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
                "MYLOCCA: Votre bail %s expire dans %d jours (%s). Contactez-nous",
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

        // Générer les quittances du mois
        $receipts = $this->rentReceiptService->generateMonthlyReceipts($monthDate);

        // Générer les avis d'échéance pour le mois prochain
        $nextMonth = (clone $monthDate)->modify('+1 month');
        $notices = $this->rentReceiptService->generateUpcomingNotices($nextMonth);

        $total = count($receipts) + count($notices);

        // Logger le résultat
        $this->logger->info(sprintf(
            'Documents générés pour %s : %d quittances, %d avis d\'échéance (Total: %d)',
            $monthDate->format('F Y'),
            count($receipts),
            count($notices),
            $total
        ));
    }
}
