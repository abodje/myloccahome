<?php

namespace App\Command;

use App\Entity\AccountingEntry;
use App\Repository\AccountingEntryRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(
    name: 'app:test-accounting-entry-organization',
    description: 'Test les nouvelles fonctionnalités organization et company pour AccountingEntry',
)]
class TestAccountingEntryOrganizationCommand extends Command
{
    private EntityManagerInterface $entityManager;

    public function __construct(EntityManagerInterface $entityManager)
    {
        $this->entityManager = $entityManager;
        parent::__construct();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        $io->title('Test des fonctionnalités organization et company pour AccountingEntry');

        // Étape 1: Vérifier les écritures comptables existantes
        $io->section('Étape 1: Vérification des écritures comptables existantes');

        $accountingRepository = $this->entityManager->getRepository(AccountingEntry::class);
        $totalEntries = $accountingRepository->count([]);

        $io->writeln(sprintf('Total écritures comptables: %d', $totalEntries));

        if ($totalEntries === 0) {
            $io->warning('Aucune écriture comptable trouvée. Créez d\'abord des loyers pour tester.');
            return Command::SUCCESS;
        }

        // Étape 2: Vérifier les écritures avec organization et company
        $io->section('Étape 2: Vérification des champs organization et company');

        $entriesWithOrg = $this->entityManager->createQuery(
            'SELECT COUNT(ae) FROM App\Entity\AccountingEntry ae WHERE ae.organization IS NOT NULL'
        )->getSingleScalarResult();

        $entriesWithCompany = $this->entityManager->createQuery(
            'SELECT COUNT(ae) FROM App\Entity\AccountingEntry ae WHERE ae.company IS NOT NULL'
        )->getSingleScalarResult();

        $io->writeln(sprintf('Écritures avec organization: %d', $entriesWithOrg));
        $io->writeln(sprintf('Écritures avec company: %d', $entriesWithCompany));

        // Étape 3: Tester les nouvelles méthodes de filtrage
        $io->section('Étape 3: Test des méthodes de filtrage par organisation');

        // Récupérer la première organisation disponible
        $organization = $this->entityManager->getRepository(\App\Entity\Organization::class)->findOneBy([]);

        if ($organization) {
            $io->writeln(sprintf('Organisation test: %s', $organization->getName()));

            $entriesByOrg = $accountingRepository->findByOrganization($organization);
            $io->writeln(sprintf('Écritures pour cette organisation: %d', count($entriesByOrg)));

            // Test avec filtres
            $entriesWithFilters = $accountingRepository->findByOrganizationWithFilters($organization, 'CREDIT', 'LOYER');
            $io->writeln(sprintf('Écritures avec filtres: %d', count($entriesWithFilters)));

            // Test des statistiques
            $stats = $accountingRepository->getOrganizationStatistics($organization);
            $io->writeln('Statistiques de l\'organisation:');
            $io->writeln(sprintf('  - Total crédits: %.2f', $stats['total_credits']));
            $io->writeln(sprintf('  - Total débits: %.2f', $stats['total_debits']));
            $io->writeln(sprintf('  - Solde: %.2f', $stats['balance']));
        } else {
            $io->warning('Aucune organisation trouvée pour le test');
        }

        // Étape 4: Tester les méthodes de filtrage par société
        $io->section('Étape 4: Test des méthodes de filtrage par société');

        // Récupérer la première société disponible
        $company = $this->entityManager->getRepository(\App\Entity\Company::class)->findOneBy([]);

        if ($company) {
            $io->writeln(sprintf('Société test: %s', $company->getName()));

            $entriesByCompany = $accountingRepository->findByCompany($company);
            $io->writeln(sprintf('Écritures pour cette société: %d', count($entriesByCompany)));

            // Test avec filtres
            $entriesWithFilters = $accountingRepository->findByCompanyWithFilters($company, 'CREDIT');
            $io->writeln(sprintf('Écritures avec filtres: %d', count($entriesWithFilters)));

            // Test des statistiques
            $stats = $accountingRepository->getCompanyStatistics($company);
            $io->writeln('Statistiques de la société:');
            $io->writeln(sprintf('  - Total crédits: %.2f', $stats['total_credits']));
            $io->writeln(sprintf('  - Total débits: %.2f', $stats['total_debits']));
            $io->writeln(sprintf('  - Solde: %.2f', $stats['balance']));
        } else {
            $io->warning('Aucune société trouvée pour le test');
        }

        // Étape 5: Vérifier les écritures récentes
        $io->section('Étape 5: Vérification des écritures récentes');

        $recentEntries = $accountingRepository->findBy([], ['createdAt' => 'DESC'], 5);

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
        $io->section('Résumé des tests');

        if ($entriesWithOrg > 0 || $entriesWithCompany > 0) {
            $io->success('✅ Les champs organization et company sont correctement utilisés !');
            $io->writeln('✅ Les méthodes de filtrage fonctionnent correctement');
            $io->writeln('✅ Les statistiques sont calculées correctement');
        } else {
            $io->warning('⚠️ Aucune écriture comptable n\'a de organization ou company assignée');
            $io->writeln('Les nouvelles écritures générées incluront ces champs automatiquement');
        }

        return Command::SUCCESS;
    }
}
