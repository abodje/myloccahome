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
    name: 'app:test-email-tasks',
    description: 'Teste les nouvelles tâches liées aux paramètres email et SMTP',
)]
class TestEmailTasksCommand extends Command
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

        $io->title('Test des tâches email et SMTP');

        // Liste des tâches à tester
        $taskTypes = [
            'INITIALIZE_EMAIL_SETTINGS',
            'TEST_EMAIL_SETTINGS',
            'TEST_SMTP_CONFIGURATION',
            'UPDATE_SMTP_CONFIGURATION'
        ];

        $results = [];

        foreach ($taskTypes as $taskType) {
            $io->section(sprintf('Test de la tâche: %s', $taskType));

            try {
                // Trouver la tâche
                $task = $this->entityManager->getRepository(Task::class)
                    ->findOneBy(['type' => $taskType]);

                if (!$task) {
                    $io->error(sprintf('Tâche %s non trouvée', $taskType));
                    $results[$taskType] = 'Tâche non trouvée';
                    continue;
                }

                $io->writeln(sprintf('Tâche trouvée: %s', $task->getName()));

                // Exécuter la tâche
                $this->taskManagerService->executeTask($task);

                $io->success(sprintf('✅ Tâche %s exécutée avec succès', $taskType));
                $results[$taskType] = 'Succès';

            } catch (\Exception $e) {
                $io->error(sprintf('❌ Erreur lors de l\'exécution de %s: %s', $taskType, $e->getMessage()));
                $results[$taskType] = 'Erreur: ' . $e->getMessage();
            }
        }

        // Résumé
        $io->section('Résumé des tests');

        $table = [];
        foreach ($results as $taskType => $result) {
            $table[] = [$taskType, $result];
        }

        $io->table(['Type de tâche', 'Résultat'], $table);

        $successCount = count(array_filter($results, fn($result) => $result === 'Succès'));
        $totalCount = count($results);

        if ($successCount === $totalCount) {
            $io->success(sprintf('🎉 Toutes les tâches (%d/%d) ont été exécutées avec succès !', $successCount, $totalCount));
            return Command::SUCCESS;
        } else {
            $io->warning(sprintf('⚠️ %d/%d tâches ont été exécutées avec succès', $successCount, $totalCount));
            return Command::FAILURE;
        }
    }
}
