<?php

namespace App\Command;

use App\Entity\Document;
use App\Entity\AccountingEntry;
use App\Repository\DocumentRepository;
use App\Repository\AccountingEntryRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(
    name: 'app:sync-accounting-entries',
    description: 'Synchronise les écritures comptables avec les documents de quittances et avis d\'échéances',
)]
class SyncAccountingEntriesCommand extends Command
{
    public function __construct(
        private EntityManagerInterface $entityManager,
        private DocumentRepository $documentRepository,
        private AccountingEntryRepository $accountingRepository
    ) {
        parent::__construct();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        $io->title('🔄 Synchronisation des écritures comptables');

        // Récupérer tous les documents de quittances et avis d'échéances
        $receipts = $this->documentRepository->findBy(['type' => 'Quittance de loyer']);
        $notices = $this->documentRepository->findBy(['type' => 'Avis d\'échéance']);

        $io->info(sprintf(
            'Documents trouvés : %d quittances, %d avis d\'échéance',
            count($receipts),
            count($notices)
        ));

        $createdEntries = 0;
        $updatedEntries = 0;

        // Traiter les quittances
        foreach ($receipts as $document) {
            $payment = $document->getLease()?->getPayments()->first();
            if (!$payment) {
                continue;
            }

            $existingEntry = $this->accountingRepository->findOneBy(['payment' => $payment]);
            if ($existingEntry) {
                // Mettre à jour la référence
                if (!$existingEntry->getReference() || !str_contains($existingEntry->getReference(), 'QUITTANCE-')) {
                    $existingEntry->setReference('QUITTANCE-' . $document->getId());
                    $updatedEntries++;
                }
            } else {
                // Créer une nouvelle écriture
                $entry = new AccountingEntry();
                $entry->setEntryDate($payment->getPaidDate() ?? $payment->getDueDate());
                $entry->setDescription('Quittance de loyer - ' . $document->getName());
                $entry->setAmount($payment->getAmount());
                $entry->setType('CREDIT');
                $entry->setCategory('LOYER');
                $entry->setReference('QUITTANCE-' . $document->getId());
                $entry->setProperty($payment->getProperty());
                $entry->setOwner($payment->getProperty()?->getOwner());
                $entry->setPayment($payment);
                $entry->setNotes('Généré automatiquement lors de la synchronisation');

                $this->entityManager->persist($entry);
                $createdEntries++;
            }
        }

        // Traiter les avis d'échéance
        foreach ($notices as $document) {
            $payment = $document->getLease()?->getPayments()->first();
            if (!$payment) {
                continue;
            }

            $existingEntry = $this->accountingRepository->findOneBy(['payment' => $payment]);
            if ($existingEntry) {
                // Mettre à jour la référence
                if (!$existingEntry->getReference() || !str_contains($existingEntry->getReference(), 'AVIS-')) {
                    $existingEntry->setReference('AVIS-' . $document->getId());
                    $updatedEntries++;
                }
            } else {
                // Créer une nouvelle écriture
                $entry = new AccountingEntry();
                $entry->setEntryDate($payment->getDueDate());
                $entry->setDescription('Avis d\'échéance - ' . $document->getName());
                $entry->setAmount($payment->getAmount());
                $entry->setType('CREDIT');
                $entry->setCategory('LOYER_ATTENDU');
                $entry->setReference('AVIS-' . $document->getId());
                $entry->setProperty($payment->getProperty());
                $entry->setOwner($payment->getProperty()?->getOwner());
                $entry->setPayment($payment);
                $entry->setNotes('Généré automatiquement lors de la synchronisation');

                $this->entityManager->persist($entry);
                $createdEntries++;
            }
        }

        // Sauvegarder les changements
        $this->entityManager->flush();

        $io->success(sprintf(
            '✅ Synchronisation terminée : %d écritures créées, %d écritures mises à jour',
            $createdEntries,
            $updatedEntries
        ));

        return Command::SUCCESS;
    }
}
