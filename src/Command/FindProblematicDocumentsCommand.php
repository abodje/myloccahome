<?php

namespace App\Command;

use App\Entity\Document;
use App\Service\SecureFileService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(
    name: 'app:find-problematic-documents',
    description: 'Trouve les documents qui posent problème',
)]
class FindProblematicDocumentsCommand extends Command
{
    public function __construct(
        private EntityManagerInterface $entityManager,
        private SecureFileService $secureFileService
    ) {
        parent::__construct();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        $io->title('Recherche des documents problématiques');

        try {
            // Récupérer tous les documents
            $documents = $this->entityManager->getRepository(Document::class)->findAll();

            $io->writeln("Trouvé " . count($documents) . " documents à vérifier");
            $io->writeln('');

            $problematicDocuments = [];
            $workingDocuments = [];

            foreach ($documents as $document) {
                $io->writeln(sprintf('📄 Document ID %d: %s', $document->getId(), $document->getName()));
                $io->writeln(sprintf('   Fichier: %s', $document->getFileName()));
                $io->writeln(sprintf('   Organisation: %s', $document->getOrganization() ? $document->getOrganization()->getName() : 'AUCUNE'));

                $filePath = $this->secureFileService->getDocumentsDirectory() . '/' . $document->getFileName();

                // Vérifier l'existence du fichier
                if (!file_exists($filePath)) {
                    $io->writeln('   ❌ FICHIER PHYSIQUE MANQUANT');
                    $problematicDocuments[] = [
                        'document' => $document,
                        'issue' => 'Fichier physique manquant',
                        'details' => "Fichier attendu: $filePath"
                    ];
                    continue;
                }

                // Vérifier la taille du fichier
                $fileSize = filesize($filePath);
                if ($fileSize === 0) {
                    $io->writeln('   ❌ FICHIER VIDE');
                    $problematicDocuments[] = [
                        'document' => $document,
                        'issue' => 'Fichier vide',
                        'details' => "Taille: $fileSize bytes"
                    ];
                    continue;
                }

                // Tester le déchiffrement
                try {
                    $content = file_get_contents($filePath);

                    // Vérifier si le fichier semble chiffré
                    if (str_starts_with($content, '%PDF-') ||
                        str_starts_with($content, 'PK') ||
                        str_starts_with($content, 'Document de test')) {
                        $io->writeln('   ⚠️  FICHIER NON CHIFFRÉ');
                        $problematicDocuments[] = [
                            'document' => $document,
                            'issue' => 'Fichier non chiffré',
                            'details' => 'Le fichier semble être en clair'
                        ];
                        continue;
                    }

                    // Tenter le déchiffrement
                    $decryptedContent = $this->secureFileService->testDecryptFile($document);

                    if ($decryptedContent === false || empty($decryptedContent)) {
                        $io->writeln('   ❌ ÉCHEC DU DÉCHIFFREMENT');
                        $problematicDocuments[] = [
                            'document' => $document,
                            'issue' => 'Échec du déchiffrement',
                            'details' => 'Le déchiffrement a échoué'
                        ];
                        continue;
                    }

                    $io->writeln('   ✅ Fichier OK');
                    $workingDocuments[] = $document;

                } catch (\Exception $e) {
                    $io->writeln('   ❌ ERREUR: ' . $e->getMessage());
                    $problematicDocuments[] = [
                        'document' => $document,
                        'issue' => 'Erreur de déchiffrement',
                        'details' => $e->getMessage()
                    ];
                }

                $io->writeln('');
            }

            // Résumé
            $io->section('Résumé');
            $io->writeln(sprintf('✅ Documents fonctionnels: %d', count($workingDocuments)));
            $io->writeln(sprintf('❌ Documents problématiques: %d', count($problematicDocuments)));

            if (!empty($problematicDocuments)) {
                $io->section('Documents problématiques détectés');

                foreach ($problematicDocuments as $problem) {
                    $doc = $problem['document'];
                    $io->writeln(sprintf('📄 ID %d: %s', $doc->getId(), $doc->getName()));
                    $io->writeln(sprintf('   Problème: %s', $problem['issue']));
                    $io->writeln(sprintf('   Détails: %s', $problem['details']));
                    $io->writeln('');
                }

                $io->section('Actions recommandées');
                $io->writeln([
                    '1. Pour les fichiers manquants:',
                    '   php bin/console app:cleanup-missing-files --remove',
                    '',
                    '2. Pour les fichiers non chiffrés:',
                    '   php bin/console app:migrate-documents-security',
                    '',
                    '3. Pour les erreurs de déchiffrement:',
                    '   Vérifiez la clé de chiffrement: php bin/console app:check-encryption-key'
                ]);
            } else {
                $io->success('Tous les documents sont en bon état !');
            }

            return Command::SUCCESS;

        } catch (\Exception $e) {
            $io->error('Erreur lors de la vérification: ' . $e->getMessage());
            return Command::FAILURE;
        }
    }
}
