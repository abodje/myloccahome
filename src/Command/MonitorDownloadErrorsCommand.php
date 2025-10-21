<?php

namespace App\Command;

use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(
    name: 'app:monitor-download-errors',
    description: 'Surveille les erreurs de téléchargement en temps réel',
)]
class MonitorDownloadErrorsCommand extends Command
{
    protected function configure(): void
    {
        $this->addOption('tail-lines', 't', InputOption::VALUE_OPTIONAL, 'Nombre de lignes à afficher', 50);
        $this->addOption('follow', 'f', InputOption::VALUE_NONE, 'Suivre les logs en temps réel');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        $io->title('Surveillance des erreurs de téléchargement');

        $tailLines = $input->getOption('tail-lines');
        $follow = $input->getOption('follow');

        // Vérifier les logs Symfony
        $logFile = 'var/log/dev.log';
        if (file_exists($logFile)) {
            $io->section('Logs Symfony (dernières ' . $tailLines . ' lignes)');

            if ($follow) {
                $io->writeln('Surveillance en temps réel... (Ctrl+C pour arrêter)');
                $io->writeln('');

                // Utiliser tail -f pour suivre les logs
                $command = "tail -f -n $tailLines $logFile";
                if (PHP_OS_FAMILY === 'Windows') {
                    $command = "powershell Get-Content $logFile -Wait -Tail $tailLines";
                }

                passthru($command);
            } else {
                $lines = file($logFile);
                $recentLines = array_slice($lines, -$tailLines);

                foreach ($recentLines as $line) {
                    if (strpos($line, 'download') !== false ||
                        strpos($line, 'téléchargement') !== false ||
                        strpos($line, 'error') !== false ||
                        strpos($line, 'exception') !== false) {
                        $io->writeln(trim($line));
                    }
                }
            }
        } else {
            $io->error('Fichier de log Symfony non trouvé: ' . $logFile);
        }

        // Vérifier les logs d'erreur PHP
        $phpLogFile = ini_get('error_log');
        if ($phpLogFile && file_exists($phpLogFile)) {
            $io->section('Logs PHP');
            $lines = file($phpLogFile);
            $recentLines = array_slice($lines, -20);

            foreach ($recentLines as $line) {
                if (strpos($line, 'download') !== false ||
                    strpos($line, 'téléchargement') !== false) {
                    $io->writeln(trim($line));
                }
            }
        }

        // Instructions pour le débogage
        $io->section('Instructions de débogage');

        $instructions = [
            '1. Ouvrez votre navigateur et essayez de télécharger un document',
            '2. Surveillez les logs avec cette commande:',
            '   php bin/console app:monitor-download-errors --follow',
            '3. Ou vérifiez les logs manuellement:',
            '   tail -f var/log/dev.log',
            '4. Notez l\'erreur exacte qui apparaît',
            '5. Vérifiez que vous êtes bien connecté à l\'interface web'
        ];

        foreach ($instructions as $instruction) {
            $io->writeln($instruction);
        }

        return Command::SUCCESS;
    }
}
