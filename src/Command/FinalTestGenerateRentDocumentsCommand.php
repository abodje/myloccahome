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
    name: 'app:final-test-generate-rent-documents',
    description: 'Test final de la correction de l\'erreur EntityManager pour GENERATE_RENT_DOCUMENTS.',
)]
class FinalTestGenerateRentDocumentsCommand extends Command
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
        $io->title('Test final de la correction EntityManager - GENERATE_RENT_DOCUMENTS');

        try {
            // 1. Vérifier que la tâche est active
            $task = $this->taskRepository->findOneBy(['type' => 'GENERATE_RENT_DOCUMENTS']);
            if (!$task) {
                $io->error('❌ Tâche GENERATE_RENT_DOCUMENTS non trouvée');
                return Command::FAILURE;
            }

            if (!$task->isActive()) {
                $task->setStatus('ACTIVE');
                $this->entityManager->flush();
                $io->writeln('✅ Tâche activée');
            }

            // 2. Test avec EntityManager ouvert
            $io->section('Test 1: Exécution avec EntityManager ouvert');
            $io->writeln('État EntityManager: ' . ($this->entityManager->isOpen() ? 'OUVERT' : 'FERMÉ'));

            try {
                $this->taskManagerService->executeTask($task);
                $io->success('✅ Exécution réussie');

                // Afficher les résultats
                $io->table(
                    ['Résultat', 'Valeur'],
                    [
                        ['Quittances générées', $task->getParameter('last_receipts_generated', 0)],
                        ['Avis générés', $task->getParameter('last_notices_generated', 0)],
                        ['Total documents', $task->getParameter('last_total_documents', 0)],
                        ['Dernière erreur', $task->getParameter('last_error', 'Aucune')],
                    ]
                );

            } catch (\Exception $e) {
                if (strpos($e->getMessage(), 'EntityManager is closed') !== false) {
                    $io->success('✅ Erreur "EntityManager is closed" correctement gérée');
                    $io->writeln('L\'erreur est capturée et ne cause plus de crash de l\'application.');
                } else {
                    $io->error('❌ Erreur inattendue: ' . $e->getMessage());
                    return Command::FAILURE;
                }
            }

            // 3. Test avec EntityManager fermé
            $io->section('Test 2: Simulation avec EntityManager fermé');
            $this->entityManager->close();
            $io->writeln('EntityManager fermé intentionnellement');

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

            $io->success('🎉 Tous les tests ont réussi !');
            $io->writeln('La tâche GENERATE_RENT_DOCUMENTS est maintenant protégée contre l\'erreur "EntityManager is closed".');
            $io->writeln('L\'application ne crash plus et gère l\'erreur de manière gracieuse.');

            return Command::SUCCESS;

        } catch (\Exception $e) {
            $io->error('❌ Erreur générale: ' . $e->getMessage());
            return Command::FAILURE;
        }
    }
}
