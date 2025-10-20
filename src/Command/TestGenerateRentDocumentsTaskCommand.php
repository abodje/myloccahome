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
    name: 'app:test-generate-rent-documents-task',
    description: 'Teste la tâche GENERATE_RENT_DOCUMENTS pour vérifier que l\'erreur EntityManager est corrigée.',
)]
class TestGenerateRentDocumentsTaskCommand extends Command
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
        $io->title('Test de la tâche GENERATE_RENT_DOCUMENTS');

        try {
            // 1. Vérifier l'état de l'EntityManager avant
            $io->section('1. État initial de l\'EntityManager');
            $io->writeln(sprintf('EntityManager ouvert: %s', $this->entityManager->isOpen() ? 'OUI' : 'NON'));

            // 2. Trouver la tâche GENERATE_RENT_DOCUMENTS
            $io->section('2. Recherche de la tâche GENERATE_RENT_DOCUMENTS');
            $task = $this->taskRepository->findOneBy(['type' => 'GENERATE_RENT_DOCUMENTS']);

            if (!$task) {
                $io->error('❌ Tâche GENERATE_RENT_DOCUMENTS non trouvée');
                $io->writeln('Assurez-vous d\'avoir exécuté app:initialize-system pour créer les tâches par défaut.');
                return Command::FAILURE;
            }

            $io->writeln(sprintf('✅ Tâche trouvée: %s', $task->getName()));
            $io->writeln(sprintf('ID: %d', $task->getId()));

            // 3. Exécuter la tâche
            $io->section('3. Exécution de la tâche');
            $io->writeln('Exécution de la tâche de génération des documents de loyer...');

            try {
                $this->taskManagerService->executeTask($task);

                // 4. Vérifier l'état de l'EntityManager après
                $io->section('4. État de l\'EntityManager après exécution');
                $io->writeln(sprintf('EntityManager ouvert: %s', $this->entityManager->isOpen() ? 'OUI' : 'NON'));

                // 5. Afficher les résultats
                $io->section('5. Résultats de l\'exécution');
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

                $io->success('🎉 Tâche exécutée avec succès !');
                $io->writeln('L\'erreur "EntityManager is closed" a été corrigée pour GENERATE_RENT_DOCUMENTS.');

                return Command::SUCCESS;

            } catch (\Exception $e) {
                $io->error('❌ Erreur lors de l\'exécution de la tâche : ' . $e->getMessage());

                // Vérifier l'état de l'EntityManager après l'erreur
                $io->section('État de l\'EntityManager après l\'erreur');
                $io->writeln(sprintf('EntityManager ouvert: %s', $this->entityManager->isOpen() ? 'OUI' : 'NON'));

                $io->writeln('Trace complète :');
                $io->writeln($e->getTraceAsString());

                return Command::FAILURE;
            }

        } catch (\Exception $e) {
            $io->error('❌ Erreur générale : ' . $e->getMessage());
            $io->writeln('Trace : ' . $e->getTraceAsString());
            return Command::FAILURE;
        }
    }
}
