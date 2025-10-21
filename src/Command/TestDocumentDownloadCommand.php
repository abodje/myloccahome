<?php

namespace App\Command;

use App\Entity\Document;
use App\Entity\User;
use App\Service\SecureFileService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(
    name: 'app:test-document-download',
    description: 'Teste le téléchargement d\'un document spécifique',
)]
class TestDocumentDownloadCommand extends Command
{
    public function __construct(
        private EntityManagerInterface $entityManager,
        private SecureFileService $secureFileService
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this->addOption('document-id', 'd', InputOption::VALUE_REQUIRED, 'ID du document à tester');
        $this->addOption('user-id', 'u', InputOption::VALUE_OPTIONAL, 'ID de l\'utilisateur pour le test');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        $documentId = $input->getOption('document-id');
        if (!$documentId) {
            $io->error('Veuillez spécifier l\'ID du document avec --document-id');
            return Command::FAILURE;
        }

        $io->title('Test de téléchargement de document');

        try {
            // Récupérer le document
            $document = $this->entityManager->getRepository(Document::class)->find($documentId);
            if (!$document) {
                $io->error("Document avec l'ID $documentId non trouvé");
                return Command::FAILURE;
            }

            $io->writeln(sprintf("📄 Document trouvé: %s (ID: %d)", $document->getName(), $document->getId()));
            $io->writeln(sprintf("   Fichier: %s", $document->getFileName()));
            $io->writeln(sprintf("   Type: %s", $document->getMimeType()));
            $io->writeln(sprintf("   Organisation: %s", $document->getOrganization()?->getName() ?? 'Aucune'));

            // Récupérer l'utilisateur si spécifié
            $user = null;
            $userId = $input->getOption('user-id');
            if ($userId) {
                $user = $this->entityManager->getRepository(User::class)->find($userId);
                if (!$user) {
                    $io->error("Utilisateur avec l'ID $userId non trouvé");
                    return Command::FAILURE;
                }
                $io->writeln(sprintf("👤 Utilisateur: %s (ID: %d)", $user->getEmail(), $user->getId()));
                $io->writeln(sprintf("   Rôles: %s", implode(', ', $user->getRoles())));
                $io->writeln(sprintf("   Organisation: %s", $user->getOrganization()?->getName() ?? 'Aucune'));
            } else {
                $io->writeln("👤 Aucun utilisateur spécifié - test sans permissions");
            }

            // Test 1: Vérifier l'existence du fichier
            $io->section('1. Vérification du fichier');
            $filePath = $this->secureFileService->getDocumentsDirectory() . '/' . $document->getFileName();
            if (file_exists($filePath)) {
                $io->writeln("✅ Fichier physique existe: $filePath");
                $fileSize = filesize($filePath);
                $io->writeln(sprintf("   Taille: %d bytes", $fileSize));
            } else {
                $io->error("❌ Fichier physique manquant: $filePath");
                return Command::FAILURE;
            }

            // Test 2: Test de déchiffrement direct
            $io->section('2. Test de déchiffrement direct');
            try {
                $decryptedContent = $this->secureFileService->testDecryptFile($document);
                $io->writeln("✅ Déchiffrement direct réussi");
                $io->writeln(sprintf("   Contenu déchiffré: %d bytes", strlen($decryptedContent)));
                $io->writeln(sprintf("   Début du contenu: %s", substr($decryptedContent, 0, 50) . "..."));
            } catch (\Exception $e) {
                $io->error("❌ Erreur de déchiffrement direct: " . $e->getMessage());
                return Command::FAILURE;
            }

            // Test 3: Test de téléchargement sécurisé
            $io->section('3. Test de téléchargement sécurisé');
            try {
                $response = $this->secureFileService->downloadSecureFile($document, $user);
                $io->writeln("✅ Téléchargement sécurisé réussi");
                $io->writeln(sprintf("   Type de contenu: %s", $response->headers->get('Content-Type')));
                $io->writeln(sprintf("   Taille: %d bytes", strlen($response->getContent())));
            } catch (\Exception $e) {
                $io->error("❌ Erreur de téléchargement sécurisé: " . $e->getMessage());
                $io->writeln("   Trace: " . $e->getTraceAsString());
                return Command::FAILURE;
            }

            $io->success('Tous les tests sont passés avec succès !');

            return Command::SUCCESS;

        } catch (\Exception $e) {
            $io->error('Erreur lors du test: ' . $e->getMessage());
            $io->writeln('Trace: ' . $e->getTraceAsString());
            return Command::FAILURE;
        }
    }
}
