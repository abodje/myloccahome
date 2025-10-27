<?php

namespace App\Command;

use App\Entity\Payment;
use App\Repository\LeaseRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(
    name: 'app:diagnose-duplicate-rents',
    description: 'Diagnostique et supprime les loyers en double',
)]
class DiagnoseDuplicateRentsCommand extends Command
{
    public function __construct(
        private EntityManagerInterface $entityManager,
        private LeaseRepository $leaseRepository
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this
            ->addOption('lease-id', 'l', InputOption::VALUE_OPTIONAL, 'ID du contrat à analyser')
            ->addOption('fix', 'f', InputOption::VALUE_NONE, 'Supprimer les doublons trouvés')
            ->addOption('all', 'a', InputOption::VALUE_NONE, 'Analyser tous les contrats');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $io->title('🔍 Diagnostic des loyers en double');

        $leaseId = $input->getOption('lease-id');
        $fix = $input->getOption('fix');
        $all = $input->getOption('all');

        if (!$leaseId && !$all) {
            $io->error('Vous devez spécifier --lease-id=X ou --all');
            return Command::FAILURE;
        }

        // Récupérer les contrats à analyser
        if ($leaseId) {
            $leases = [$this->leaseRepository->find($leaseId)];
            if (!$leases[0]) {
                $io->error("Contrat #{$leaseId} introuvable");
                return Command::FAILURE;
            }
        } else {
            $leases = $this->leaseRepository->findAll();
        }

        $io->info("📊 Analyse de " . count($leases) . " contrat(s)");
        $io->newLine();

        $totalDuplicates = 0;
        $duplicatesDetails = [];

        foreach ($leases as $lease) {
            // Récupérer tous les paiements du contrat
            $payments = $this->entityManager->getRepository(Payment::class)
                ->createQueryBuilder('p')
                ->where('p.lease = :lease')
                ->andWhere('p.type = :type')
                ->setParameter('lease', $lease)
                ->setParameter('type', 'Loyer')
                ->orderBy('p.dueDate', 'ASC')
                ->addOrderBy('p.id', 'ASC')
                ->getQuery()
                ->getResult();

            if (empty($payments)) {
                continue;
            }

            // Grouper par date d'échéance
            $grouped = [];
            foreach ($payments as $payment) {
                $dateKey = $payment->getDueDate()->format('Y-m-d');
                if (!isset($grouped[$dateKey])) {
                    $grouped[$dateKey] = [];
                }
                $grouped[$dateKey][] = $payment;
            }

            // Détecter les doublons
            $leaseDuplicates = [];
            foreach ($grouped as $date => $paymentsForDate) {
                if (count($paymentsForDate) > 1) {
                    $leaseDuplicates[$date] = $paymentsForDate;
                    $totalDuplicates += count($paymentsForDate) - 1;
                }
            }

            if (!empty($leaseDuplicates)) {
                $duplicatesDetails[] = [
                    'lease' => $lease,
                    'duplicates' => $leaseDuplicates
                ];

                $io->section("🔴 Contrat #{$lease->getId()} - {$lease->getTenant()->getFullName()}");
                $io->writeln("📅 Période: {$lease->getStartDate()->format('d/m/Y')} → {$lease->getEndDate()->format('d/m/Y')}");
                $io->writeln("💰 Loyer: {$lease->getMonthlyRent()} CFA");
                $io->writeln("📆 Jour d'échéance: {$lease->getRentDueDay()}");
                $io->newLine();

                foreach ($leaseDuplicates as $date => $paymentsForDate) {
                    $io->warning("❌ {count($paymentsForDate)} paiements pour la date {$date}:");

                    $tableData = [];
                    foreach ($paymentsForDate as $index => $payment) {
                        $tableData[] = [
                            $payment->getId(),
                            $payment->getDueDate()->format('d/m/Y'),
                            $payment->getAmount() . ' CFA',
                            $payment->getStatus(),
                            $index === 0 ? '✅ Garder' : '❌ Supprimer'
                        ];
                    }

                    $io->table(
                        ['ID', 'Échéance', 'Montant', 'Statut', 'Action'],
                        $tableData
                    );
                }
                $io->newLine();
            }
        }

        // Résumé
        $io->section('📊 Résumé');
        if ($totalDuplicates > 0) {
            $io->warning("{$totalDuplicates} doublon(s) détecté(s) dans " . count($duplicatesDetails) . " contrat(s)");

            if ($fix) {
                $io->info('🔧 Suppression des doublons...');
                $deleted = 0;

                foreach ($duplicatesDetails as $detail) {
                    foreach ($detail['duplicates'] as $paymentsForDate) {
                        // Garder le premier, supprimer les autres
                        $keep = array_shift($paymentsForDate);

                        foreach ($paymentsForDate as $payment) {
                            $this->entityManager->remove($payment);
                            $deleted++;
                        }
                    }
                }

                $this->entityManager->flush();
                $io->success("✅ {$deleted} doublon(s) supprimé(s)");
            } else {
                $io->note('💡 Utilisez --fix pour supprimer automatiquement les doublons');
            }
        } else {
            $io->success('✅ Aucun doublon détecté');
        }

        return Command::SUCCESS;
    }
}
