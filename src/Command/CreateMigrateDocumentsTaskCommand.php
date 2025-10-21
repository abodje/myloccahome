<?php

namespace App\Command;

use App\Entity\Task;
use App\Service\TaskManagerService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(
    name: 'app:create-migrate-documents-task',
    description: 'Crée une tâche de migration des documents de sécurité',
)]
class CreateMigrateDocumentsTaskCommand extends Command
{
    public function __construct(
        private EntityManagerInterface $entityManager,
        private TaskManagerService $taskManagerService
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this
            ->addOption('dry-run', null, InputOption::VALUE_NONE, 'Mode simulation')
            ->addOption('backup', null, InputOption::VALUE_NONE, 'Créer une sauvegarde')
            ->addOption('batch-size', null, InputOption::VALUE_REQUIRED, 'Taille des lots', 10)
            ->addOption('frequency', null, InputOption::VALUE_REQUIRED, 'Fréquence d\'exécution', 'ONCE')
            ->addOption('active', null, InputOption::VALUE_NONE, 'Activer la tâche immédiatement')
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        $io->title('Création de la tâche de migration des documents de sécurité');

        // Vérifier si une tâche de migration existe déjà
        $existingTask = $this->entityManager->getRepository(Task::class)
            ->findOneBy(['type' => 'MIGRATE_DOCUMENTS_SECURITY']);

        if ($existingTask) {
            $io->warning('Une tâche de migration des documents existe déjà.');

            if (!$io->confirm('Voulez-vous la remplacer ?', false)) {
                $io->info('Opération annulée.');
                return Command::SUCCESS;
            }

            $this->entityManager->remove($existingTask);
            $this->entityManager->flush();
        }

        // Créer la nouvelle tâche
        $task = new Task();
        $task->setName('Migration des documents de sécurité');
        $task->setType('MIGRATE_DOCUMENTS_SECURITY');
        $task->setDescription('Migre tous les documents existants vers le système de sécurité avec chiffrement');
        $task->setFrequency($input->getOption('frequency'));
        $task->setStatus($input->getOption('active') ? 'ACTIVE' : 'INACTIVE');

        // Paramètres de la tâche
        $parameters = [
            'dry_run' => $input->getOption('dry-run'),
            'backup' => $input->getOption('backup'),
            'batch_size' => (int) $input->getOption('batch-size'),
        ];
        $task->setParameters($parameters);

        // Calculer la prochaine exécution
        if ($input->getOption('active')) {
            $task->calculateNextRun();
        }

        $this->entityManager->persist($task);
        $this->entityManager->flush();

        $io->success('Tâche de migration des documents créée avec succès !');

        $io->table(
            ['Propriété', 'Valeur'],
            [
                ['ID', $task->getId()],
                ['Nom', $task->getName()],
                ['Type', $task->getType()],
                ['Statut', $task->getStatus()],
                ['Fréquence', $task->getFrequency()],
                ['Mode simulation', $parameters['dry_run'] ? 'Oui' : 'Non'],
                ['Sauvegarde', $parameters['backup'] ? 'Oui' : 'Non'],
                ['Taille des lots', $parameters['batch_size']],
                ['Prochaine exécution', $task->getNextRunAt() ? $task->getNextRunAt()->format('Y-m-d H:i:s') : 'Non programmée'],
            ]
        );

        if ($input->getOption('active')) {
            $io->info('La tâche est active et sera exécutée automatiquement.');
        } else {
            $io->info('La tâche est inactive. Utilisez l\'option --active pour l\'activer.');
        }

        $io->note([
            'Pour exécuter la tâche manuellement :',
            'php bin/console app:task-manager:run --task-id=' . $task->getId(),
            '',
            'Pour surveiller les tâches :',
            'php bin/console app:task-manager:list',
        ]);

        return Command::SUCCESS;
    }
}
