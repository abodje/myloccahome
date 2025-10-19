<?php

namespace App\Command;

use App\Service\DemoEnvironmentService;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

/**
 * @AsCommand(name="app:demo:cleanup", description="Nettoie automatiquement les environnements de démo expirés")
 */
class DemoCleanupCommand extends Command
{
    private DemoEnvironmentService $demoService;

    public function __construct(DemoEnvironmentService $demoService)
    {
        $this->demoService = $demoService;
        parent::__construct();
    }

    protected function configure(): void
    {
        $this
            ->addOption('dry-run', null, InputOption::VALUE_NONE, 'Simuler le nettoyage sans supprimer')
            ->addOption('force', null, InputOption::VALUE_NONE, 'Forcer le nettoyage sans confirmation')
            ->setHelp('Cette commande nettoie automatiquement les environnements de démo expirés.');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        $io->title('🧹 Nettoyage des environnements de démo');

        // Obtenir les statistiques
        $stats = $this->demoService->getDemoStatistics();

        $io->section('📊 Statistiques actuelles');
        $io->table(
            ['Métrique', 'Valeur'],
            [
                ['Total démos', $stats['total_demos']],
                ['Démos actives', $stats['active_demos']],
                ['Démos expirées', $stats['expired_demos']],
                ['Expirent bientôt', $stats['expiring_soon']],
            ]
        );

        if ($stats['expired_demos'] === 0) {
            $io->success('✅ Aucune démo expirée à nettoyer.');
            return Command::SUCCESS;
        }

        // Mode dry-run
        if ($input->getOption('dry-run')) {
            $io->note('🔍 Mode simulation activé - aucune suppression réelle');
            $io->info("Démos qui seraient supprimées : {$stats['expired_demos']}");
            return Command::SUCCESS;
        }

        // Confirmation
        if (!$input->getOption('force')) {
            if (!$io->confirm("Voulez-vous supprimer {$stats['expired_demos']} démo(s) expirée(s) ?", false)) {
                $io->info('Nettoyage annulé.');
                return Command::SUCCESS;
            }
        }

        // Exécuter le nettoyage
        $io->section('🧹 Nettoyage en cours...');

        $result = $this->demoService->cleanupExpiredDemos();

        if ($result['success']) {
            $io->success("✅ {$result['message']}");

            if (!empty($result['cleaned_demos'])) {
                $io->section('🗑️ Démos supprimées');
                $io->listing($result['cleaned_demos']);
            }
        } else {
            $io->error("❌ Erreur lors du nettoyage : {$result['message']}");
            return Command::FAILURE;
        }

        // Afficher les nouvelles statistiques
        $newStats = $this->demoService->getDemoStatistics();
        $io->section('📊 Nouvelles statistiques');
        $io->table(
            ['Métrique', 'Avant', 'Après'],
            [
                ['Total démos', $stats['total_demos'], $newStats['total_demos']],
                ['Démos actives', $stats['active_demos'], $newStats['active_demos']],
                ['Démos expirées', $stats['expired_demos'], $newStats['expired_demos']],
            ]
        );

        return Command::SUCCESS;
    }
}
