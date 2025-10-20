<?php

namespace App\Command;

use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(
    name: 'app:check-accounting-entries',
    description: 'Vérifie les écritures comptables créées par la génération de loyers',
)]
class CheckAccountingEntriesCommand extends Command
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

        $io->title('Vérification des écritures comptables');

        // Statistiques générales
        $totalEntries = $this->entityManager->getRepository(\App\Entity\AccountingEntry::class)
            ->count([]);

        $loyerEntries = $this->entityManager->getRepository(\App\Entity\AccountingEntry::class)
            ->count(['category' => 'LOYER_ATTENDU']);

        $io->writeln(sprintf('Total écritures comptables: %d', $totalEntries));
        $io->writeln(sprintf('Écritures LOYER_ATTENDU: %d', $loyerEntries));

        // Détails des dernières écritures LOYER_ATTENDU
        $io->section('Dernières écritures LOYER_ATTENDU');

        $entries = $this->entityManager->getRepository(\App\Entity\AccountingEntry::class)
            ->findBy(['category' => 'LOYER_ATTENDU'], ['createdAt' => 'DESC'], 10);

        if (empty($entries)) {
            $io->warning('Aucune écriture comptable LOYER_ATTENDU trouvée.');
            return Command::SUCCESS;
        }

        $io->table(
            ['ID', 'Date', 'Description', 'Montant', 'Référence', 'Propriété'],
            array_map(function($entry) {
                return [
                    $entry->getId(),
                    $entry->getEntryDate()->format('d/m/Y'),
                    substr($entry->getDescription(), 0, 50) . '...',
                    $entry->getAmount() . ' FCFA',
                    $entry->getReference(),
                    $entry->getProperty() ? $entry->getProperty()->getFullAddress() : 'N/A'
                ];
            }, $entries)
        );

        // Vérifier les paiements associés
        $io->section('Paiements associés aux écritures comptables');

        $paymentsWithAccounting = $this->entityManager->createQuery(
            'SELECT COUNT(p) FROM App\Entity\Payment p
             WHERE p.type = :type AND p.id IN (
                 SELECT IDENTITY(ae.payment) FROM App\Entity\AccountingEntry ae
                 WHERE ae.category = :category
             )'
        )
        ->setParameter('type', 'Loyer')
        ->setParameter('category', 'LOYER_ATTENDU')
        ->getSingleScalarResult();

        $totalPayments = $this->entityManager->getRepository(\App\Entity\Payment::class)
            ->count(['type' => 'Loyer']);

        $io->writeln(sprintf('Paiements loyers avec écritures comptables: %d', $paymentsWithAccounting));
        $io->writeln(sprintf('Total paiements loyers: %d', $totalPayments));

        if ($paymentsWithAccounting > 0) {
            $io->success(sprintf('✅ %d paiements loyers ont des écritures comptables associées !', $paymentsWithAccounting));
        } else {
            $io->warning('⚠️ Aucun paiement loyer n\'a d\'écriture comptable associée.');
        }

        return Command::SUCCESS;
    }
}
