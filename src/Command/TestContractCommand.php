<?php

namespace App\Command;

use App\Repository\LeaseRepository;
use App\Service\ContractGenerationService;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(
    name: 'app:test-contract',
    description: 'Teste la génération d\'un contrat de bail',
)]
class TestContractCommand extends Command
{
    public function __construct(
        private LeaseRepository $leaseRepository,
        private ContractGenerationService $contractService
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this->addArgument('lease-id', InputArgument::OPTIONAL, 'ID du bail (défaut: premier bail actif)');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        $leaseId = $input->getArgument('lease-id');

        if ($leaseId) {
            $lease = $this->leaseRepository->find($leaseId);
        } else {
            $lease = $this->leaseRepository->findOneBy(['status' => 'Actif']);
        }

        if (!$lease) {
            $io->error('Aucun bail trouvé !');
            return Command::FAILURE;
        }

        $io->info("Génération du contrat pour :");
        $io->listing([
            "Bail #" . $lease->getId(),
            "Locataire : " . $lease->getTenant()->getFullName(),
            "Propriété : " . $lease->getProperty()->getAddress(),
            "Loyer : " . $lease->getMonthlyRent() . " €",
        ]);

        try {
            $io->info('Génération en cours...');
            $document = $this->contractService->generateContractManually($lease);

            $io->success([
                '✅ Contrat généré avec succès !',
                '',
                'Fichier : ' . $document->getFileName(),
                'Taille : ' . round($document->getFileSize() / 1024, 2) . ' KB',
                'Document ID : ' . $document->getId(),
            ]);

            $io->info('Vérifiez le fichier : public/uploads/documents/' . $document->getFileName());

            return Command::SUCCESS;
        } catch (\Exception $e) {
            $io->error('Erreur lors de la génération : ' . $e->getMessage());
            $io->note('Trace : ' . $e->getTraceAsString());
            return Command::FAILURE;
        }
    }
}

