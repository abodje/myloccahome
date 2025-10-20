<?php

namespace App\Command;

use App\Entity\AccountingEntry;
use App\Entity\Organization;
use App\Entity\Company;
use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(
    name: 'app:generate-accounting-entries-with-context',
    description: 'Génère des écritures comptables avec organization et company pour tester le filtrage',
)]
class GenerateAccountingEntriesWithContextCommand extends Command
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

        $io->title('Génération d\'écritures comptables avec contexte');

        // Récupérer une organisation et une société
        $organization = $this->entityManager->getRepository(Organization::class)->findOneBy([]);
        $company = $this->entityManager->getRepository(Company::class)->findOneBy([]);

        if (!$organization || !$company) {
            $io->error('Aucune organisation ou société trouvée. Créez d\'abord des organisations et sociétés.');
            return Command::FAILURE;
        }

        $io->writeln(sprintf('Organisation: %s', $organization->getName()));
        $io->writeln(sprintf('Société: %s', $company->getName()));

        // Générer quelques écritures comptables de test
        $testEntries = [
            [
                'description' => 'Test - Vente de service',
                'amount' => 50000,
                'type' => 'CREDIT',
                'category' => 'Revenue'
            ],
            [
                'description' => 'Test - Achat de matériel',
                'amount' => 25000,
                'type' => 'DEBIT',
                'category' => 'Expense'
            ],
            [
                'description' => 'Test - Loyer reçu',
                'amount' => 75000,
                'type' => 'CREDIT',
                'category' => 'Revenue'
            ],
            [
                'description' => 'Test - Maintenance',
                'amount' => 15000,
                'type' => 'DEBIT',
                'category' => 'Expense'
            ],
            [
                'description' => 'Test - Commission',
                'amount' => 10000,
                'type' => 'CREDIT',
                'category' => 'Revenue'
            ]
        ];

        $createdCount = 0;

        foreach ($testEntries as $entryData) {
            $entry = new AccountingEntry();
            $entry->setDescription($entryData['description']);
            $entry->setAmount($entryData['amount']);
            $entry->setType($entryData['type']);
            $entry->setCategory($entryData['category']);
            $entry->setEntryDate(new \DateTime());
            $entry->setOrganization($organization);
            $entry->setCompany($company);
            $entry->setReference('TEST-' . str_pad($createdCount + 1, 3, '0', STR_PAD_LEFT));

            $this->entityManager->persist($entry);
            $createdCount++;
        }

        $this->entityManager->flush();

        $io->success(sprintf('✅ %d écritures comptables créées avec succès !', $createdCount));
        $io->writeln('Ces écritures incluent les champs organization et company pour tester le filtrage.');

        return Command::SUCCESS;
    }
}
