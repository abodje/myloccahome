<?php

namespace App\Command;

use App\Service\BackupService;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(
    name: 'app:backup',
    description: 'Crée une sauvegarde complète de la base de données et des fichiers',
)]
class BackupCommand extends Command
{
    public function __construct(
        private BackupService $backupService
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this
            ->addOption('database-only', 'd', InputOption::VALUE_NONE, 'Sauvegarder uniquement la base de données')
            ->addOption('files-only', 'f', InputOption::VALUE_NONE, 'Sauvegarder uniquement les fichiers')
            ->addOption('clean', 'c', InputOption::VALUE_OPTIONAL, 'Nettoyer les sauvegardes de plus de X jours', null);
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        $io->title('💾 Sauvegarde LOKAPRO');

        // Nettoyage si demandé
        if ($input->getOption('clean') !== null) {
            $days = (int) $input->getOption('clean') ?: 30;
            $io->section("🧹 Nettoyage des sauvegardes de plus de {$days} jours");

            $deleted = $this->backupService->cleanOldBackups($days);
            $io->success("{$deleted} fichier(s) supprimé(s)");

            if (!$input->getOption('database-only') && !$input->getOption('files-only')) {
                return Command::SUCCESS;
            }
        }

        // Créer la sauvegarde
        $io->section('📦 Création de la sauvegarde');

        $timestamp = date('Y-m-d_H-i-s');
        $io->writeln("Timestamp : <info>{$timestamp}</info>");
        $io->newLine();

        $results = [
            'database' => null,
            'files' => null
        ];

        try {
            // Sauvegarde base de données
            if (!$input->getOption('files-only')) {
                $io->write('📊 Sauvegarde de la base de données... ');
                $results['database'] = $this->backupService->backupDatabase($timestamp);
                $io->writeln('<info>✅ Terminé</info>');

                if (isset($results['database']['warning'])) {
                    $io->warning($results['database']['warning']);
                }

                $io->writeln(sprintf(
                    '   📁 Fichier : %s (%s)',
                    $results['database']['file'],
                    $this->formatBytes($results['database']['size'])
                ));
                $io->newLine();
            }

            // Sauvegarde fichiers
            if (!$input->getOption('database-only')) {
                $io->write('📁 Sauvegarde des fichiers... ');
                $results['files'] = $this->backupService->backupFiles($timestamp);
                $io->writeln('<info>✅ Terminé</info>');

                if ($results['files']['file']) {
                    $io->writeln(sprintf(
                        '   📁 Fichier : %s (%s)',
                        $results['files']['file'],
                        $this->formatBytes($results['files']['size'])
                    ));
                } else {
                    $io->warning('Aucun fichier à sauvegarder');
                }
                $io->newLine();
            }

            // Résumé
            $io->success([
                '✅ Sauvegarde créée avec succès !',
                '',
                "Timestamp : {$timestamp}",
                "Emplacement : var/backups/",
            ]);

            // Afficher les statistiques
            $stats = $this->backupService->getBackupStatistics();
            $io->section('📊 Statistiques des sauvegardes');
            $io->table(
                ['Métrique', 'Valeur'],
                [
                    ['Nombre total de sauvegardes', $stats['count']],
                    ['Espace utilisé', $stats['total_size_formatted']],
                    ['Sauvegarde la plus récente', $stats['newest']['date'] ?? 'Aucune'],
                ]
            );

            return Command::SUCCESS;

        } catch (\Exception $e) {
            $io->error('Erreur lors de la sauvegarde : ' . $e->getMessage());
            return Command::FAILURE;
        }
    }

    private function formatBytes(int $bytes): string
    {
        $units = ['B', 'KB', 'MB', 'GB'];
        for ($i = 0; $bytes > 1024 && $i < count($units) - 1; $i++) {
            $bytes /= 1024;
        }
        return round($bytes, 2) . ' ' . $units[$i];
    }
}

