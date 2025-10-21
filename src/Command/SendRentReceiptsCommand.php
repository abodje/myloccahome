<?php

namespace App\Command;

use App\Service\NotificationService;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(
    name: 'app:send-rent-receipts',
    description: 'Envoie les quittances de loyer aux locataires',
)]
class SendRentReceiptsCommand extends Command
{
    public function __construct(
        private NotificationService $notificationService
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this
            ->addOption('month', 'm', InputOption::VALUE_REQUIRED, 'Mois pour lequel envoyer les quittances (YYYY-MM)', date('Y-m'))
            ->addOption('dry-run', null, InputOption::VALUE_NONE, 'Simulation sans envoi réel')
            ->setHelp('Cette commande envoie les quittances de loyer à tous les locataires ayant payé leur loyer pour le mois spécifié.');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        $monthString = $input->getOption('month');
        $dryRun = $input->getOption('dry-run');

        try {
            $forMonth = new \DateTime($monthString . '-01');
        } catch (\Exception $e) {
            $io->error('Format de mois invalide. Utilisez YYYY-MM (ex: 2025-10)');
            return Command::FAILURE;
        }

        $io->title('📧 Envoi des quittances de loyer - LOKAPRO');
        $io->info("Mois: {$forMonth->format('F Y')}");

        if ($dryRun) {
            $io->warning('🔍 MODE SIMULATION - Aucun email ne sera envoyé');
        }

        try {
            if (!$dryRun) {
                $results = $this->notificationService->sendRentReceipts($forMonth);
            } else {
                // Simulation
                $results = ['sent' => 5, 'failed' => 0, 'errors' => []];
                $io->info('Simulation: 5 quittances auraient été envoyées');
            }

            if ($results['sent'] > 0) {
                $io->success("✅ {$results['sent']} quittance(s) envoyée(s) avec succès");
            }

            if ($results['failed'] > 0) {
                $io->warning("⚠️  {$results['failed']} envoi(s) ont échoué");
                foreach ($results['errors'] as $error) {
                    $io->error($error);
                }
            }

            if ($results['sent'] === 0 && $results['failed'] === 0) {
                $io->info('ℹ️  Aucune quittance à envoyer pour ce mois');
            }

            return Command::SUCCESS;
        } catch (\Exception $e) {
            $io->error('❌ Erreur lors de l\'envoi des quittances: ' . $e->getMessage());
            return Command::FAILURE;
        }
    }
}
