<?php

namespace App\Command;

use App\Service\RentReceiptService;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(
    name: 'app:generate-rent-documents',
    description: 'Génère les quittances de loyer et avis d\'échéances automatiquement',
)]
class GenerateRentDocumentsCommand extends Command
{
    public function __construct(
        private RentReceiptService $rentReceiptService
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this
            ->addOption('month', 'm', InputOption::VALUE_OPTIONAL, 'Mois pour lequel générer les documents (format: YYYY-MM)', date('Y-m'))
            ->addOption('receipts-only', 'r', InputOption::VALUE_NONE, 'Générer uniquement les quittances')
            ->addOption('notices-only', 'n', InputOption::VALUE_NONE, 'Générer uniquement les avis d\'échéance')
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        $monthStr = $input->getOption('month');
        $receiptsOnly = $input->getOption('receipts-only');
        $noticesOnly = $input->getOption('notices-only');

        try {
            $month = new \DateTime($monthStr . '-01');
        } catch (\Exception $e) {
            $io->error('Format de mois invalide. Utilisez le format YYYY-MM (ex: 2025-10)');
            return Command::FAILURE;
        }

        $io->title('Génération des Documents de Loyer');
        $io->section('Mois : ' . $month->format('F Y'));

        $totalGenerated = 0;

        // Générer les quittances de loyer
        if (!$noticesOnly) {
            $io->section('📄 Génération des Quittances de Loyer');
            $io->text('Recherche des paiements payés pour le mois de ' . $month->format('F Y') . '...');

            try {
                $receipts = $this->rentReceiptService->generateMonthlyReceipts($month);
                $io->success(sprintf('✅ %d quittance(s) générée(s)', count($receipts)));
                $totalGenerated += count($receipts);

                if (count($receipts) > 0) {
                    $io->listing(array_map(
                        fn($receipt) => sprintf(
                            'Quittance #%d - %s (%s)',
                            $receipt->getId(),
                            $receipt->getTitle(),
                            $receipt->getTenant()->getFullName()
                        ),
                        $receipts
                    ));
                }
            } catch (\Exception $e) {
                $io->error('Erreur lors de la génération des quittances : ' . $e->getMessage());
            }
        }

        // Générer les avis d'échéance
        if (!$receiptsOnly) {
            $io->section('⏰ Génération des Avis d\'Échéance');

            // Pour le mois suivant
            $nextMonth = (clone $month)->modify('+1 month');
            $io->text('Recherche des paiements à venir pour le mois de ' . $nextMonth->format('F Y') . '...');

            try {
                $notices = $this->rentReceiptService->generateUpcomingNotices($nextMonth);
                $io->success(sprintf('✅ %d avis d\'échéance généré(s)', count($notices)));
                $totalGenerated += count($notices);

                if (count($notices) > 0) {
                    $io->listing(array_map(
                        fn($notice) => sprintf(
                            'Avis #%d - %s (%s)',
                            $notice->getId(),
                            $notice->getTitle(),
                            $notice->getTenant()->getFullName()
                        ),
                        $notices
                    ));
                }
            } catch (\Exception $e) {
                $io->error('Erreur lors de la génération des avis : ' . $e->getMessage());
            }
        }

        $io->newLine();
        $io->success(sprintf('🎉 Total : %d document(s) généré(s) avec succès !', $totalGenerated));

        return Command::SUCCESS;
    }
}

