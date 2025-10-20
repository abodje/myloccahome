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
    name: 'app:test-property-status-task',
    description: 'Test la tâche UPDATE_PROPERTY_STATUS via le TaskManagerService',
)]
class TestPropertyStatusTaskCommand extends Command
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

        $io->title('Test de la tâche UPDATE_PROPERTY_STATUS via TaskManagerService');

        // Créer une tâche de test
        $task = new Task();
        $task->setName('Test - Mise à jour du statut des propriétés')
             ->setType('UPDATE_PROPERTY_STATUS')
             ->setDescription('Test de la mise à jour automatique du statut des propriétés')
             ->setFrequency('MANUAL')
             ->setParameters([
                 'log_details' => true
             ])
             ->setStatus('ACTIVE');

        $this->entityManager->persist($task);
        $this->entityManager->flush();

        $io->writeln(sprintf('Tâche créée avec l\'ID: %d', $task->getId()));

        try {
            // Exécuter la tâche
            $io->section('Exécution de la tâche...');
            $this->taskManagerService->executeTask($task);

            $io->success('Tâche exécutée avec succès !');
            $io->writeln(sprintf('Résultat: %s', $task->getResult()));

        } catch (\Exception $e) {
            $io->error(sprintf('Erreur lors de l\'exécution de la tâche: %s', $e->getMessage()));
            return Command::FAILURE;
        }

        // Nettoyer la tâche de test
        $this->entityManager->remove($task);
        $this->entityManager->flush();

        $io->writeln('Tâche de test supprimée.');

        return Command::SUCCESS;
    }
}
