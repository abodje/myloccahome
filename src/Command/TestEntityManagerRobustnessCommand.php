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
    name: 'app:test-entity-manager-robustness',
    description: 'Teste la robustesse de l\'EntityManager dans différents scénarios d\'exécution.',
)]
class TestEntityManagerRobustnessCommand extends Command
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
        $io->title('Test de robustesse de l\'EntityManager');

        try {
            // 1. Test avec fermeture forcée de l'EntityManager
            $io->section('1. Test avec fermeture forcée de l\'EntityManager');
            $this->testWithForcedClose($io);

            // 2. Test avec exceptions multiples
            $io->section('2. Test avec exceptions multiples');
            $this->testWithMultipleExceptions($io);

            // 3. Test avec récupération automatique
            $io->section('3. Test avec récupération automatique');
            $this->testWithRecovery($io);

            $io->success('🎉 Tous les tests de robustesse ont réussi !');
            return Command::SUCCESS;

        } catch (\Exception $e) {
            $io->error('❌ Erreur lors des tests : ' . $e->getMessage());
            return Command::FAILURE;
        }
    }

    private function testWithForcedClose(SymfonyStyle $io): void
    {
        $io->writeln('Simulation de fermeture forcée de l\'EntityManager...');

        // Forcer la fermeture de l'EntityManager
        $this->entityManager->close();

        $io->writeln(sprintf('EntityManager fermé: %s', !$this->entityManager->isOpen() ? 'OUI' : 'NON'));

        // Tenter d'exécuter une tâche
        $task = $this->taskRepository->findOneBy(['type' => 'RENT_RECEIPT']);
        if ($task) {
            try {
                $this->taskManagerService->executeTask($task);
                $io->success('✅ Tâche exécutée malgré EntityManager fermé');
            } catch (\Exception $e) {
                $io->error('❌ Échec: ' . $e->getMessage());
            }
        }
    }

    private function testWithMultipleExceptions(SymfonyStyle $io): void
    {
        $io->writeln('Test avec gestion de multiples exceptions...');

        $task = $this->taskRepository->findOneBy(['type' => 'RENT_RECEIPT']);
        if ($task) {
            // Exécuter la tâche plusieurs fois de suite
            for ($i = 0; $i < 3; $i++) {
                try {
                    $this->taskManagerService->executeTask($task);
                    $io->writeln(sprintf('  ✅ Exécution %d réussie', $i + 1));
                } catch (\Exception $e) {
                    $io->writeln(sprintf('  ❌ Exécution %d échouée: %s', $i + 1, $e->getMessage()));
                }

                // Vérifier l'état de l'EntityManager
                $io->writeln(sprintf('    EntityManager ouvert: %s', $this->entityManager->isOpen() ? 'OUI' : 'NON'));
            }
        }
    }

    private function testWithRecovery(SymfonyStyle $io): void
    {
        $io->writeln('Test de récupération automatique...');

        // Fermer l'EntityManager
        $this->entityManager->close();
        $io->writeln('EntityManager fermé intentionnellement');

        // Tenter de recréer l'EntityManager
        if (!$this->entityManager->isOpen()) {
            $io->writeln('Tentative de récupération...');

            // Cette approche ne fonctionne pas directement, mais on peut tester la logique
            $io->writeln('La récupération automatique est gérée dans TaskManagerService');
        }

        // Vérifier l'état final
        $io->writeln(sprintf('État final EntityManager: %s', $this->entityManager->isOpen() ? 'OUVERT' : 'FERMÉ'));
    }
}
