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
    name: 'app:simulate-web-execution-generate-rent-documents',
    description: 'Simule l\'exécution web de GENERATE_RENT_DOCUMENTS pour reproduire l\'erreur EntityManager.',
)]
class SimulateWebExecutionGenerateRentDocumentsCommand extends Command
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
        $io->title('Simulation de l\'exécution web de GENERATE_RENT_DOCUMENTS');

        try {
            // 1. Simuler une session web avec plusieurs opérations
            $io->section('1. Simulation d\'une session web');
            $this->simulateWebSession($io);

            // 2. Exécuter la tâche dans ce contexte
            $io->section('2. Exécution de GENERATE_RENT_DOCUMENTS');

            $task = $this->taskRepository->findOneBy(['type' => 'GENERATE_RENT_DOCUMENTS']);
            if (!$task) {
                $io->error('❌ Tâche GENERATE_RENT_DOCUMENTS non trouvée');
                return Command::FAILURE;
            }

            // S'assurer que la tâche est active
            if (!$task->isActive()) {
                $task->setStatus('ACTIVE');
                $this->entityManager->flush();
                $io->writeln('✅ Tâche activée');
            }

            $io->writeln(sprintf('État EntityManager avant exécution: %s',
                $this->entityManager->isOpen() ? 'OUVERT' : 'FERMÉ'));

            try {
                $this->taskManagerService->executeTask($task);

                $io->writeln(sprintf('État EntityManager après exécution: %s',
                    $this->entityManager->isOpen() ? 'OUVERT' : 'FERMÉ'));

                // Afficher les résultats
                $io->section('3. Résultats');
                $io->table(
                    ['Paramètre', 'Valeur'],
                    [
                        ['Quittances générées', $task->getParameter('last_receipts_generated', 0)],
                        ['Avis générés', $task->getParameter('last_notices_generated', 0)],
                        ['Total documents', $task->getParameter('last_total_documents', 0)],
                        ['Dernière erreur', $task->getParameter('last_error', 'Aucune')],
                        ['Dernière exécution', $task->getLastRunAt() ? $task->getLastRunAt()->format('Y-m-d H:i:s') : 'Jamais'],
                    ]
                );

                $io->success('✅ Exécution réussie');

            } catch (\Exception $e) {
                $io->error('❌ Erreur lors de l\'exécution: ' . $e->getMessage());

                $io->writeln(sprintf('État EntityManager après erreur: %s',
                    $this->entityManager->isOpen() ? 'OUVERT' : 'FERMÉ'));

                // Analyser le type d'erreur
                if (strpos($e->getMessage(), 'EntityManager is closed') !== false) {
                    $io->warning('⚠️ Erreur "EntityManager is closed" détectée');
                    $io->writeln('Cette erreur devrait être gérée par notre système de protection.');
                }

                return Command::FAILURE;
            }

            return Command::SUCCESS;

        } catch (\Exception $e) {
            $io->error('❌ Erreur générale : ' . $e->getMessage());
            return Command::FAILURE;
        }
    }

    private function simulateWebSession(SymfonyStyle $io): void
    {
        $io->writeln('Simulation d\'une session web avec plusieurs opérations...');

        // Simuler des requêtes multiples qui pourraient affecter l'EntityManager
        for ($i = 0; $i < 3; $i++) {
            try {
                // Simuler des requêtes de base de données
                $tasks = $this->taskRepository->findAll();
                $io->writeln(sprintf('  Requête %d: %d tâches trouvées', $i + 1, count($tasks)));

                // Simuler une petite pause
                usleep(50000); // 0.05 seconde

            } catch (\Exception $e) {
                $io->writeln(sprintf('  Requête %d échouée: %s', $i + 1, $e->getMessage()));
            }
        }

        // Vérifier l'état de l'EntityManager
        $io->writeln(sprintf('État EntityManager après simulation: %s',
            $this->entityManager->isOpen() ? 'OUVERT' : 'FERMÉ'));
    }
}
