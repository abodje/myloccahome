<?php

// Commande pour corriger les utilisateurs tenant sans entité Tenant
// À exécuter avec: php bin/console app:fix-tenant-users

namespace App\Command;

use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(
    name: 'app:fix-tenant-users',
    description: 'Corrige les utilisateurs avec ROLE_TENANT qui n\'ont pas d\'entité Tenant associée.',
)]
class FixTenantUsersCommand extends Command
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
            ->setDescription('Associe les utilisateurs ROLE_TENANT avec des entités Tenant manquantes')
            ->addOption('dry-run', null, InputOption::VALUE_NONE, 'Affiche ce qui serait fait sans exécuter les modifications')
            ->setHelp('Cette commande trouve les utilisateurs avec le rôle ROLE_TENANT qui n\'ont pas d\'entité Tenant associée et les crée automatiquement.');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $dryRun = $input->getOption('dry-run');

        $io->title('🔧 Correction des utilisateurs Tenant');
        if ($dryRun) {
            $io->note('Mode DRY-RUN : Aucune modification ne sera effectuée');
        }

        // Trouver les utilisateurs avec ROLE_TENANT mais sans entité Tenant
        $users = $this->entityManager->getRepository(\App\Entity\User::class)
            ->createQueryBuilder('u')
            ->leftJoin('u.tenant', 't')
            ->where('u.roles LIKE :role')
            ->andWhere('t.id IS NULL')
            ->setParameter('role', '%ROLE_TENANT%')
            ->getQuery()
            ->getResult();

        $io->text("Utilisateurs avec ROLE_TENANT sans entité Tenant: " . count($users));

        if (empty($users)) {
            $io->success('✅ Tous les utilisateurs tenant ont une entité Tenant associée');
            return Command::SUCCESS;
        }

        $io->section('Utilisateurs à corriger:');
        foreach ($users as $user) {
            $io->text("  - {$user->getEmail()} (ID: {$user->getId()})");
        }

        if (!$io->confirm('Voulez-vous créer des entités Tenant pour ces utilisateurs ?')) {
            $io->text('Opération annulée');
            return Command::SUCCESS;
        }

        $created = 0;
        foreach ($users as $user) {
            try {
                // Créer une entité Tenant
                $tenant = new \App\Entity\Tenant();
                $tenant->setFirstName($user->getFirstName() ?? 'Prénom');
                $tenant->setLastName($user->getLastName() ?? 'Nom');
                $tenant->setEmail($user->getEmail());
                $tenant->setPhone($user->getMobilePhone() ?? '');
                $tenant->setUser($user);

                // Associer à l'organisation et la société de l'utilisateur
                if ($user->getOrganization()) {
                    $tenant->setOrganization($user->getOrganization());
                } else {
                    $io->warning("⚠️ Utilisateur {$user->getEmail()} n'a pas d'organisation - tenant créé sans organisation");
                }
                // Ne pas définir company si elle est null pour éviter l'erreur de contrainte
                if ($user->getCompany()) {
                    $tenant->setCompany($user->getCompany());
                }

                if (!$dryRun) {
                    $this->entityManager->persist($tenant);
                }
                $created++;

                if ($dryRun) {
                    $io->text("🔍 [DRY-RUN] Tenant serait créé pour {$user->getEmail()}");
                } else {
                    $io->text("✅ Tenant créé pour {$user->getEmail()}");
                }
            } catch (\Exception $e) {
                $io->error("❌ Erreur pour {$user->getEmail()}: " . $e->getMessage());
            }
        }

        if ($created > 0) {
            if (!$dryRun) {
                $this->entityManager->flush();
                $io->success("✅ $created entités Tenant créées avec succès");
            } else {
                $io->success("🔍 [DRY-RUN] $created entités Tenant seraient créées");
            }
        }

        // Vérifier le calendrier maintenant
        $io->section('Test du calendrier après correction');

        $testUser = $users[0];
        $tenant = $testUser->getTenant();

        if ($tenant) {
            $io->text("Utilisateur de test: {$testUser->getEmail()}");
            $io->text("Tenant associé: {$tenant->getFullName()}");

            // Test des paiements
            $paymentRepo = $this->entityManager->getRepository(\App\Entity\Payment::class);
            $payments = $paymentRepo->findByTenantWithFilters($tenant->getId());
            $io->text("Paiements trouvés: " . count($payments));

            // Test des baux
            $leaseRepo = $this->entityManager->getRepository(\App\Entity\Lease::class);
            $leases = $leaseRepo->findBy(['tenant' => $tenant]);
            $io->text("Baux trouvés: " . count($leases));

            if (count($payments) > 0 || count($leases) > 0) {
                $io->success('🎉 Le calendrier devrait maintenant fonctionner !');
            } else {
                $io->warning('⚠️ Aucune donnée trouvée pour ce tenant. Vérifiez les données.');
            }
        }

        return Command::SUCCESS;
    }
}
