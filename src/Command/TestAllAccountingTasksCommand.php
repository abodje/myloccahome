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
    name: 'app:test-all-accounting-tasks',
    description: 'Teste toutes les tâches comptables du TaskManagerService',
)]
class TestAllAccountingTasksCommand extends Command
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

        $io->title('Test de toutes les tâches comptables');

        // Liste des tâches comptables à tester
        $accountingTasks = [
            'CREATE_ACCOUNTING_CONFIGURATIONS',
            'TEST_ACCOUNTING_CONFIG',
            'CHECK_ACCOUNTING_ENTRIES',
            'TEST_RENT_GENERATION_WITH_CONFIG',
            'DEMO_ACCOUNTING_SYSTEM',
            'FIX_ACCOUNTING_TABLE',
            'SETUP_ACCOUNTING_SYSTEM'
        ];

        $results = [];

        foreach ($accountingTasks as $taskType) {
            $io->section(sprintf('Test de la tâche: %s', $taskType));

            $task = $this->entityManager->getRepository(Task::class)
                ->findOneBy(['type' => $taskType]);

            if (!$task) {
                $io->error(sprintf('❌ Tâche %s non trouvée', $taskType));
                $results[$taskType] = 'Non trouvée';
                continue;
            }

            try {
                $this->taskManagerService->executeTask($task);
                $io->success(sprintf('✅ Tâche %s exécutée avec succès', $taskType));
                $io->writeln(sprintf('   Résultat: %s', $task->getResult()));
                $results[$taskType] = 'Succès';
            } catch (\Exception $e) {
                $io->error(sprintf('❌ Erreur lors de l\'exécution de %s: %s', $taskType, $e->getMessage()));
                $results[$taskType] = 'Erreur';
            }
        }

        // Résumé final
        $io->section('Résumé des tests');

        $successCount = 0;
        $errorCount = 0;
        $notFoundCount = 0;

        foreach ($results as $taskType => $result) {
            $status = match($result) {
                'Succès' => '✅',
                'Erreur' => '❌',
                'Non trouvée' => '⚠️',
                default => '❓'
            };

            $io->writeln(sprintf('%s %s: %s', $status, $taskType, $result));

            match($result) {
                'Succès' => $successCount++,
                'Erreur' => $errorCount++,
                'Non trouvée' => $notFoundCount++,
                default => null
            };
        }

        $io->writeln('');
        $io->writeln(sprintf('Total: %d tâches testées', count($results)));
        $io->writeln(sprintf('Succès: %d', $successCount));
        $io->writeln(sprintf('Erreurs: %d', $errorCount));
        $io->writeln(sprintf('Non trouvées: %d', $notFoundCount));

        if ($errorCount === 0 && $notFoundCount === 0) {
            $io->success('🎉 Toutes les tâches comptables fonctionnent correctement !');
            return Command::SUCCESS;
        } else {
            $io->warning(sprintf('⚠️ %d tâche(s) ont des problèmes', $errorCount + $notFoundCount));
            return Command::FAILURE;
        }
    }
}
