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
    name: 'app:simulate-web-execution',
    description: 'Simule l\'exécution via l\'interface web pour reproduire l\'erreur EntityManager.',
)]
class SimulateWebExecutionCommand extends Command
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
        $io->title('Simulation de l\'exécution via l\'interface web');

        try {
            // 1. Simuler une session web avec plusieurs requêtes
            $io->section('1. Simulation d\'une session web');

            // Simuler plusieurs opérations qui pourraient fermer l'EntityManager
            $this->simulateWebOperations($io);

            // 2. Tenter d'exécuter la tâche dans ce contexte
            $io->section('2. Exécution de la tâche dans le contexte web');

            $task = $this->taskRepository->findOneBy(['type' => 'RENT_RECEIPT']);
            if (!$task) {
                $io->error('❌ Tâche RENT_RECEIPT non trouvée');
                return Command::FAILURE;
            }

            $io->writeln(sprintf('État EntityManager avant exécution: %s',
                $this->entityManager->isOpen() ? 'OUVERT' : 'FERMÉ'));

            try {
                $this->taskManagerService->executeTask($task);

                $io->writeln(sprintf('État EntityManager après exécution: %s',
                    $this->entityManager->isOpen() ? 'OUVERT' : 'FERMÉ'));

                $io->success('✅ Tâche exécutée avec succès');

            } catch (\Exception $e) {
                $io->error('❌ Erreur lors de l\'exécution: ' . $e->getMessage());

                $io->writeln(sprintf('État EntityManager après erreur: %s',
                    $this->entityManager->isOpen() ? 'OUVERT' : 'FERMÉ'));

                return Command::FAILURE;
            }

            return Command::SUCCESS;

        } catch (\Exception $e) {
            $io->error('❌ Erreur générale : ' . $e->getMessage());
            return Command::FAILURE;
        }
    }

    private function simulateWebOperations(SymfonyStyle $io): void
    {
        $io->writeln('Simulation d\'opérations web qui pourraient affecter l\'EntityManager...');

        // Simuler des requêtes multiples
        for ($i = 0; $i < 5; $i++) {
            try {
                // Simuler une requête qui pourrait causer des problèmes
                $tasks = $this->taskRepository->findAll();
                $io->writeln(sprintf('  Requête %d: %d tâches trouvées', $i + 1, count($tasks)));

                // Simuler une petite pause
                usleep(100000); // 0.1 seconde

            } catch (\Exception $e) {
                $io->writeln(sprintf('  Requête %d échouée: %s', $i + 1, $e->getMessage()));
            }
        }

        // Vérifier l'état de l'EntityManager
        $io->writeln(sprintf('État EntityManager après simulation: %s',
            $this->entityManager->isOpen() ? 'OUVERT' : 'FERMÉ'));
    }
}
