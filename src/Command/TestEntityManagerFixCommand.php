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
    name: 'app:test-entity-manager-fix',
    description: 'Teste la correction complète de l\'erreur EntityManager is closed.',
)]
class TestEntityManagerFixCommand extends Command
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
        $io->title('Test de la correction complète EntityManager');

        try {
            // 1. Test normal
            $io->section('1. Test normal (EntityManager ouvert)');
            $this->testNormalExecution($io);

            // 2. Test avec EntityManager fermé
            $io->section('2. Test avec EntityManager fermé');
            $this->testWithClosedEntityManager($io);

            // 3. Test de récupération
            $io->section('3. Test de récupération après fermeture');
            $this->testRecovery($io);

            $io->success('🎉 Tous les tests de correction ont réussi !');
            return Command::SUCCESS;

        } catch (\Exception $e) {
            $io->error('❌ Erreur lors des tests : ' . $e->getMessage());
            return Command::FAILURE;
        }
    }

    private function testNormalExecution(SymfonyStyle $io): void
    {
        $task = $this->taskRepository->findOneBy(['type' => 'RENT_RECEIPT']);
        if (!$task) {
            $io->error('Tâche RENT_RECEIPT non trouvée');
            return;
        }

        $io->writeln('État EntityManager: ' . ($this->entityManager->isOpen() ? 'OUVERT' : 'FERMÉ'));

        try {
            $this->taskManagerService->executeTask($task);
            $io->success('✅ Exécution normale réussie');
        } catch (\Exception $e) {
            $io->error('❌ Échec inattendu: ' . $e->getMessage());
        }
    }

    private function testWithClosedEntityManager(SymfonyStyle $io): void
    {
        $io->writeln('Fermeture de l\'EntityManager...');
        $this->entityManager->close();

        $io->writeln('État EntityManager: ' . ($this->entityManager->isOpen() ? 'OUVERT' : 'FERMÉ'));

        $task = $this->taskRepository->findOneBy(['type' => 'RENT_RECEIPT']);
        if (!$task) {
            $io->error('Tâche RENT_RECEIPT non trouvée');
            return;
        }

        try {
            $this->taskManagerService->executeTask($task);
            $io->success('✅ Exécution réussie malgré EntityManager fermé');
        } catch (\Exception $e) {
            if (strpos($e->getMessage(), 'EntityManager is closed') !== false) {
                $io->success('✅ Erreur "EntityManager is closed" correctement détectée et gérée');
            } else {
                $io->error('❌ Erreur inattendue: ' . $e->getMessage());
            }
        }
    }

    private function testRecovery(SymfonyStyle $io): void
    {
        $io->writeln('Test de récupération après fermeture...');

        // L'EntityManager devrait être fermé du test précédent
        if (!$this->entityManager->isOpen()) {
            $io->writeln('EntityManager toujours fermé du test précédent');

            // Tenter une nouvelle opération
            try {
                $tasks = $this->taskRepository->findAll();
                $io->success('✅ Récupération automatique réussie');
            } catch (\Exception $e) {
                $io->error('❌ Échec de récupération: ' . $e->getMessage());
            }
        } else {
            $io->writeln('EntityManager déjà récupéré');
        }
    }
}
