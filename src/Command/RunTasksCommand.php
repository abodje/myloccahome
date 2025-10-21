<?php

namespace App\Command;

use App\Service\TaskManagerService;
use App\Repository\TaskRepository;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(
    name: 'app:tasks:run',
    description: 'Exécute une tâche spécifique ou toutes les tâches actives.',
)]
class RunTasksCommand extends Command
{
    public function __construct(
        private TaskManagerService $taskManagerService,
        private TaskRepository $taskRepository
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this
            ->addOption('task-type', 't', InputOption::VALUE_REQUIRED, 'Type de tâche à exécuter')
            ->addOption('task-id', 'i', InputOption::VALUE_REQUIRED, 'ID de la tâche à exécuter')
            ->addOption('all', 'a', InputOption::VALUE_NONE, 'Exécuter toutes les tâches actives')
            ->setHelp('Cette commande exécute une tâche spécifique ou toutes les tâches actives.');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $io->title('🚀 Exécution des tâches programmées - LOKAPRO');

        $taskType = $input->getOption('task-type');
        $taskId = $input->getOption('task-id');
        $runAll = $input->getOption('all');

        try {
            if ($taskId) {
                // Exécuter une tâche spécifique par ID
                $task = $this->taskRepository->find($taskId);
                if (!$task) {
                    $io->error(sprintf('❌ Tâche avec l\'ID %d non trouvée', $taskId));
                    return Command::FAILURE;
                }
                $this->executeTask($task, $io);

            } elseif ($taskType) {
                // Exécuter une tâche spécifique par type
                $task = $this->taskRepository->findOneBy(['type' => $taskType]);
                if (!$task) {
                    $io->error(sprintf('❌ Tâche de type "%s" non trouvée', $taskType));
                    return Command::FAILURE;
                }
                $this->executeTask($task, $io);

            } elseif ($runAll) {
                // Exécuter toutes les tâches actives
                $tasks = $this->taskRepository->findBy(['status' => 'ACTIVE']);
                if (empty($tasks)) {
                    $io->warning('Aucune tâche active trouvée.');
                    return Command::SUCCESS;
                }

                $io->writeln(sprintf('Exécution de %d tâches actives...', count($tasks)));

                foreach ($tasks as $task) {
                    $this->executeTask($task, $io);
                }

            } else {
                $io->error('❌ Veuillez spécifier --task-type, --task-id ou --all');
                $io->writeln('');
                $io->writeln('Exemples d\'utilisation :');
                $io->writeln('  php bin/console app:tasks:run --task-type=GENERATE_RENTS');
                $io->writeln('  php bin/console app:tasks:run --task-id=1');
                $io->writeln('  php bin/console app:tasks:run --all');
                return Command::FAILURE;
            }

            $io->success('🎉 Exécution des tâches terminée !');
            return Command::SUCCESS;

        } catch (\Exception $e) {
            $io->error('❌ Erreur lors de l\'exécution: ' . $e->getMessage());
            return Command::FAILURE;
        }
    }

    private function executeTask($task, SymfonyStyle $io): void
    {
        $io->writeln(sprintf('🔄 Exécution de la tâche: %s', $task->getName()));

        try {
            $this->taskManagerService->executeTask($task);
            $io->writeln(sprintf('✅ Tâche "%s" exécutée avec succès', $task->getName()));

            // Afficher les résultats si disponibles
            if ($task->getResult()) {
                $io->writeln(sprintf('   Résultat: %s', $task->getResult()));
            }

        } catch (\Exception $e) {
            $io->writeln(sprintf('❌ Erreur lors de l\'exécution: %s', $e->getMessage()));
        }

        $io->writeln('');
    }
}
