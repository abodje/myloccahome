<?php

namespace App\Command;

use App\Entity\Document;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(
    name: 'app:cleanup-missing-files',
    description: 'Nettoie les références de fichiers manquants dans la base de données',
)]
class CleanupMissingFilesCommand extends Command
{
    public function __construct(
        private EntityManagerInterface $entityManager
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this->addOption('dry-run', null, InputOption::VALUE_NONE, 'Mode simulation - ne supprime rien');
        $this->addOption('fix-references', null, InputOption::VALUE_NONE, 'Essaie de corriger les références de fichiers');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        $io->title('Nettoyage des fichiers manquants');

        $dryRun = $input->getOption('dry-run');
        $fixReferences = $input->getOption('fix-references');

        if ($dryRun) {
            $io->warning('Mode simulation activé - aucune modification ne sera effectuée');
        }

        // Récupérer tous les documents
        $documents = $this->entityManager->getRepository(Document::class)->findAll();
        $io->writeln(sprintf('Trouvé %d documents en base de données', count($documents)));

        $missingFiles = [];
        $existingFiles = [];
        $fixedReferences = [];

        foreach ($documents as $document) {
            $fileName = $document->getFileName();
            $filePath = 'public/uploads/documents/' . $fileName;

            if (!file_exists($filePath)) {
                $missingFiles[] = $document;
                $io->writeln(sprintf("❌ Fichier manquant: %s (Document ID: %d)", $fileName, $document->getId()));

                if ($fixReferences) {
                    // Essayer de trouver un fichier similaire
                    $similarFile = $this->findSimilarFile($fileName);
                    if ($similarFile) {
                        $io->writeln(sprintf("   🔧 Fichier similaire trouvé: %s", $similarFile));
                        $fixedReferences[] = ['document' => $document, 'newFile' => $similarFile];
                    }
                }
            } else {
                $existingFiles[] = $document;
                $io->writeln(sprintf("✅ Fichier existant: %s (Document ID: %d)", $fileName, $document->getId()));
            }
        }

        // Résumé
        $io->section('Résumé');
        $io->table(
            ['Type', 'Nombre'],
            [
                ['Fichiers existants', count($existingFiles)],
                ['Fichiers manquants', count($missingFiles)],
                ['Références corrigées', count($fixedReferences)]
            ]
        );

        if (count($missingFiles) > 0) {
            $io->section('Actions recommandées');

            if ($dryRun) {
                $io->writeln('Pour supprimer les documents avec fichiers manquants:');
                $io->writeln('php bin/console app:cleanup-missing-files');

                $io->writeln('Pour corriger les références de fichiers:');
                $io->writeln('php bin/console app:cleanup-missing-files --fix-references');
            } else {
                // Supprimer les documents avec fichiers manquants
                foreach ($missingFiles as $document) {
                    $this->entityManager->remove($document);
                    $io->writeln(sprintf("🗑️  Document supprimé: %s (ID: %d)", $document->getName(), $document->getId()));
                }

                // Corriger les références si demandé
                if ($fixReferences) {
                    foreach ($fixedReferences as $fix) {
                        $document = $fix['document'];
                        $newFile = $fix['newFile'];
                        $document->setFileName($newFile);
                        $io->writeln(sprintf("🔧 Référence corrigée: %s -> %s", $document->getName(), $newFile));
                    }
                }

                $this->entityManager->flush();
                $io->success(sprintf('%d documents nettoyés avec succès', count($missingFiles)));
            }
        } else {
            $io->success('Aucun fichier manquant trouvé !');
        }

        return Command::SUCCESS;
    }

    /**
     * Trouve un fichier similaire dans le répertoire
     */
    private function findSimilarFile(string $missingFileName): ?string
    {
        $documentsDir = 'public/uploads/documents/';
        $files = glob($documentsDir . '*');

        // Extraire le nom de base du fichier manquant
        $baseName = pathinfo($missingFileName, PATHINFO_FILENAME);
        $extension = pathinfo($missingFileName, PATHINFO_EXTENSION);

        foreach ($files as $file) {
            $fileName = basename($file);
            if (is_file($file) && $fileName !== '.htaccess') {
                // Chercher des fichiers avec un nom similaire
                if (strpos($fileName, $baseName) !== false || strpos($baseName, pathinfo($fileName, PATHINFO_FILENAME)) !== false) {
                    return $fileName;
                }
            }
        }

        return null;
    }
}
