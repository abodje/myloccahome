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
    name: 'app:test-accounting-config-task',
    description: 'Test la tâche CREATE_ACCOUNTING_CONFIGURATIONS via TaskManagerService',
)]
class TestAccountingConfigTaskCommand extends Command
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

        $io->title('Test de la tâche CREATE_ACCOUNTING_CONFIGURATIONS');

        // Chercher la tâche
        $task = $this->entityManager->getRepository(Task::class)
            ->findOneBy(['type' => 'CREATE_ACCOUNTING_CONFIGURATIONS']);

        if (!$task) {
            $io->error('La tâche CREATE_ACCOUNTING_CONFIGURATIONS n\'a pas été trouvée dans la base de données.');
            $io->writeln('Exécutez d\'abord: php bin/console app:create-default-tasks');
            return Command::FAILURE;
        }

        $io->writeln(sprintf('Tâche trouvée: %s', $task->getName()));
        $io->writeln(sprintf('ID: %d', $task->getId()));
        $io->writeln(sprintf('Fréquence: %s', $task->getFrequency()));
        $io->writeln(sprintf('Statut: %s', $task->getStatus()));

        // Compter les configurations comptables avant l'exécution
        $configsBefore = $this->entityManager->getRepository(\App\Entity\AccountingConfiguration::class)
            ->count([]);

        $io->writeln(sprintf('Configurations comptables avant: %d', $configsBefore));

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

        // Compter les configurations comptables après l'exécution
        $configsAfter = $this->entityManager->getRepository(\App\Entity\AccountingConfiguration::class)
            ->count([]);

        $io->section('Résultats');
        $io->writeln(sprintf('Configurations comptables après: %d (+%d)', $configsAfter, $configsAfter - $configsBefore));

        if ($configsAfter > $configsBefore) {
            $io->success('✅ La tâche a bien créé des configurations comptables !');

            // Afficher les configurations créées
            $configurations = $this->entityManager->getRepository(\App\Entity\AccountingConfiguration::class)
                ->findAll();

            $io->section('Configurations comptables disponibles');
            $io->table(
                ['Type', 'Compte', 'Libellé', 'Sens', 'Catégorie'],
                array_map(function($config) {
                    return [
                        $config->getOperationType(),
                        $config->getAccountNumber(),
                        $config->getAccountLabel(),
                        $config->getEntryType(),
                        $config->getCategory()
                    ];
                }, $configurations)
            );
        } else {
            $io->warning('⚠️ La tâche n\'a pas créé de nouvelles configurations (peut-être qu\'elles existent déjà).');
        }

        return Command::SUCCESS;
    }
}
