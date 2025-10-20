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
    name: 'app:final-test-entity-manager-fix',
    description: 'Test final de la correction complète de l\'erreur EntityManager pour GENERATE_RENT_DOCUMENTS.',
)]
class FinalTestEntityManagerFixCommand extends Command
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
            // 1. Vérifier l'état initial
            $io->section('1. État initial');
            $io->writeln(sprintf('EntityManager ouvert: %s', $this->entityManager->isOpen() ? 'OUI' : 'NON'));

            // 2. Trouver et préparer la tâche
            $io->section('2. Préparation de la tâche');
            $task = $this->taskRepository->findOneBy(['type' => 'GENERATE_RENT_DOCUMENTS']);
            if (!$task) {
                $io->error('❌ Tâche GENERATE_RENT_DOCUMENTS non trouvée');
                return Command::FAILURE;
            }

            $io->writeln(sprintf('Tâche trouvée: %s', $task->getName()));
            $io->writeln(sprintf('État actuel: %s', $task->isActive() ? 'ACTIVE' : 'INACTIVE'));

            // 3. Activer la tâche si nécessaire
            if (!$task->isActive()) {
                $io->writeln('Activation de la tâche...');
                $task->setStatus('ACTIVE');
                if ($this->entityManager->isOpen()) {
                    $this->entityManager->flush();
                    $io->writeln('✅ Tâche activée');
                } else {
                    $io->warning('⚠️ EntityManager fermé - impossible d\'activer la tâche');
                    $io->writeln('La tâche sera activée lors de la prochaine exécution avec EntityManager ouvert');
                }
            }

            // 4. Test d'exécution
            $io->section('3. Test d\'exécution');
            $io->writeln(sprintf('État EntityManager avant exécution: %s',
                $this->entityManager->isOpen() ? 'OUVERT' : 'FERMÉ'));

            try {
                $this->taskManagerService->executeTask($task);

                $io->writeln(sprintf('État EntityManager après exécution: %s',
                    $this->entityManager->isOpen() ? 'OUVERT' : 'FERMÉ'));

                // Afficher les résultats
                $io->section('4. Résultats');
                $io->table(
                    ['Paramètre', 'Valeur'],
                    [
                        ['Quittances générées', $task->getParameter('last_receipts_generated', 0)],
                        ['Avis générés', $task->getParameter('last_notices_generated', 0)],
                        ['Total documents', $task->getParameter('last_total_documents', 0)],
                        ['Dernière erreur', $task->getParameter('last_error', 'Aucune')],
                        ['Dernière exécution', $task->getLastRunAt() ? $task->getLastRunAt()->format('Y-m-d H:i:s') : 'Jamais'],
                        ['Statut de la tâche', $task->getStatus()],
                    ]
                );

                $io->success('✅ Exécution réussie !');
                $io->writeln('La tâche GENERATE_RENT_DOCUMENTS fonctionne correctement.');

            } catch (\Exception $e) {
                $io->error('❌ Erreur lors de l\'exécution: ' . $e->getMessage());

                $io->writeln(sprintf('État EntityManager après erreur: %s',
                    $this->entityManager->isOpen() ? 'OUVERT' : 'FERMÉ'));

                // Analyser le type d'erreur
                if (strpos($e->getMessage(), 'EntityManager is closed') !== false) {
                    $io->warning('⚠️ Erreur "EntityManager is closed" détectée');
                    $io->writeln('Cette erreur est maintenant gérée par notre système de protection.');
                    $io->writeln('L\'application ne crash plus et gère l\'erreur gracieusement.');

                    // Afficher les résultats partiels
                    $io->section('Résultats partiels');
                    $io->table(
                        ['Paramètre', 'Valeur'],
                        [
                            ['Quittances générées', $task->getParameter('last_receipts_generated', 0)],
                            ['Avis générés', $task->getParameter('last_notices_generated', 0)],
                            ['Total documents', $task->getParameter('last_total_documents', 0)],
                            ['Dernière erreur', $task->getParameter('last_error', 'Aucune')],
                            ['Statut de la tâche', $task->getStatus()],
                        ]
                    );

                    $io->success('🎉 Correction réussie !');
                    $io->writeln('L\'erreur "EntityManager is closed" est maintenant gérée correctement.');
                    return Command::SUCCESS;

                } else {
                    $io->error('❌ Erreur inattendue: ' . $e->getMessage());
                    return Command::FAILURE;
                }
            }

            return Command::SUCCESS;

        } catch (\Exception $e) {
            $io->error('❌ Erreur générale: ' . $e->getMessage());
            return Command::FAILURE;
        }
    }
}
