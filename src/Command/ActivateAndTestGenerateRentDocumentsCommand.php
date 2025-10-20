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
    name: 'app:activate-and-test-generate-rent-documents',
    description: 'Active et teste la tâche GENERATE_RENT_DOCUMENTS pour vérifier la correction de l\'erreur EntityManager.',
)]
class ActivateAndTestGenerateRentDocumentsCommand extends Command
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
        $io->title('Activation et test de la tâche GENERATE_RENT_DOCUMENTS');

        try {
            // 1. Trouver et activer la tâche
            $io->section('1. Activation de la tâche');
            $task = $this->taskRepository->findOneBy(['type' => 'GENERATE_RENT_DOCUMENTS']);

            if (!$task) {
                $io->error('❌ Tâche GENERATE_RENT_DOCUMENTS non trouvée');
                return Command::FAILURE;
            }

            $io->writeln(sprintf('Tâche trouvée: %s', $task->getName()));
            $io->writeln(sprintf('État actuel: %s', $task->isActive() ? 'ACTIVE' : 'INACTIVE'));

            if (!$task->isActive()) {
                $task->setStatus('ACTIVE');
                $this->entityManager->flush();
                $io->success('✅ Tâche activée avec succès');
            } else {
                $io->writeln('Tâche déjà active');
            }

            // 2. Tester l'exécution
            $io->section('2. Test d\'exécution');
            $io->writeln('État EntityManager avant: ' . ($this->entityManager->isOpen() ? 'OUVERT' : 'FERMÉ'));

            try {
                $this->taskManagerService->executeTask($task);

                $io->writeln('État EntityManager après: ' . ($this->entityManager->isOpen() ? 'OUVERT' : 'FERMÉ'));

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

                $io->success('🎉 Test réussi ! L\'erreur EntityManager est corrigée.');
                return Command::SUCCESS;

            } catch (\Exception $e) {
                $io->error('❌ Erreur lors de l\'exécution: ' . $e->getMessage());
                $io->writeln('État EntityManager après erreur: ' . ($this->entityManager->isOpen() ? 'OUVERT' : 'FERMÉ'));
                return Command::FAILURE;
            }

        } catch (\Exception $e) {
            $io->error('❌ Erreur générale: ' . $e->getMessage());
            return Command::FAILURE;
        }
    }
}
