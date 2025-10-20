<?php

namespace App\Command;

use App\Service\ExportService;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(
    name: 'app:test-export-properties',
    description: 'Teste l\'export des propriétés pour vérifier que l\'erreur maintenanceRequests est corrigée.',
)]
class TestExportPropertiesCommand extends Command
{
    private ExportService $exportService;

    public function __construct(ExportService $exportService)
    {
        parent::__construct();
        $this->exportService = $exportService;
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $io->title('Test de l\'export des propriétés');

        try {
            $io->section('1. Test de l\'export Excel des propriétés');

            $excelFile = $this->exportService->generatePropertiesExport(false, 'excel');
            $io->success(sprintf('✅ Export Excel réussi ! Fichier: %s', $excelFile));

            $io->section('2. Test de l\'export Excel des propriétés avec inventaire');

            $excelWithInventoryFile = $this->exportService->generatePropertiesExport(true, 'excel');
            $io->success(sprintf('✅ Export Excel avec inventaire réussi ! Fichier: %s', $excelWithInventoryFile));

            $io->section('3. Test de l\'export PDF des propriétés');

            $pdfFile = $this->exportService->generatePropertiesExport(false, 'pdf');
            $io->success(sprintf('✅ Export PDF réussi ! Fichier: %s', $pdfFile));

            $io->success('🎉 Tous les tests d\'export des propriétés ont réussi !');
            $io->writeln('L\'erreur "maintenanceRequests" a été corrigée avec succès.');

            return Command::SUCCESS;

        } catch (\Exception $e) {
            $io->error('❌ Erreur lors du test d\'export : ' . $e->getMessage());
            $io->writeln('Trace : ' . $e->getTraceAsString());
            return Command::FAILURE;
        }
    }
}
