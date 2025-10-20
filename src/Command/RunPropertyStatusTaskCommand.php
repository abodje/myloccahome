<?php

namespace App\Command;

use App\Entity\Task;
use App\Service\TaskManagerService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(
    name: 'app:run-property-status-task',
    description: 'Exécute manuellement la tâche UPDATE_PROPERTY_STATUS',
)]
class RunPropertyStatusTaskCommand extends Command
{
    private TaskManagerService $taskManagerService;
    private EntityManagerInterface $entityManager;

    public function __construct(TaskManagerService $taskManagerService, EntityManagerInterface $entityManager)
    {
        $this->taskManagerService = $taskManagerService;
        $this->entityManager = $entityManager;
        parent::__construct();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        $io->title('Exécution manuelle de la tâche UPDATE_PROPERTY_STATUS');

        // Chercher la tâche
        $task = $this->entityManager->getRepository(Task::class)
            ->findOneBy(['type' => 'UPDATE_PROPERTY_STATUS']);

        if (!$task) {
            $io->error('La tâche UPDATE_PROPERTY_STATUS n\'a pas été trouvée dans la base de données.');
            return Command::FAILURE;
        }

        $io->writeln(sprintf('Exécution de la tâche: %s', $task->getName()));

        try {
            // Exécuter la tâche
            $this->taskManagerService->executeTask($task);

            $io->success('Tâche exécutée avec succès !');
            $io->writeln(sprintf('Résultat: %s', $task->getResult()));
            $io->writeln(sprintf('Nombre d\'exécutions: %d', $task->getRunCount()));
            $io->writeln(sprintf('Succès: %d', $task->getSuccessCount()));

        } catch (\Exception $e) {
            $io->error(sprintf('Erreur lors de l\'exécution de la tâche: %s', $e->getMessage()));
            $io->writeln(sprintf('Échecs: %d', $task->getFailureCount()));
            $io->writeln(sprintf('Dernière erreur: %s', $task->getLastError()));
            return Command::FAILURE;
        }

        return Command::SUCCESS;
    }
}
