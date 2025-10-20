<?php

namespace App\Command;

use App\Entity\Task;
use App\Service\TaskManagerService;
use App\Service\NotificationService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(
    name: 'app:test-generate-rents-with-accounting',
    description: 'Test la génération de loyers avec création d\'écritures comptables',
)]
class TestGenerateRentsWithAccountingCommand extends Command
{
    private TaskManagerService $taskManagerService;
    private NotificationService $notificationService;
    private EntityManagerInterface $entityManager;

    public function __construct(
        TaskManagerService $taskManagerService,
        NotificationService $notificationService,
        EntityManagerInterface $entityManager
    ) {
        $this->taskManagerService = $taskManagerService;
        $this->notificationService = $notificationService;
        $this->entityManager = $entityManager;
        parent::__construct();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        $io->title('Test de génération de loyers avec écritures comptables');

        // Test 1: Génération directe via NotificationService
        $io->section('Test 1: Génération directe via NotificationService');
        
        $results = $this->notificationService->generateNextMonthRents();
        
        $io->writeln(sprintf('Loyers générés: %d', $results['generated']));
        $io->writeln(sprintf('Loyers ignorés: %d', $results['skipped']));
        if (!empty($results['errors'])) {
            $io->writeln('Erreurs:');
            foreach ($results['errors'] as $error) {
                $io->writeln(sprintf('  - %s', $error));
            }
        }

        // Vérifier les écritures comptables créées
        $accountingEntries = $this->entityManager->getRepository(\App\Entity\AccountingEntry::class)
            ->findBy(['category' => 'LOYER_ATTENDU']);

        $io->writeln(sprintf('Écritures comptables créées: %d', count($accountingEntries)));

        // Test 2: Via TaskManagerService
        $io->section('Test 2: Génération via TaskManagerService');
        
        $task = $this->entityManager->getRepository(Task::class)
            ->findOneBy(['type' => 'GENERATE_RENTS']);

        if (!$task) {
            $io->error('Tâche GENERATE_RENTS non trouvée');
            return Command::FAILURE;
        }

        try {
            $this->taskManagerService->executeTask($task);
            $io->success('Tâche exécutée avec succès !');
            $io->writeln(sprintf('Résultat: %s', $task->getResult()));
        } catch (\Exception $e) {
            $io->error(sprintf('Erreur lors de l\'exécution de la tâche: %s', $e->getMessage()));
            return Command::FAILURE;
        }

        // Statistiques finales
        $io->section('Statistiques finales');
        
        $totalPayments = $this->entityManager->getRepository(\App\Entity\Payment::class)
            ->count(['type' => 'Loyer']);
        
        $totalAccountingEntries = $this->entityManager->getRepository(\App\Entity\AccountingEntry::class)
            ->count(['category' => 'LOYER_ATTENDU']);

        $io->writeln(sprintf('Total paiements loyers: %d', $totalPayments));
        $io->writeln(sprintf('Total écritures comptables LOYER_ATTENDU: %d', $totalAccountingEntries));

        if ($totalPayments > 0 && $totalAccountingEntries > 0) {
            $io->success('✅ Les écritures comptables sont maintenant créées avec les loyers !');
        } else {
            $io->warning('⚠️ Aucune écriture comptable créée. Vérifiez les données de test.');
        }

        return Command::SUCCESS;
    }
}
