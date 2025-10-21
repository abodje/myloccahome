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
    name: 'app:debug-decrypt-error',
    description: 'Diagnostic approfondi de l\'erreur de déchiffrement',
)]
class DebugDecryptErrorCommand extends Command
{
    public function __construct(
        private EntityManagerInterface $entityManager,
        private SecureFileService $secureFileService
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this->addOption('document-id', 'd', InputOption::VALUE_REQUIRED, 'ID du document à diagnostiquer', 3);
        $this->addOption('user-id', 'u', InputOption::VALUE_REQUIRED, 'ID de l\'utilisateur', 1);
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        $io->title('Diagnostic approfondi de l\'erreur de déchiffrement');

        $documentId = $input->getOption('document-id');
        $userId = $input->getOption('user-id');

        try {
            // 1. Récupérer le document
            $io->section('1. Informations du document');
            $document = $this->entityManager->getRepository(Document::class)->find($documentId);
            if (!$document) {
                $io->error("Document avec l'ID $documentId non trouvé");
                return Command::FAILURE;
            }

            $io->writeln("ID: " . $document->getId());
            $io->writeln("Nom: " . $document->getName());
            $io->writeln("Fichier: " . $document->getFileName());
            $io->writeln("Type MIME: " . $document->getMimeType());
            $io->writeln("Taille: " . $document->getFileSize() . " bytes");
            $io->writeln("Organisation: " . ($document->getOrganization() ? $document->getOrganization()->getName() : 'Aucune'));

            // 2. Vérifier le fichier physique
            $io->section('2. Vérification du fichier physique');
            $filePath = $this->secureFileService->getDocumentsDirectory() . '/' . $document->getFileName();
            $io->writeln("Chemin: $filePath");

            if (!file_exists($filePath)) {
                $io->error("❌ Fichier physique manquant");
                return Command::FAILURE;
            }

            $fileSize = filesize($filePath);
            $io->writeln("✅ Fichier existe");
            $io->writeln("Taille physique: $fileSize bytes");
            $io->writeln("Taille en base: " . $document->getFileSize() . " bytes");

            if ($fileSize !== $document->getFileSize()) {
                $io->warning("⚠️  Différence de taille entre fichier physique et base de données");
            }

            // 3. Analyser le contenu du fichier
            $io->section('3. Analyse du contenu du fichier');
            $content = file_get_contents($filePath);
            $io->writeln("Contenu lu: " . strlen($content) . " bytes");

            // Vérifier si le fichier semble chiffré
            $firstBytes = substr($content, 0, 50);
            $io->writeln("Premiers bytes: " . bin2hex(substr($content, 0, 20)));

            if (str_starts_with($content, '%PDF-')) {
                $io->warning("⚠️  Fichier semble être un PDF non chiffré");
                $io->writeln("Début du contenu: " . substr($content, 0, 100));
            } elseif (str_starts_with($content, 'PK')) {
                $io->warning("⚠️  Fichier semble être un ZIP non chiffré");
            } else {
                $io->writeln("✅ Fichier semble être chiffré");
            }

            // 4. Test de déchiffrement avec gestion d'erreur détaillée
            $io->section('4. Test de déchiffrement détaillé');
            try {
                $decryptedContent = $this->secureFileService->testDecryptFile($document);
                $io->writeln("✅ Déchiffrement réussi");
                $io->writeln("Contenu déchiffré: " . strlen($decryptedContent) . " bytes");
                $io->writeln("Début du contenu déchiffré: " . substr($decryptedContent, 0, 100));
            } catch (\Exception $e) {
                $io->error("❌ Erreur de déchiffrement: " . $e->getMessage());
                $io->writeln("Type d'erreur: " . get_class($e));
                $io->writeln("Trace complète:");
                $io->writeln($e->getTraceAsString());

                // Analyser l'erreur plus en détail
                if (strpos($e->getMessage(), 'openssl_decrypt') !== false) {
                    $io->writeln("\n🔍 Analyse de l'erreur OpenSSL:");
                    $io->writeln("Le fichier pourrait être corrompu ou utiliser une clé différente");
                }

                return Command::FAILURE;
            }

            // 5. Test avec l'utilisateur
            $io->section('5. Test avec utilisateur');
            $user = $this->entityManager->getRepository(User::class)->find($userId);
            if (!$user) {
                $io->error("Utilisateur avec l'ID $userId non trouvé");
                return Command::FAILURE;
            }

            $io->writeln("Utilisateur: " . $user->getEmail());
            $io->writeln("Rôles: " . implode(', ', $user->getRoles()));

            try {
                $response = $this->secureFileService->downloadSecureFile($document, $user);
                $io->writeln("✅ Téléchargement sécurisé réussi");
                $io->writeln("Type de réponse: " . $response->headers->get('Content-Type'));
                $io->writeln("Taille de réponse: " . strlen($response->getContent()) . " bytes");
            } catch (\Exception $e) {
                $io->error("❌ Erreur de téléchargement sécurisé: " . $e->getMessage());
                $io->writeln("Type d'erreur: " . get_class($e));
                $io->writeln("Trace complète:");
                $io->writeln($e->getTraceAsString());
                return Command::FAILURE;
            }

            // 6. Vérification de la clé de chiffrement
            $io->section('6. Vérification de la clé de chiffrement');
            $envKey = $_ENV['APP_ENCRYPTION_KEY'] ?? null;
            $serverKey = $_SERVER['APP_ENCRYPTION_KEY'] ?? null;

            if ($envKey) {
                $io->writeln("✅ Clé disponible dans \$_ENV");
                $io->writeln("Longueur: " . strlen($envKey) . " caractères");
            } else {
                $io->error("❌ Clé manquante dans \$_ENV");
            }

            if ($serverKey) {
                $io->writeln("✅ Clé disponible dans \$_SERVER");
                $io->writeln("Longueur: " . strlen($serverKey) . " caractères");
            } else {
                $io->error("❌ Clé manquante dans \$_SERVER");
            }

            // Test de chiffrement/déchiffrement simple
            try {
                $testData = 'Test de chiffrement - ' . date('Y-m-d H:i:s');
                $iv = random_bytes(16);
                $encrypted = openssl_encrypt($testData, 'AES-256-CBC', $envKey, 0, $iv);
                $decrypted = openssl_decrypt($encrypted, 'AES-256-CBC', $envKey, 0, $iv);

                if ($decrypted === $testData) {
                    $io->writeln("✅ Test de chiffrement/déchiffrement réussi");
                } else {
                    $io->error("❌ Test de chiffrement/déchiffrement échoué");
                }
            } catch (\Exception $e) {
                $io->error("❌ Erreur lors du test de chiffrement: " . $e->getMessage());
            }

            $io->success('Diagnostic terminé !');

            return Command::SUCCESS;

        } catch (\Exception $e) {
            $io->error('Erreur lors du diagnostic: ' . $e->getMessage());
            $io->writeln('Trace: ' . $e->getTraceAsString());
            return Command::FAILURE;
        }
    }
}
