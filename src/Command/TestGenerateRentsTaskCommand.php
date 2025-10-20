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
    name: 'app:test-generate-rents-task',
    description: 'Test la tâche GENERATE_RENTS via TaskManagerService',
)]
class TestGenerateRentsTaskCommand extends Command
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

        $io->title('Test de la tâche GENERATE_RENTS');

        // Chercher la tâche
        $task = $this->entityManager->getRepository(Task::class)
            ->findOneBy(['type' => 'GENERATE_RENTS']);

        if (!$task) {
            $io->error('La tâche GENERATE_RENTS n\'a pas été trouvée dans la base de données.');
            return Command::FAILURE;
        }

        $io->writeln(sprintf('Tâche trouvée: %s', $task->getName()));
        $io->writeln(sprintf('ID: %d', $task->getId()));
        $io->writeln(sprintf('Fréquence: %s', $task->getFrequency()));
        $io->writeln(sprintf('Statut: %s', $task->getStatus()));

        // Compter les écritures comptables avant l'exécution
        $entriesBefore = $this->entityManager->getRepository(\App\Entity\AccountingEntry::class)
            ->count(['category' => 'LOYER_ATTENDU']);

        $paymentsBefore = $this->entityManager->getRepository(\App\Entity\Payment::class)
            ->count(['type' => 'Loyer']);

        $io->writeln(sprintf('Écritures comptables avant: %d', $entriesBefore));
        $io->writeln(sprintf('Paiements loyers avant: %d', $paymentsBefore));

        try {
            // Exécuter la tâche
            $io->section('Exécution de la tâche...');
            $this->taskManagerService->executeTask($task);

            $io->success('Tâche exécutée avec succès !');
            $io->writeln(sprintf('Résultat: %s', $task->getResult()));
            $io->writeln(sprintf('Nombre d\'exécutions: %d', $task->getRunCount()));
            $io->writeln(sprintf('Succès: %d', $task->getSuccessCount()));

        } catch (\Exception $e) {
            $io->error(sprintf('Erreur lors de l\'exécution de la tâche: %s', $e->getMessage()));
            $io->writeln(sprintf('Échecs: %d', $task->getFailureCount()));
            $io->writeln(sprintf('Dernière erreur: %s', $task->getLastError()));
            return Command::FAILURE;
        }

        // Compter les écritures comptables après l'exécution
        $entriesAfter = $this->entityManager->getRepository(\App\Entity\AccountingEntry::class)
            ->count(['category' => 'LOYER_ATTENDU']);

        $paymentsAfter = $this->entityManager->getRepository(\App\Entity\Payment::class)
            ->count(['type' => 'Loyer']);

        $io->section('Résultats');
        $io->writeln(sprintf('Écritures comptables après: %d (+%d)', $entriesAfter, $entriesAfter - $entriesBefore));
        $io->writeln(sprintf('Paiements loyers après: %d (+%d)', $paymentsAfter, $paymentsAfter - $paymentsBefore));

        if ($entriesAfter > $entriesBefore && $paymentsAfter > $paymentsBefore) {
            $io->success('✅ La tâche crée bien des paiements ET des écritures comptables !');
        } elseif ($paymentsAfter > $paymentsBefore) {
            $io->warning('⚠️ La tâche crée des paiements mais pas d\'écritures comptables.');
        } else {
            $io->warning('⚠️ La tâche n\'a pas créé de nouveaux paiements (peut-être qu\'ils existent déjà).');
        }

        return Command::SUCCESS;
    }
}
