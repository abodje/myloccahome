<?php

namespace App\Command;

use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use App\Service\TaskManagerService;
use App\Entity\Task;

#[AsCommand(
    name: 'app:demo:create-task',
    description: 'Crée et exécute une tâche de création d\'environnements de démo via le TaskManagerService.',
)]
class CreateDemoTaskCommand extends Command
{
    private TaskManagerService $taskManagerService;

    public function __construct(TaskManagerService $taskManagerService)
    {
        parent::__construct();
        $this->taskManagerService = $taskManagerService;
    }

    protected function configure(): void
    {
        $this
            ->setHelp('Cette commande crée et exécute une tâche de création d\'environnements de démo via le TaskManagerService.');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $io->title('Création d\'environnements de démo');

        try {
            // Créer la tâche de création de démo si elle n'existe pas
            $taskRepository = $this->taskManagerService->getEntityManager()->getRepository(Task::class);
            $task = $taskRepository->findOneBy(['type' => 'DEMO_CREATE']);

            if (!$task) {
                $task = new Task();
                $task->setName('Création d\'environnements de démo');
                $task->setType('DEMO_CREATE');
                $task->setDescription('Crée des environnements de démo pour les utilisateurs avec données de test');
                $task->setFrequency('MANUAL');
                $task->setParameters([
                    'default_days' => 14,
                    'auto_cleanup' => true,
                    'log_details' => true,
                ]);
                $task->setStatus('ACTIVE');
                $task->calculateNextRun();
                $this->taskManagerService->getEntityManager()->persist($task);
                $this->taskManagerService->getEntityManager()->flush();
                $io->info('Tâche DEMO_CREATE créée.');
            } else {
                // S'assurer que la tâche est active
                if ($task->getStatus() !== 'ACTIVE') {
                    $task->setStatus('ACTIVE');
                    $this->taskManagerService->getEntityManager()->flush();
                    $io->info('Tâche DEMO_CREATE activée.');
                } else {
                    $io->info('Tâche DEMO_CREATE existante trouvée et active.');
                }
            }

            // Exécuter la tâche
            $io->section('Exécution de la tâche de création d\'environnements de démo...');
            $this->taskManagerService->executeTask($task);

            $io->success('Création d\'environnements de démo terminée.');
            $io->writeln('Résultat de la tâche: ' . $task->getResult());

            return Command::SUCCESS;

        } catch (\Exception $e) {
            $io->error('Erreur lors de la création d\'environnements de démo : ' . $e->getMessage());
            return Command::FAILURE;
        }
    }
}
