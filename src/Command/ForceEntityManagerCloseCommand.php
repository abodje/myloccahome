<?php

namespace App\Command;

use App\Service\TaskManagerService;
use App\Repository\TaskRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(
    name: 'app:force-entity-manager-close',
    description: 'Force la fermeture de l\'EntityManager pour tester la gestion d\'erreur.',
)]
class ForceEntityManagerCloseCommand extends Command
{
    private TaskManagerService $taskManagerService;
    private TaskRepository $taskRepository;
    private EntityManagerInterface $entityManager;

    public function __construct(
        TaskManagerService $taskManagerService,
        TaskRepository $taskRepository,
        EntityManagerInterface $entityManager
    ) {
        parent::__construct();
        $this->taskManagerService = $taskManagerService;
        $this->taskRepository = $taskRepository;
        $this->entityManager = $entityManager;
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $io->title('Test de fermeture forcée de l\'EntityManager');

        try {
            // 1. Fermer intentionnellement l'EntityManager
            $io->section('1. Fermeture forcée de l\'EntityManager');
            $io->writeln('Fermeture de l\'EntityManager...');

            $this->entityManager->close();

            $io->writeln(sprintf('État EntityManager: %s',
                $this->entityManager->isOpen() ? 'OUVERT' : 'FERMÉ'));

            // 2. Tenter d'exécuter une tâche avec EntityManager fermé
            $io->section('2. Tentative d\'exécution avec EntityManager fermé');

            $task = $this->taskRepository->findOneBy(['type' => 'RENT_RECEIPT']);
            if (!$task) {
                $io->error('❌ Tâche RENT_RECEIPT non trouvée');
                return Command::FAILURE;
            }

            try {
                $io->writeln('Tentative d\'exécution de la tâche...');
                $this->taskManagerService->executeTask($task);

                $io->success('✅ Tâche exécutée malgré EntityManager fermé');

            } catch (\Exception $e) {
                $io->error('❌ Erreur capturée: ' . $e->getMessage());

                // Vérifier si c'est bien l'erreur attendue
                if (strpos($e->getMessage(), 'EntityManager is closed') !== false) {
                    $io->writeln('✅ Erreur "EntityManager is closed" détectée et gérée');
                } else {
                    $io->writeln('⚠️ Erreur différente de celle attendue');
                }

                return Command::SUCCESS; // On s'attend à cette erreur
            }

            return Command::SUCCESS;

        } catch (\Exception $e) {
            $io->error('❌ Erreur générale : ' . $e->getMessage());
            return Command::FAILURE;
        }
    }
}
