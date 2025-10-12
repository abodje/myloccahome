<?php

namespace App\Command;

use App\Entity\Lease;
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
    name: 'app:generate-rents',
    description: 'Génère automatiquement les loyers pour tous les contrats actifs',
)]
class GenerateRentsCommand extends Command
{
    public function __construct(
        private LeaseRepository $leaseRepository,
        private EntityManagerInterface $entityManager
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this
            ->addOption('month', 'm', InputOption::VALUE_REQUIRED, 'Mois à générer (YYYY-MM)', null)
            ->addOption('months-ahead', null, InputOption::VALUE_REQUIRED, 'Nombre de mois à générer à l\'avance', 1)
            ->addOption('dry-run', null, InputOption::VALUE_NONE, 'Simulation sans création réelle')
            ->setHelp(
                'Cette commande génère automatiquement les échéances de loyer pour tous les contrats actifs.' . PHP_EOL .
                'Par défaut, elle génère pour le mois suivant.' . PHP_EOL . PHP_EOL .
                'Exemples :' . PHP_EOL .
                '  php bin/console app:generate-rents' . PHP_EOL .
                '  php bin/console app:generate-rents --months-ahead=3' . PHP_EOL .
                '  php bin/console app:generate-rents --month=2026-01' . PHP_EOL .
                '  php bin/console app:generate-rents --dry-run'
            );
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        $monthOption = $input->getOption('month');
        $monthsAhead = (int) $input->getOption('months-ahead');
        $dryRun = $input->getOption('dry-run');

        $io->title('🏠 Génération automatique des loyers - MYLOCCA');

        if ($dryRun) {
            $io->warning('🔍 MODE SIMULATION - Aucune donnée ne sera enregistrée');
        }

        // Déterminer les mois à générer
        if ($monthOption) {
            try {
                $startMonth = new \DateTime($monthOption . '-01');
            } catch (\Exception $e) {
                $io->error('Format de mois invalide. Utilisez YYYY-MM (ex: 2026-01)');
                return Command::FAILURE;
            }
        } else {
            $startMonth = new \DateTime('first day of next month');
        }

        // Récupérer tous les contrats actifs
        $activeLeases = $this->leaseRepository->findByStatus('Actif');

        if (empty($activeLeases)) {
            $io->warning('Aucun contrat actif trouvé.');
            return Command::SUCCESS;
        }

        $io->info("📊 Contrats actifs trouvés : " . count($activeLeases));
        $io->info("📅 Génération pour {$monthsAhead} mois à partir de " . $startMonth->format('F Y'));
        $io->newLine();

        $totalGenerated = 0;
        $totalSkipped = 0;
        $details = [];

        foreach ($activeLeases as $lease) {
            $leaseGenerated = 0;
            $leaseSkipped = 0;

            for ($i = 0; $i < $monthsAhead; $i++) {
                $targetMonth = clone $startMonth;
                $targetMonth->modify("+{$i} months");

                $dueDate = clone $targetMonth;
                $dueDate->setDate(
                    $targetMonth->format('Y'),
                    $targetMonth->format('n'),
                    $lease->getRentDueDay() ?? 1
                );

                // ⚠️ Vérifier que la date n'excède pas la fin du bail
                if ($lease->getEndDate() && $dueDate > $lease->getEndDate()) {
                    $leaseSkipped++;
                    if ($i === 0) {
                        $details[] = [
                            'tenant' => $lease->getTenant()->getFullName(),
                            'property' => $lease->getProperty()->getAddress(),
                            'status' => '⚠️  Bail expiré le ' . $lease->getEndDate()->format('d/m/Y'),
                            'generated' => 0
                        ];
                    }
                    break; // Arrêter pour ce bail
                }

                // Vérifier si le loyer existe déjà
                $existingPayment = $this->entityManager->getRepository(Payment::class)->findOneBy([
                    'lease' => $lease,
                    'dueDate' => $dueDate,
                    'type' => 'Loyer'
                ]);

                if (!$existingPayment) {
                    if (!$dryRun) {
                        $payment = new Payment();
                        $payment->setLease($lease)
                               ->setDueDate($dueDate)
                               ->setAmount($lease->getMonthlyRent())
                               ->setType('Loyer')
                               ->setStatus('En attente');

                        $this->entityManager->persist($payment);
                    }
                    $leaseGenerated++;
                    $totalGenerated++;
                } else {
                    $leaseSkipped++;
                }
            }

            if ($leaseGenerated > 0 || $i === 0) {
                $details[] = [
                    'tenant' => $lease->getTenant()->getFullName(),
                    'property' => $lease->getProperty()->getAddress(),
                    'status' => $leaseGenerated > 0 ? "✅ {$leaseGenerated} loyer(s)" : '➖ Déjà générés',
                    'generated' => $leaseGenerated
                ];
            }
        }

        // Sauvegarder en base (si pas dry-run)
        if (!$dryRun && $totalGenerated > 0) {
            $this->entityManager->flush();
        }

        // Afficher les résultats
        $io->section('📋 Résultats par contrat');

        if (!empty($details)) {
            $tableData = [];
            foreach ($details as $detail) {
                $tableData[] = [
                    $detail['tenant'],
                    $detail['property'],
                    $detail['status']
                ];
            }

            $io->table(
                ['Locataire', 'Propriété', 'Résultat'],
                $tableData
            );
        }

        $io->section('📊 Résumé global');

        $io->definitionList(
            ['Loyers générés' => $totalGenerated],
            ['Contrats traités' => count($activeLeases)],
            ['Mode' => $dryRun ? 'SIMULATION' : 'RÉEL']
        );

        if ($totalGenerated > 0) {
            if ($dryRun) {
                $io->success("✅ {$totalGenerated} loyer(s) seraient générés (simulation)");
            } else {
                $io->success("✅ {$totalGenerated} loyer(s) générés avec succès !");
            }
        } else {
            $io->info('ℹ️  Aucun nouveau loyer à générer. Tous les loyers sont déjà créés.');
        }

        return Command::SUCCESS;
    }
}

