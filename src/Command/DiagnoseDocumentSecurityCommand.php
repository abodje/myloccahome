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
    name: 'app:diagnose-document-security',
    description: 'Diagnostique les problèmes de sécurité des documents existants',
)]
class DiagnoseDocumentSecurityCommand extends Command
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

        $io->title('Diagnostic de sécurité des documents');

        // Récupérer tous les documents
        $documents = $this->entityManager->getRepository(Document::class)->findAll();

        $io->writeln(sprintf('Trouvé %d documents en base de données', count($documents)));

        $encryptedCount = 0;
        $unencryptedCount = 0;
        $missingFiles = 0;
        $errors = [];

        foreach ($documents as $document) {
            $fileName = $document->getFileName();
            $filePath = $this->secureFileService->getDocumentsDirectory() . '/' . $fileName;

            $io->writeln(sprintf("\n📄 Document ID %d: %s", $document->getId(), $document->getName()));
            $io->writeln(sprintf("   Fichier: %s", $fileName));

            if (!file_exists($filePath)) {
                $io->writeln("   ❌ Fichier manquant");
                $missingFiles++;
                continue;
            }

            // Analyser le contenu du fichier
            $content = file_get_contents($filePath);
            if ($content === false) {
                $io->writeln("   ❌ Impossible de lire le fichier");
                $errors[] = sprintf("Document %d: Impossible de lire le fichier", $document->getId());
                continue;
            }

            // Vérifier si le fichier est chiffré (base64 + commence par des caractères aléatoires)
            $isEncrypted = $this->isFileEncrypted($content, $document->getMimeType());

            if ($isEncrypted) {
                $io->writeln("   ✅ Fichier chiffré");
                $encryptedCount++;

                // Tester le déchiffrement
                try {
                    $decrypted = $this->secureFileService->testDecryptFile($document);
                    $io->writeln("   ✅ Déchiffrement réussi");
                } catch (\Exception $e) {
                    $io->writeln("   ❌ Erreur de déchiffrement: " . $e->getMessage());
                    $errors[] = sprintf("Document %d: %s", $document->getId(), $e->getMessage());
                }
            } else {
                $io->writeln("   ⚠️  Fichier non chiffré");
                $unencryptedCount++;
            }
        }

        // Résumé
        $io->section('Résumé du diagnostic');
        $io->table(
            ['Type', 'Nombre', 'Statut'],
            [
                ['Fichiers chiffrés', $encryptedCount, '✅ Sécurisés'],
                ['Fichiers non chiffrés', $unencryptedCount, '⚠️  À migrer'],
                ['Fichiers manquants', $missingFiles, '❌ Problème'],
                ['Erreurs', count($errors), count($errors) > 0 ? '❌ Problème' : '✅ OK']
            ]
        );

        if ($unencryptedCount > 0) {
            $io->warning(sprintf(
                'Il y a %d fichiers non chiffrés qui doivent être migrés vers le système de sécurité.',
                $unencryptedCount
            ));
            $io->writeln('Utilisez la commande: php bin/console app:migrate-documents-security');
        }

        if (count($errors) > 0) {
            $io->error('Erreurs détectées:');
            foreach ($errors as $error) {
                $io->writeln("  - $error");
            }
        }

        if ($encryptedCount > 0 && $unencryptedCount === 0 && count($errors) === 0) {
            $io->success('Tous les documents sont correctement sécurisés !');
        }

        return Command::SUCCESS;
    }

    /**
     * Détermine si un fichier est chiffré
     */
    private function isFileEncrypted(string $content, ?string $mimeType): bool
    {
        // Si le fichier commence par des signatures connues de fichiers non chiffrés
        $unencryptedSignatures = [
            '%PDF-',  // PDF
            'PK\x03\x04',  // ZIP/DOCX
            '\x89PNG',  // PNG
            '\xFF\xD8\xFF',  // JPEG
            'GIF8',  // GIF
        ];

        foreach ($unencryptedSignatures as $signature) {
            if (str_starts_with($content, $signature)) {
                return false;
            }
        }

        // Si le fichier est en base64 et ne commence pas par des signatures connues
        if (base64_decode($content, true) !== false) {
            return true;
        }

        // Par défaut, considérer comme non chiffré si on ne peut pas déterminer
        return false;
    }
}
