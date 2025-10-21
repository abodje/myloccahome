<?php

namespace App\Command;

use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(
    name: 'app:watch-web-errors',
    description: 'Surveille les erreurs spécifiques à l\'interface web',
)]
class WatchWebErrorsCommand extends Command
{
    protected function configure(): void
    {
        $this->addOption('tail-lines', 't', InputOption::VALUE_OPTIONAL, 'Nombre de lignes à afficher', 100);
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        $io->title('Surveillance des erreurs de l\'interface web');

        $tailLines = $input->getOption('tail-lines');
        $logFile = 'var/log/dev.log';

        if (!file_exists($logFile)) {
            $io->error('Fichier de log non trouvé: ' . $logFile);
            return Command::FAILURE;
        }

        $io->section('Instructions');
        $io->writeln([
            '1. Ouvrez votre navigateur',
            '2. Connectez-vous à l\'interface web',
            '3. Essayez de télécharger un document',
            '4. Surveillez les logs ci-dessous',
            '',
            'Appuyez sur Ctrl+C pour arrêter la surveillance'
        ]);

        $io->section('Logs en temps réel');
        $io->writeln('Surveillance des erreurs liées au téléchargement...');
        $io->writeln('');

        // Lire les dernières lignes
        $lines = file($logFile);
        $recentLines = array_slice($lines, -$tailLines);

        foreach ($recentLines as $line) {
            if ($this->isRelevantLine($line)) {
                $io->writeln(trim($line));
            }
        }

        $io->writeln('');
        $io->writeln('--- Surveillance en cours ---');
        $io->writeln('');

        // Surveiller les nouvelles lignes
        $lastSize = filesize($logFile);

        while (true) {
            clearstatcache();
            $currentSize = filesize($logFile);

            if ($currentSize > $lastSize) {
                // Nouvelles lignes ajoutées
                $handle = fopen($logFile, 'r');
                fseek($handle, $lastSize);

                while (($line = fgets($handle)) !== false) {
                    if ($this->isRelevantLine($line)) {
                        $io->writeln(trim($line));
                    }
                }

                fclose($handle);
                $lastSize = $currentSize;
            }

            sleep(1);
        }

        return Command::SUCCESS;
    }

    private function isRelevantLine(string $line): bool
    {
        $keywords = [
            'download',
            'téléchargement',
            'déchiffrement',
            'decrypt',
            'secure',
            'error',
            'exception',
            'SecureDocumentController',
            'SecureFileService',
            'app_secure_document_download',
            'Erreur lors du téléchargement',
            'Erreur lors du déchiffrement'
        ];

        foreach ($keywords as $keyword) {
            if (stripos($line, $keyword) !== false) {
                return true;
            }
        }

        return false;
    }
}
