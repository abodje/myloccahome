<?php

namespace App\Command;

use App\Entity\Document;
use App\Entity\Organization;
use App\Entity\User;
use App\Service\SecureFileService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\HttpFoundation\File\UploadedFile;

#[AsCommand(
    name: 'app:test-document-upload',
    description: 'Teste le processus complet d\'upload et téléchargement de documents',
)]
class TestDocumentUploadCommand extends Command
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

        $io->title('Test complet d\'upload et téléchargement de documents');

        try {
            // 1. Créer un fichier de test temporaire
            $testContent = "Ceci est un fichier de test pour vérifier le chiffrement/déchiffrement.\nDate: " . date('Y-m-d H:i:s');
            $tempFile = tempnam(sys_get_temp_dir(), 'test_document');
            file_put_contents($tempFile, $testContent);

            $io->section('1. Création du fichier de test');
            $io->writeln("Fichier temporaire créé: $tempFile");
            $io->writeln("Contenu: " . substr($testContent, 0, 50) . "...");

            // 2. Créer un UploadedFile simulé
            $uploadedFile = new UploadedFile(
                $tempFile,
                'test_document.txt',
                'text/plain',
                null,
                true
            );

            $io->section('2. Simulation de l\'upload');
            $io->writeln("Nom original: " . $uploadedFile->getClientOriginalName());
            $io->writeln("Type MIME: " . $uploadedFile->getMimeType());
            $io->writeln("Taille: " . $uploadedFile->getSize() . " bytes");

            // 3. Créer un document en base de données
            $document = new Document();
            $document->setName('Document de test');
            $document->setType('Test'); // Champ requis
            $document->setDescription('Test de chiffrement/déchiffrement');
            $document->setMimeType('text/plain');
            $document->setOriginalFileName('test_document.txt');
            $document->setFileSize($uploadedFile->getSize());
            $document->setCreatedAt(new \DateTime());

            // Récupérer ou créer une organisation de test
            $organization = $this->entityManager->getRepository(Organization::class)->findOneBy([]);
            if (!$organization) {
                $organization = new Organization();
                $organization->setName('Organisation de test');
                $organization->setSlug('test-org');
                $organization->setCreatedAt(new \DateTime());
                $this->entityManager->persist($organization);
            }
            $document->setOrganization($organization);

            // 4. Uploader le fichier de manière sécurisée AVANT de sauvegarder en base
            $secureFileName = $this->secureFileService->uploadSecureFile($uploadedFile, $document);
            $document->setFileName($secureFileName);

            $this->entityManager->persist($document);
            $this->entityManager->flush();

            $io->writeln("Document créé en base avec ID: " . $document->getId());
            $io->writeln("Fichier uploadé avec le nom sécurisé: $secureFileName");

            // 5. Vérifier que le fichier chiffré existe
            $filePath = $this->secureFileService->getDocumentsDirectory() . '/' . $secureFileName;
            if (file_exists($filePath)) {
                $io->writeln("✅ Fichier chiffré créé: $filePath");
                $encryptedContent = file_get_contents($filePath);
                $io->writeln("Contenu chiffré (premiers 50 chars): " . substr($encryptedContent, 0, 50) . "...");
            } else {
                $io->error("❌ Fichier chiffré non trouvé: $filePath");
                return Command::FAILURE;
            }

            // 6. Tester le déchiffrement
            $io->section('3. Test de déchiffrement');
            try {
                $decryptedContent = $this->secureFileService->testDecryptFile($document);

                if ($decryptedContent === $testContent) {
                    $io->writeln("✅ Déchiffrement réussi !");
                    $io->writeln("Contenu déchiffré: " . substr($decryptedContent, 0, 50) . "...");
                } else {
                    $io->error("❌ Contenu déchiffré incorrect");
                    $io->writeln("Attendu: " . substr($testContent, 0, 50) . "...");
                    $io->writeln("Reçu: " . substr($decryptedContent, 0, 50) . "...");
                    return Command::FAILURE;
                }
            } catch (\Exception $e) {
                $io->error("❌ Erreur lors du déchiffrement: " . $e->getMessage());
                return Command::FAILURE;
            }

            // 7. Nettoyage
            $io->section('4. Nettoyage');
            unlink($tempFile);
            $this->secureFileService->deleteSecureFile($secureFileName);
            $this->entityManager->remove($document);
            $this->entityManager->flush();

            $io->writeln("✅ Fichiers temporaires supprimés");
            $io->writeln("✅ Document supprimé de la base de données");

            $io->success('Test complet réussi ! Le système de chiffrement/déchiffrement fonctionne correctement.');

            return Command::SUCCESS;

        } catch (\Exception $e) {
            $io->error('Erreur lors du test: ' . $e->getMessage());
            $io->writeln('Trace: ' . $e->getTraceAsString());
            return Command::FAILURE;
        }
    }
}
