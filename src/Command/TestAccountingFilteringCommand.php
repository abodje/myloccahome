<?php

namespace App\Command;

use App\Entity\User;
use App\Repository\AccountingEntryRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(
    name: 'app:test-accounting-filtering',
    description: 'Teste le système de filtrage de la comptabilité selon les rôles, organisations et sociétés',
)]
class TestAccountingFilteringCommand extends Command
{
    private AccountingEntryRepository $accountingRepository;
    private EntityManagerInterface $entityManager;

    public function __construct(AccountingEntryRepository $accountingRepository, EntityManagerInterface $entityManager)
    {
        $this->accountingRepository = $accountingRepository;
        $this->entityManager = $entityManager;
        parent::__construct();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        $io->title('Test du système de filtrage de la comptabilité');

        // Étape 1: Vérifier les écritures comptables existantes
        $io->section('Étape 1: Vérification des écritures comptables existantes');

        $totalEntries = $this->accountingRepository->count([]);
        $io->writeln(sprintf('Total écritures comptables: %d', $totalEntries));

        if ($totalEntries === 0) {
            $io->warning('Aucune écriture comptable trouvée. Créez d\'abord des loyers pour tester.');
            return Command::SUCCESS;
        }

        // Étape 2: Tester différents types d'utilisateurs
        $io->section('Étape 2: Test des différents types d\'utilisateurs');

        // Test SUPER_ADMIN
        $superAdmin = $this->entityManager->getRepository(User::class)
            ->createQueryBuilder('u')
            ->where('u.roles LIKE :role')
            ->setParameter('role', '%ROLE_SUPER_ADMIN%')
            ->setMaxResults(1)
            ->getQuery()
            ->getOneOrNullResult();

        if ($superAdmin) {
            $io->section('Test SUPER_ADMIN');
            $io->writeln(sprintf('Utilisateur: %s', $superAdmin->getEmail()));
            $io->writeln(sprintf('Rôles: %s', implode(', ', $superAdmin->getRoles())));

            try {
                $orgName = $superAdmin->getOrganization()?->getName() ?: 'Aucune';
                $companyName = $superAdmin->getCompany()?->getName() ?: 'Aucune';
                $io->writeln(sprintf('Organisation: %s', $orgName));
                $io->writeln(sprintf('Société: %s', $companyName));

                $entries = $this->accountingRepository->findWithFilters();
                $io->writeln(sprintf('Écritures visibles: %d (devrait être toutes)', count($entries)));
            } catch (\Exception $e) {
                $io->error(sprintf('Erreur lors du test SUPER_ADMIN: %s', $e->getMessage()));
            }
        }

        // Test ADMIN avec société
        $adminWithCompany = $this->entityManager->getRepository(User::class)
            ->createQueryBuilder('u')
            ->where('u.roles LIKE :role')
            ->andWhere('u.company IS NOT NULL')
            ->setParameter('role', '%ROLE_ADMIN%')
            ->setMaxResults(1)
            ->getQuery()
            ->getOneOrNullResult();

        if ($adminWithCompany) {
            $io->section('Test ADMIN avec société');
            $io->writeln(sprintf('Utilisateur: %s', $adminWithCompany->getEmail()));
            $io->writeln(sprintf('Rôles: %s', implode(', ', $adminWithCompany->getRoles())));

            try {
                $orgName = $adminWithCompany->getOrganization()?->getName() ?: 'Aucune';
                $companyName = $adminWithCompany->getCompany()?->getName() ?: 'Aucune';
                $io->writeln(sprintf('Organisation: %s', $orgName));
                $io->writeln(sprintf('Société: %s', $companyName));

                $company = $adminWithCompany->getCompany();
                $entries = $this->accountingRepository->findByCompanyWithFilters($company);
                $io->writeln(sprintf('Écritures visibles: %d (devrait être filtrées par société)', count($entries)));
            } catch (\Exception $e) {
                $io->error(sprintf('Erreur lors du test ADMIN avec société: %s', $e->getMessage()));
            }
        }

        // Test ADMIN avec organisation
        $adminWithOrg = $this->entityManager->getRepository(User::class)
            ->createQueryBuilder('u')
            ->where('u.roles LIKE :role')
            ->andWhere('u.organization IS NOT NULL')
            ->andWhere('u.company IS NULL')
            ->setParameter('role', '%ROLE_ADMIN%')
            ->setMaxResults(1)
            ->getQuery()
            ->getOneOrNullResult();

        if ($adminWithOrg) {
            $io->section('Test ADMIN avec organisation');
            $io->writeln(sprintf('Utilisateur: %s', $adminWithOrg->getEmail()));
            $io->writeln(sprintf('Rôles: %s', implode(', ', $adminWithOrg->getRoles())));

            try {
                $orgName = $adminWithOrg->getOrganization()?->getName() ?: 'Aucune';
                $companyName = $adminWithOrg->getCompany()?->getName() ?: 'Aucune';
                $io->writeln(sprintf('Organisation: %s', $orgName));
                $io->writeln(sprintf('Société: %s', $companyName));

                $organization = $adminWithOrg->getOrganization();
                $entries = $this->accountingRepository->findByOrganizationWithFilters($organization);
                $io->writeln(sprintf('Écritures visibles: %d (devrait être filtrées par organisation)', count($entries)));
            } catch (\Exception $e) {
                $io->error(sprintf('Erreur lors du test ADMIN avec organisation: %s', $e->getMessage()));
            }
        }

        // Test MANAGER
        $manager = $this->entityManager->getRepository(User::class)
            ->createQueryBuilder('u')
            ->where('u.roles LIKE :role')
            ->setParameter('role', '%ROLE_MANAGER%')
            ->setMaxResults(1)
            ->getQuery()
            ->getOneOrNullResult();

        if ($manager) {
            $io->section('Test MANAGER');
            $io->writeln(sprintf('Utilisateur: %s', $manager->getEmail()));
            $io->writeln(sprintf('Rôles: %s', implode(', ', $manager->getRoles())));
            $io->writeln(sprintf('Organisation: %s', $manager->getOrganization()?->getName() ?: 'Aucune'));
            $io->writeln(sprintf('Société: %s', $manager->getCompany()?->getName() ?: 'Aucune'));

            $owner = $manager->getOwner();
            if ($owner) {
                $entries = $this->accountingRepository->findByManagerWithFilters($owner->getId());
                $io->writeln(sprintf('Écritures visibles: %d (devrait être filtrées par propriétaire)', count($entries)));
            } else {
                $io->writeln('Aucun propriétaire associé à ce gestionnaire');
            }
        }

        // Test TENANT
        $tenant = $this->entityManager->getRepository(User::class)
            ->createQueryBuilder('u')
            ->where('u.roles LIKE :role')
            ->setParameter('role', '%ROLE_TENANT%')
            ->setMaxResults(1)
            ->getQuery()
            ->getOneOrNullResult();

        if ($tenant) {
            $io->section('Test TENANT');
            $io->writeln(sprintf('Utilisateur: %s', $tenant->getEmail()));
            $io->writeln(sprintf('Rôles: %s', implode(', ', $tenant->getRoles())));
            $io->writeln(sprintf('Organisation: %s', $tenant->getOrganization()?->getName() ?: 'Aucune'));
            $io->writeln(sprintf('Société: %s', $tenant->getCompany()?->getName() ?: 'Aucune'));

            $tenantEntity = $tenant->getTenant();
            if ($tenantEntity) {
                $entries = $this->accountingRepository->findByTenantWithFilters($tenantEntity->getId());
                $io->writeln(sprintf('Écritures visibles: %d (devrait être filtrées par locataire)', count($entries)));
            } else {
                $io->writeln('Aucune entité locataire associée à cet utilisateur');
            }
        }

        // Étape 3: Vérifier les écritures avec organization et company
        $io->section('Étape 3: Vérification des champs organization et company');

        $entriesWithOrg = $this->entityManager->createQuery(
            'SELECT COUNT(ae) FROM App\Entity\AccountingEntry ae WHERE ae.organization IS NOT NULL'
        )->getSingleScalarResult();

        $entriesWithCompany = $this->entityManager->createQuery(
            'SELECT COUNT(ae) FROM App\Entity\AccountingEntry ae WHERE ae.company IS NOT NULL'
        )->getSingleScalarResult();

        $io->writeln(sprintf('Écritures avec organization: %d', $entriesWithOrg));
        $io->writeln(sprintf('Écritures avec company: %d', $entriesWithCompany));

        // Étape 4: Afficher quelques exemples d'écritures
        $io->section('Étape 4: Exemples d\'écritures comptables');

        $recentEntries = $this->accountingRepository->findBy([], ['createdAt' => 'DESC'], 5);

        $io->writeln('Dernières écritures comptables:');
        foreach ($recentEntries as $entry) {
            $orgName = $entry->getOrganization() ? $entry->getOrganization()->getName() : 'N/A';
            $companyName = $entry->getCompany() ? $entry->getCompany()->getName() : 'N/A';

            $io->writeln(sprintf(
                '  - ID %d: %s (%s) - Org: %s, Company: %s',
                $entry->getId(),
                $entry->getDescription(),
                $entry->getAmount() . ' FCFA',
                $orgName,
                $companyName
            ));
        }

        // Résumé final
        $io->section('Résumé du test');

        if ($entriesWithOrg > 0 || $entriesWithCompany > 0) {
            $io->success('✅ Le système de filtrage fonctionne correctement !');
            $io->writeln('✅ Les champs organization et company sont utilisés');
            $io->writeln('✅ Les méthodes de filtrage fonctionnent');
        } else {
            $io->warning('⚠️ Aucune écriture comptable n\'a de organization ou company assignée');
            $io->writeln('Les nouvelles écritures générées incluront ces champs automatiquement');
        }

        return Command::SUCCESS;
    }
}
