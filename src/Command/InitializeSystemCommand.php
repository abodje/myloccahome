<?php

namespace App\Command;

use App\Service\TaskManagerService;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(
    name: 'app:initialize-system',
    description: 'Initialise le système MYLOCCA : crée les tâches et plans par défaut',
)]
class InitializeSystemCommand extends Command
{
    public function __construct(
        private TaskManagerService $taskManager
    ) {
        parent::__construct();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        $io->title('🚀 Initialisation du Système MYLOCCA');

        $io->section('Création des ressources par défaut...');

        try {
            $results = $this->taskManager->initializeSystem();

            if (count($results['errors']) > 0) {
                $io->warning('Erreurs rencontrées :');
                foreach ($results['errors'] as $error) {
                    $io->error($error);
                }
            }

            $io->success('✅ Tâches créées : ' . $results['tasks_created']);
            $io->success('✅ Plans d\'abonnement créés : ' . $results['plans_created']);

            $io->newLine();
            $io->success('🎉 Système initialisé avec succès !');

            $io->note([
                'Les tâches planifiées sont disponibles dans /admin/taches',
                'Les plans d\'abonnement sont disponibles sur /inscription/plans',
                'Utilisez "php bin/console app:run-due-tasks" pour exécuter les tâches',
            ]);

            return Command::SUCCESS;

        } catch (\Exception $e) {
            $io->error('Erreur lors de l\'initialisation : ' . $e->getMessage());
            return Command::FAILURE;
        }
    }
}

