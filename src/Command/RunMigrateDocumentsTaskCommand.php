<?php

namespace App\Command;

use App\Entity\Task;
use App\Service\TaskManagerService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(
    name: 'app:run-migrate-documents-task',
    description: 'Exécute la tâche de migration des documents de sécurité',
)]
class RunMigrateDocumentsTaskCommand extends Command
{
    public function __construct(
        private EntityManagerInterface $entityManager,
        private TaskManagerService $taskManagerService
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this
            ->addOption('task-id', null, InputOption::VALUE_REQUIRED, 'ID de la tâche à exécuter')
            ->addOption('dry-run', null, InputOption::VALUE_NONE, 'Mode simulation')
            ->addOption('backup', null, InputOption::VALUE_NONE, 'Créer une sauvegarde')
            ->addOption('batch-size', null, InputOption::VALUE_REQUIRED, 'Taille des lots', 10)
            ->addOption('create-task', null, InputOption::VALUE_NONE, 'Créer la tâche si elle n\'existe pas')
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        $io->title('Exécution de la migration des documents de sécurité');

        $taskId = $input->getOption('task-id');
        $task = null;

        if ($taskId) {
            $task = $this->entityManager->getRepository(Task::class)->find($taskId);
            if (!$task) {
                $io->error(sprintf('Tâche avec l\'ID %s non trouvée.', $taskId));
                return Command::FAILURE;
            }
        } else {
            // Chercher la tâche de migration des documents
            $task = $this->entityManager->getRepository(Task::class)
                ->findOneBy(['type' => 'MIGRATE_DOCUMENTS_SECURITY']);
        }

        if (!$task) {
            if ($input->getOption('create-task')) {
                $io->info('Création d\'une nouvelle tâche de migration...');
                $task = $this->createMigrationTask($input);
            } else {
                $io->error('Aucune tâche de migration trouvée. Utilisez --create-task pour en créer une.');
                return Command::FAILURE;
            }
        }

        // Mettre à jour les paramètres si fournis
        $parameters = $task->getParameters() ?? [];
        if ($input->getOption('dry-run')) {
            $parameters['dry_run'] = true;
        }
        if ($input->getOption('backup')) {
            $parameters['backup'] = true;
        }
        if ($input->getOption('batch-size')) {
            $parameters['batch_size'] = (int) $input->getOption('batch-size');
        }
        $task->setParameters($parameters);

        $io->info(sprintf('Exécution de la tâche: %s (ID: %d)', $task->getName(), $task->getId()));

        if ($parameters['dry_run'] ?? false) {
            $io->warning('Mode simulation activé - aucune modification ne sera effectuée');
        }

        try {
            // Exécuter la tâche
            $this->taskManagerService->forceExecuteTask($task);

            // Récupérer le résultat
            $result = json_decode($task->getResult(), true);

            if ($task->getStatus() === 'COMPLETED') {
                $io->success('Migration terminée avec succès !');

                $io->table(
                    ['Statut', 'Valeur'],
                    [
                        ['Documents traités', $result['processed'] ?? 0],
                        ['Documents migrés', $result['success'] ?? 0],
                        ['Erreurs', $result['errors'] ?? 0],
                        ['Progression', ($result['progress'] ?? 0) . '%'],
                        ['Mode simulation', ($result['dry_run'] ?? false) ? 'Oui' : 'Non'],
                        ['Sauvegarde créée', ($result['backup_created'] ?? false) ? 'Oui' : 'Non'],
                    ]
                );

                if (!empty($result['error_details'])) {
                    $io->section('Détails des erreurs');
                    foreach ($result['error_details'] as $error) {
                        $io->text($error);
                    }
                }

            } else {
                $io->error('Migration échouée: ' . $task->getLastError());
                return Command::FAILURE;
            }

        } catch (\Exception $e) {
            $io->error('Erreur lors de l\'exécution de la tâche: ' . $e->getMessage());
            return Command::FAILURE;
        }

        return Command::SUCCESS;
    }

    private function createMigrationTask(InputInterface $input): Task
    {
        $task = new Task();
        $task->setName('Migration des documents de sécurité');
        $task->setType('MIGRATE_DOCUMENTS_SECURITY');
        $task->setDescription('Migre tous les documents existants vers le système de sécurité avec chiffrement');
        $task->setFrequency('ONCE');
        $task->setStatus('ACTIVE');

        $parameters = [
            'dry_run' => $input->getOption('dry-run'),
            'backup' => $input->getOption('backup'),
            'batch_size' => (int) $input->getOption('batch-size'),
        ];
        $task->setParameters($parameters);

        $this->entityManager->persist($task);
        $this->entityManager->flush();

        return $task;
    }
}
