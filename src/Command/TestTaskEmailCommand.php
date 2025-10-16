<?php

namespace App\Command;

use App\Service\TaskManagerService;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(
    name: 'app:test-task-email',
    description: 'Teste la tâche de configuration email via TaskManager',
)]
class TestTaskEmailCommand extends Command
{
    public function __construct(
        private TaskManagerService $taskManagerService
    ) {
        parent::__construct();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        $io->title('🧪 Test de la Tâche Email via TaskManager');

        try {
            // Créer les tâches par défaut si elles n'existent pas
            $this->taskManagerService->createDefaultTasks();
            $io->success('✅ Tâches par défaut créées/mises à jour');

            // Créer une tâche de test email spécifique
            $task = new \App\Entity\Task();
            $task->setName('Test Email Configuration - ' . date('Y-m-d H:i:s'))
                 ->setType('TEST_EMAIL_CONFIG')
                 ->setDescription('Test de configuration email via TaskManager')
                 ->setFrequency('MANUAL')
                 ->setParameters(['email' => 'test@example.com'])
                 ->setStatus('ACTIVE');

            $task->calculateNextRun();

            $entityManager = $this->taskManagerService->getEntityManager();
            $entityManager->persist($task);
            $entityManager->flush();

            $io->success('✅ Tâche de test email créée');

            // Exécuter la tâche
            $io->section('📤 Exécution de la tâche...');
            $this->taskManagerService->executeTask($task);

            $io->success('✅ Tâche de test email exécutée avec succès !');
            $io->note('Vérifiez les logs pour plus de détails');

            return Command::SUCCESS;

        } catch (\Exception $e) {
            $io->error('❌ Erreur lors du test : ' . $e->getMessage());
            return Command::FAILURE;
        }
    }
}
