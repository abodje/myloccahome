<?php

namespace App\Command;

use App\Service\CpanelApiService;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(
    name: 'app:demo:cpanel-test',
    description: 'Teste la connexion et les fonctions de l\'API cPanel',
)]
class TestCpanelApiCommand extends Command
{
    public function __construct(
        private CpanelApiService $cpanelService
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this
            ->addOption('create', 'c', InputOption::VALUE_NONE, 'Créer un environnement de test')
            ->addOption('delete', 'd', InputOption::VALUE_NONE, 'Supprimer l\'environnement de test')
            ->addOption('demo-id', null, InputOption::VALUE_OPTIONAL, 'ID de démo personnalisé', 'test' . time());
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $io->title('🧪 Test de l\'API cPanel');

        $demoId = $input->getOption('demo-id');
        $create = $input->getOption('create');
        $delete = $input->getOption('delete');

        if (!$create && !$delete) {
            $io->error('Vous devez spécifier --create ou --delete');
            return Command::FAILURE;
        }

        if ($create) {
            return $this->testCreate($io, $demoId);
        }

        if ($delete) {
            return $this->testDelete($io, $demoId);
        }

        return Command::SUCCESS;
    }

    private function testCreate(SymfonyStyle $io, string $demoId): int
    {
        $io->section('📦 Création d\'un environnement de démo de test');
        $io->writeln("ID de démo: <info>{$demoId}</info>");
        $io->newLine();

        $io->writeln('⏳ Création en cours...');
        $result = $this->cpanelService->createDemoEnvironment($demoId);

        if ($result['success']) {
            $io->success('✅ Environnement de démo créé avec succès!');

            $io->section('📋 Détails de l\'environnement');
            $io->table(
                ['Ressource', 'Valeur'],
                [
                    ['Sous-domaine', $result['subdomain']],
                    ['Base de données', $result['database']],
                    ['Utilisateur BDD', $result['db_user']],
                    ['Mot de passe BDD', $result['db_password']],
                ]
            );

            $io->note([
                '💾 Conservez ces informations en lieu sûr',
                '🌐 URL d\'accès: https://' . $result['subdomain'],
                '🗑️  Pour supprimer: php bin/console app:demo:cpanel-test --delete --demo-id=' . $demoId,
            ]);

            return Command::SUCCESS;
        } else {
            $io->error('❌ Échec de la création de l\'environnement');

            if (!empty($result['errors'])) {
                $io->section('🐛 Erreurs rencontrées');
                foreach ($result['errors'] as $error) {
                    $io->writeln("  • {$error}");
                }
            }

            return Command::FAILURE;
        }
    }

    private function testDelete(SymfonyStyle $io, string $demoId): int
    {
        $io->section('🗑️  Suppression d\'un environnement de démo de test');
        $io->writeln("ID de démo: <info>{$demoId}</info>");
        $io->newLine();

        if (!$io->confirm('Êtes-vous sûr de vouloir supprimer cet environnement ?', false)) {
            $io->warning('Suppression annulée');
            return Command::SUCCESS;
        }

        $io->writeln('⏳ Suppression en cours...');
        $result = $this->cpanelService->deleteDemoEnvironment($demoId);

        if ($result['success']) {
            $io->success('✅ Environnement de démo supprimé avec succès!');
            return Command::SUCCESS;
        } else {
            $io->error('❌ Échec de la suppression de l\'environnement');

            if (!empty($result['errors'])) {
                $io->section('🐛 Erreurs rencontrées');
                foreach ($result['errors'] as $error) {
                    $io->writeln("  • {$error}");
                }
            }

            return Command::FAILURE;
        }
    }
}
