<?php

namespace App\Command;

use App\Service\AuditLogService;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(
    name: 'app:audit:cleanup',
    description: 'Nettoie les anciens enregistrements d\'audit log',
)]
class AuditCleanupCommand extends Command
{
    public function __construct(
        private AuditLogService $auditLogService
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this
            ->addOption('days', 'd', InputOption::VALUE_OPTIONAL, 'Nombre de jours à conserver', 90)
            ->addOption('force', 'f', InputOption::VALUE_NONE, 'Forcer la suppression sans confirmation');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $days = (int) $input->getOption('days');
        $force = $input->getOption('force');

        if ($days < 30) {
            $io->error('La période minimum est de 30 jours pour des raisons de sécurité.');
            return Command::FAILURE;
        }

        $io->title('🧹 Nettoyage de l\'Audit Log');
        $io->info("Suppression des enregistrements de plus de {$days} jours");

        if (!$force) {
            if (!$io->confirm('Êtes-vous sûr de vouloir continuer ?', false)) {
                $io->warning('Opération annulée.');
                return Command::SUCCESS;
            }
        }

        try {
            $deleted = $this->auditLogService->cleanOldLogs($days);

            $io->success([
                "✅ Nettoyage terminé avec succès !",
                "{$deleted} enregistrement(s) supprimé(s).",
            ]);

            return Command::SUCCESS;
        } catch (\Exception $e) {
            $io->error('Erreur lors du nettoyage : ' . $e->getMessage());
            return Command::FAILURE;
        }
    }
}

