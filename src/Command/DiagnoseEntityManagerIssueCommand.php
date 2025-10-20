<?php

namespace App\Command;

use App\Service\TaskManagerService;
use App\Repository\TaskRepository;
use App\Service\RentReceiptService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(
    name: 'app:diagnose-entity-manager-issue',
    description: 'Diagnostique en détail le problème EntityManager dans GENERATE_RENT_DOCUMENTS.',
)]
class DiagnoseEntityManagerIssueCommand extends Command
{
    private TaskManagerService $taskManagerService;
    private TaskRepository $taskRepository;
    private RentReceiptService $rentReceiptService;
    private EntityManagerInterface $entityManager;

    public function __construct(
        TaskManagerService $taskManagerService,
        TaskRepository $taskRepository,
        RentReceiptService $rentReceiptService,
        EntityManagerInterface $entityManager
    ) {
        parent::__construct();
        $this->taskManagerService = $taskManagerService;
        $this->taskRepository = $taskRepository;
        $this->rentReceiptService = $rentReceiptService;
        $this->entityManager = $entityManager;
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $io->title('Diagnostic approfondi du problème EntityManager');

        try {
            // 1. Test de l'EntityManager de base
            $io->section('1. Test de l\'EntityManager de base');
            $this->testBasicEntityManager($io);

            // 2. Test du RentReceiptService
            $io->section('2. Test du RentReceiptService');
            $this->testRentReceiptService($io);

            // 3. Test de la tâche complète
            $io->section('3. Test de la tâche GENERATE_RENT_DOCUMENTS');
            $this->testGenerateRentDocumentsTask($io);

            return Command::SUCCESS;

        } catch (\Exception $e) {
            $io->error('❌ Erreur générale : ' . $e->getMessage());
            return Command::FAILURE;
        }
    }

    private function testBasicEntityManager(SymfonyStyle $io): void
    {
        $io->writeln('Test des opérations de base de l\'EntityManager...');

        try {
            $io->writeln(sprintf('  EntityManager ouvert: %s', $this->entityManager->isOpen() ? 'OUI' : 'NON'));

            // Test d'une requête simple
            $tasks = $this->taskRepository->findAll();
            $io->writeln(sprintf('  Requête findAll(): %d tâches trouvées', count($tasks)));

            // Test d'une requête avec QueryBuilder
            $qb = $this->entityManager->getRepository(\App\Entity\Task::class)
                ->createQueryBuilder('t')
                ->where('t.type = :type')
                ->setParameter('type', 'GENERATE_RENT_DOCUMENTS')
                ->getQuery();

            $task = $qb->getOneOrNullResult();
            $io->writeln(sprintf('  Requête QueryBuilder: %s', $task ? 'Tâche trouvée' : 'Aucune tâche'));

            $io->success('✅ EntityManager de base fonctionne correctement');

        } catch (\Exception $e) {
            $io->error('❌ Erreur avec EntityManager de base: ' . $e->getMessage());
            throw $e;
        }
    }

    private function testRentReceiptService(SymfonyStyle $io): void
    {
        $io->writeln('Test du RentReceiptService...');

        try {
            $monthDate = new \DateTime('first day of this month');
            $io->writeln(sprintf('  Mois de test: %s', $monthDate->format('F Y')));

            $io->writeln('  Test de generateMonthlyReceipts...');
            $receipts = $this->rentReceiptService->generateMonthlyReceipts($monthDate);
            $io->writeln(sprintf('  Résultat: %d quittances générées', count($receipts)));

            $io->writeln('  Test de generateUpcomingNotices...');
            $nextMonth = (clone $monthDate)->modify('+1 month');
            $notices = $this->rentReceiptService->generateUpcomingNotices($nextMonth);
            $io->writeln(sprintf('  Résultat: %d avis générés', count($notices)));

            $io->success('✅ RentReceiptService fonctionne correctement');

        } catch (\Exception $e) {
            $io->error('❌ Erreur avec RentReceiptService: ' . $e->getMessage());

            if (strpos($e->getMessage(), 'EntityManager is closed') !== false) {
                $io->warning('⚠️ L\'erreur "EntityManager is closed" vient du RentReceiptService');
            }

            throw $e;
        }
    }

    private function testGenerateRentDocumentsTask(SymfonyStyle $io): void
    {
        $io->writeln('Test de la tâche GENERATE_RENT_DOCUMENTS...');

        try {
            $task = $this->taskRepository->findOneBy(['type' => 'GENERATE_RENT_DOCUMENTS']);
            if (!$task) {
                $io->error('❌ Tâche GENERATE_RENT_DOCUMENTS non trouvée');
                return;
            }

            if (!$task->isActive()) {
                $task->setStatus('ACTIVE');
                if ($this->entityManager->isOpen()) {
                    $this->entityManager->flush();
                }
                $io->writeln('  Tâche activée');
            }

            $io->writeln(sprintf('  État EntityManager avant: %s',
                $this->entityManager->isOpen() ? 'OUVERT' : 'FERMÉ'));

            // Exécuter la tâche avec capture d'erreur détaillée
            $this->taskManagerService->executeTask($task);

            $io->writeln(sprintf('  État EntityManager après: %s',
                $this->entityManager->isOpen() ? 'OUVERT' : 'FERMÉ'));

            $io->success('✅ Tâche GENERATE_RENT_DOCUMENTS exécutée avec succès');

        } catch (\Exception $e) {
            $io->error('❌ Erreur avec la tâche GENERATE_RENT_DOCUMENTS: ' . $e->getMessage());

            $io->writeln(sprintf('  État EntityManager après erreur: %s',
                $this->entityManager->isOpen() ? 'OUVERT' : 'FERMÉ'));

            // Analyser la stack trace
            $io->writeln('  Stack trace:');
            $trace = $e->getTraceAsString();
            $lines = explode("\n", $trace);
            foreach (array_slice($lines, 0, 10) as $line) {
                $io->writeln('    ' . $line);
            }

            throw $e;
        }
    }
}
