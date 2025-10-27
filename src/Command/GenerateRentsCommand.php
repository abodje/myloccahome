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
            ->addOption('company', 'c', InputOption::VALUE_REQUIRED, 'ID de la société (génère uniquement pour cette société)', null)
            ->addOption('organization', 'o', InputOption::VALUE_REQUIRED, 'ID de l\'organization (génère pour toutes ses sociétés)', null)
            ->addOption('dry-run', null, InputOption::VALUE_NONE, 'Simulation sans création réelle')
            ->setHelp(
                'Cette commande génère automatiquement les échéances de loyer pour tous les contrats actifs.' . PHP_EOL .
                'Par défaut, elle génère pour le mois suivant.' . PHP_EOL . PHP_EOL .
                'Exemples :' . PHP_EOL .
                '  php bin/console app:generate-rents' . PHP_EOL .
                '  php bin/console app:generate-rents --months-ahead=3' . PHP_EOL .
                '  php bin/console app:generate-rents --month=2026-01' . PHP_EOL .
                '  php bin/console app:generate-rents --company=5' . PHP_EOL .
                '  php bin/console app:generate-rents --organization=2' . PHP_EOL .
                '  php bin/console app:generate-rents --dry-run'
            );
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        $monthOption = $input->getOption('month');
        $monthsAhead = (int) $input->getOption('months-ahead');
        $companyId = $input->getOption('company');
        $organizationId = $input->getOption('organization');
        $dryRun = $input->getOption('dry-run');

        $io->title('🏠 Génération automatique des loyers - LOKAPRO');

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

        // Filtrer les contrats par company/organization si spécifié
        if ($companyId) {
            $company = $this->entityManager->getRepository(\App\Entity\Company::class)->find($companyId);
            if (!$company) {
                $io->error("Société #{$companyId} introuvable");
                return Command::FAILURE;
            }
            $io->info("🏢 Filtrage par société : {$company->getName()}");
            $activeLeases = $this->leaseRepository->findBy(['company' => $company, 'status' => 'Actif']);
        } elseif ($organizationId) {
            $organization = $this->entityManager->getRepository(\App\Entity\Organization::class)->find($organizationId);
            if (!$organization) {
                $io->error("Organisation #{$organizationId} introuvable");
                return Command::FAILURE;
            }
            $io->info("🏢 Filtrage par organization : {$organization->getName()}");
            $activeLeases = $this->leaseRepository->findBy(['organization' => $organization, 'status' => 'Actif']);
        } else {
            // Récupérer tous les contrats actifs
            $activeLeases = $this->leaseRepository->findByStatus('Actif');
        }

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

                // ⚠️ Vérifier que la date est dans la période du bail
                // Ne pas générer de loyer avant le début du bail
                if ($lease->getStartDate() && $dueDate < $lease->getStartDate()) {
                    $leaseSkipped++;
                    continue; // Passer au mois suivant
                }

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

                // 🔒 SÉCURITÉ RENFORCÉE : Vérifier si le loyer existe déjà (avec TOUS les critères)
                $existingPayments = $this->entityManager->getRepository(Payment::class)->findBy([
                    'lease' => $lease,
                    'dueDate' => $dueDate,
                    'type' => 'Loyer'
                ]);

                // 🚨 Si plusieurs paiements existent pour la même date, on alerte
                if (count($existingPayments) > 1) {
                    $io->warning("⚠️  ANOMALIE: {count($existingPayments)} paiements trouvés pour {$lease->getTenant()->getFullName()} - échéance {$dueDate->format('d/m/Y')}");
                    $leaseSkipped++;
                    continue; // Passer au suivant sans créer de doublon
                }

                if (empty($existingPayments)) {
                    if (!$dryRun) {
                        $payment = new Payment();
                        $payment->setLease($lease)
                               ->setDueDate($dueDate)
                               ->setAmount($lease->getMonthlyRent())
                               ->setType('Loyer')
                               ->setStatus('En attente')
                               ->setOrganization($lease->getOrganization()) // ✅ Auto-assign organization
                               ->setCompany($lease->getCompany()); // ✅ Auto-assign company

                        $this->entityManager->persist($payment);
                        
                        // 🔒 Flush immédiatement pour garantir l'insertion en base
                        // Évite les race conditions si la commande est lancée en parallèle
                        $this->entityManager->flush();
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

        // 🔒 Note: Le flush est fait immédiatement après chaque création
        // pour garantir la cohérence et éviter les doublons
        // Plus besoin de flush global ici

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

