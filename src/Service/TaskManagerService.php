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
        private LoggerInterface $logger
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
    }

    /**
     * Exécute la tâche d'alertes d'expiration de contrat
     */
    private function executeLeaseExpirationTask(Task $task): void
    {
        $results = $this->notificationService->sendLeaseExpirationAlerts();

        $task->setParameter('last_sent_count', $results['sent']);
        $task->setParameter('last_failed_count', $results['failed']);
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
}
