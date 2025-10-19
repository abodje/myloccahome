<?php

namespace App\Command;

use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Doctrine\ORM\EntityManagerInterface;

#[AsCommand(
    name: 'app:cleanup-demo-organizations',
    description: 'Nettoie les organisations démo en doublon qui n\'ont pas d\'active_subscription_id',
)]
class CleanupDemoOrganizationsCommand extends Command
{
    private EntityManagerInterface $entityManager;

    public function __construct(EntityManagerInterface $entityManager)
    {
        parent::__construct();
        $this->entityManager = $entityManager;
    }

    protected function configure(): void
    {
        $this
            ->setHelp('Cette commande supprime les organisations démo qui n\'ont pas d\'active_subscription_id pour éviter les doublons.');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $io->title('Nettoyage des organisations démo en doublon');

        try {
            // Trouver les organisations démo sans activeSubscription
            $demoOrgs = $this->entityManager->getRepository(\App\Entity\Organization::class)
                ->createQueryBuilder('o')
                ->where('o.isDemo = :demo')
                ->andWhere('o.activeSubscription IS NULL')
                ->setParameter('demo', true)
                ->getQuery()
                ->getResult();

            $io->info(sprintf('Trouvé %d organisations démo sans activeSubscription', count($demoOrgs)));

            if (count($demoOrgs) === 0) {
                $io->success('Aucune organisation démo à nettoyer.');
                return Command::SUCCESS;
            }

            $io->section('Organisations à supprimer :');
            foreach ($demoOrgs as $org) {
                $io->writeln(sprintf('- ID %d: %s (slug: %s)', $org->getId(), $org->getName(), $org->getSlug()));
            }

            if (!$io->confirm('Voulez-vous vraiment supprimer ces organisations démo ?', false)) {
                $io->info('Opération annulée.');
                return Command::SUCCESS;
            }

            $deletedCount = 0;
            foreach ($demoOrgs as $org) {
                try {
                    $io->writeln(sprintf('Suppression de l\'organisation ID %d: %s', $org->getId(), $org->getName()));

                    // 1. Supprimer les documents liés aux tenants
                    $tenants = $this->entityManager->getRepository(\App\Entity\Tenant::class)
                        ->findBy(['organization' => $org]);

                    foreach ($tenants as $tenant) {
                        // Supprimer les documents liés au tenant
                        $documents = $this->entityManager->getRepository(\App\Entity\Document::class)
                            ->findBy(['tenant' => $tenant]);
                        foreach ($documents as $document) {
                            $this->entityManager->remove($document);
                        }

                        // Supprimer les paiements liés au tenant
                        $payments = $this->entityManager->getRepository(\App\Entity\Payment::class)
                            ->createQueryBuilder('p')
                            ->join('p.lease', 'l')
                            ->where('l.tenant = :tenant')
                            ->setParameter('tenant', $tenant)
                            ->getQuery()
                            ->getResult();
                        foreach ($payments as $payment) {
                            $this->entityManager->remove($payment);
                        }

                        // Supprimer les baux du tenant
                        $leases = $this->entityManager->getRepository(\App\Entity\Lease::class)
                            ->findBy(['tenant' => $tenant]);
                        foreach ($leases as $lease) {
                            $this->entityManager->remove($lease);
                        }

                        // Supprimer le tenant
                        $this->entityManager->remove($tenant);
                    }

                    // 2. Supprimer les propriétés et leurs entités liées
                    $properties = $this->entityManager->getRepository(\App\Entity\Property::class)
                        ->findBy(['organization' => $org]);

                    foreach ($properties as $property) {
                        // Supprimer les documents liés à la propriété
                        $documents = $this->entityManager->getRepository(\App\Entity\Document::class)
                            ->findBy(['property' => $property]);
                        foreach ($documents as $document) {
                            $this->entityManager->remove($document);
                        }

                        // Supprimer les demandes de maintenance
                        $maintenanceRequests = $this->entityManager->getRepository(\App\Entity\MaintenanceRequest::class)
                            ->findBy(['property' => $property]);
                        foreach ($maintenanceRequests as $request) {
                            $this->entityManager->remove($request);
                        }

                        // Supprimer la propriété
                        $this->entityManager->remove($property);
                    }

                    // 3. Supprimer les sociétés
                    $companies = $this->entityManager->getRepository(\App\Entity\Company::class)
                        ->findBy(['organization' => $org]);

                    foreach ($companies as $company) {
                        $this->entityManager->remove($company);
                    }

                    // 4. Supprimer les écritures comptables
                    $accountingEntries = $this->entityManager->getRepository(\App\Entity\AccountingEntry::class)
                        ->createQueryBuilder('ae')
                        ->join('ae.property', 'p')
                        ->where('p.organization = :org')
                        ->setParameter('org', $org)
                        ->getQuery()
                        ->getResult();
                    foreach ($accountingEntries as $entry) {
                        $this->entityManager->remove($entry);
                    }

                    // 5. Supprimer les abonnements
                    $subscriptions = $this->entityManager->getRepository(\App\Entity\Subscription::class)
                        ->findBy(['organization' => $org]);
                    foreach ($subscriptions as $subscription) {
                        $this->entityManager->remove($subscription);
                    }

                    // 6. Supprimer l'organisation
                    $this->entityManager->remove($org);
                    $deletedCount++;

                    $io->writeln(sprintf('  ✓ Organisation ID %d supprimée avec succès', $org->getId()));

                } catch (\Exception $e) {
                    $io->error(sprintf('Erreur lors de la suppression de l\'organisation ID %d: %s', $org->getId(), $e->getMessage()));
                }
            }

            $this->entityManager->flush();

            $io->success(sprintf('%d organisations démo supprimées avec succès.', $deletedCount));

            // Afficher les organisations restantes
            $remainingOrgs = $this->entityManager->getRepository(\App\Entity\Organization::class)
                ->createQueryBuilder('o')
                ->where('o.isDemo = :demo')
                ->setParameter('demo', true)
                ->getQuery()
                ->getResult();

            $io->section('Organisations démo restantes :');
            foreach ($remainingOrgs as $org) {
                $io->writeln(sprintf('- ID %d: %s (subscription_id: %s)',
                    $org->getId(),
                    $org->getName(),
                    $org->getActiveSubscription() ? $org->getActiveSubscription()->getId() : 'NULL'
                ));
            }

            return Command::SUCCESS;

        } catch (\Exception $e) {
            $io->error('Erreur lors du nettoyage : ' . $e->getMessage());
            return Command::FAILURE;
        }
    }
}
