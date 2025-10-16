<?php

namespace App\Command;

use App\Service\TaskManagerService;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(
    name: 'app:tasks:run',
    description: 'Exécute toutes les tâches programmées qui sont dues',
)]
class TaskRunnerCommand extends Command
{
    public function __construct(
        private TaskManagerService $taskManager
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this
            ->addOption('force', 'f', InputOption::VALUE_NONE, 'Force l\'exécution même si les tâches ne sont pas dues')
            ->addOption('task-id', 't', InputOption::VALUE_REQUIRED, 'Exécute une tâche spécifique par son ID')
            ->setHelp('Cette commande exécute toutes les tâches programmées qui sont dues ou force l\'exécution d\'une tâche spécifique.');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        $io->title('🚀 Exécution des tâches programmées - MYLOCCA');

        try {
            if ($taskId = $input->getOption('task-id')) {
                return $this->executeSingleTask($taskId, $io);
            }

            $results = $this->taskManager->runDueTasks();

            if ($results['executed'] > 0) {
                $io->success("✅ {$results['executed']} tâche(s) exécutée(s) avec succès");
            }

            if ($results['failed'] > 0) {
                $io->warning("⚠️  {$results['failed']} tâche(s) ont échoué");
                foreach ($results['errors'] as $error) {
                    $io->error($error);
                }
            }

            if ($results['executed'] === 0 && $results['failed'] === 0) {
                $io->info('ℹ️  Aucune tâche à exécuter pour le moment');
            }

            // Afficher les statistiques
            $stats = $this->taskManager->getTaskStatistics();
            $io->section('📊 Statistiques des tâches');
            $io->table(
                ['Métrique', 'Valeur'],
                [
                    ['Total des tâches', $stats['total']],
                    ['Tâches actives', $stats['active']],
                    ['Tâches en cours', $stats['running']],
                    ['Tâches dues', $stats['due']],
                ]
            );

            return Command::SUCCESS;
        } catch (\Exception $e) {
            $io->error('❌ Erreur lors de l\'exécution des tâches: ' . $e->getMessage());
            return Command::FAILURE;
        }
    }

    private function executeSingleTask(int $taskId, SymfonyStyle $io): int
    {
        $taskRepository = $this->taskManager->getEntityManager()->getRepository(\App\Entity\Task::class);
        $task = $taskRepository->find($taskId);

        if (!$task) {
            $io->error("❌ Tâche avec l'ID {$taskId} introuvable");
            return Command::FAILURE;
        }

        $io->info("🔄 Exécution de la tâche: {$task->getName()}");

        try {
            $this->taskManager->forceExecuteTask($task);
            $io->success("✅ Tâche exécutée avec succès");
            return Command::SUCCESS;
        } catch (\Exception $e) {
            $io->error("❌ Erreur lors de l'exécution: " . $e->getMessage());
            return Command::FAILURE;
        }
    }
}
